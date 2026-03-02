<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentLessonService
{
  /**
   * Lấy chi tiết bài học cho học sinh: slides + quiz info + progress
   */
  public function getLessonDetail(int $lessonId, int $studentId): array
  {
    try {
      $lesson = Lesson::with([
        'presentation.slides' => fn($q) => $q->orderBy('order'),
        'quizzes' => fn($q) => $q->where('status', 'published'),
        'quizzes.questions' => fn($q) => $q->orderBy('order'),
      ])->findOrFail($lessonId);

      // Kiểm tra học sinh có thuộc lớp không
      $isEnrolled = DB::table('enrollments')
        ->where('class_id', $lesson->class_id)
        ->where('user_id', $studentId)
        ->where('status', 'approved')
        ->exists();

      if (!$isEnrolled) {
        return [
          'status' => 403,
          'data' => [
            'success' => false,
            'message' => 'Bạn không thuộc lớp học này',
          ],
        ];
      }

      // Lấy hoặc tạo progress
      $progress = LessonProgress::firstOrCreate(
        ['student_id' => $studentId, 'lesson_id' => $lessonId],
        [
          'status' => 'not_started',
          'slides_viewed' => 0,
          'total_slides' => $lesson->presentation?->slides?->count() ?? 0,
          'time_spent' => 0,
        ]
      );

      // Lấy quiz attempts
      $quizAttempts = [];
      foreach ($lesson->quizzes as $quiz) {
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
          ->where('student_id', $studentId)
          ->orderByDesc('attempt_number')
          ->get();
        $quizAttempts[$quiz->id] = $attempts;
      }

      // Build response - loại bỏ is_correct khỏi options (không cho HS thấy đáp án)
      $quizzes = $lesson->quizzes->map(function ($quiz) use ($quizAttempts) {
        $latestAttempt = $quizAttempts[$quiz->id]->first();
        $attemptCount = $quizAttempts[$quiz->id]->count();

        return [
          'id' => $quiz->id,
          'title' => $quiz->title,
          'description' => $quiz->description,
          'time_limit' => $quiz->time_limit,
          'max_attempts' => $quiz->max_attempts,
          'question_count' => $quiz->questions->count(),
          'total_points' => $quiz->questions->sum('points'),
          'attempt_count' => $attemptCount,
          'can_attempt' => $quiz->max_attempts === null || $attemptCount < $quiz->max_attempts,
          'best_score' => $quizAttempts[$quiz->id]->max('percentage'),
          'latest_attempt' => $latestAttempt ? [
            'id' => $latestAttempt->id,
            'score' => $latestAttempt->score,
            'percentage' => $latestAttempt->percentage,
            'status' => $latestAttempt->status,
            'submitted_at' => $latestAttempt->submitted_at,
          ] : null,
        ];
      });

      $totalSlides = $lesson->presentation?->slides?->count() ?? 0;
      $slidesCompleted = $progress->slides_viewed >= $totalSlides && $totalSlides > 0;

      // Kiểm tra hoàn thành quiz (có ít nhất 1 quiz đã submitted)
      $hasQuiz = $lesson->quizzes->isNotEmpty();
      $quizCompleted = false;
      if ($hasQuiz) {
        foreach ($lesson->quizzes as $quiz) {
          $submitted = $quizAttempts[$quiz->id]->where('status', 'submitted')->first()
            ?? $quizAttempts[$quiz->id]->where('status', 'graded')->first();
          if ($submitted) {
            $quizCompleted = true;
            break;
          }
        }
      }

      $lessonCompleted = $slidesCompleted && (!$hasQuiz || $quizCompleted);

      // Auto-update progress status
      if ($lessonCompleted && $progress->status !== 'completed') {
        $progress->update([
          'status' => 'completed',
          'completed_at' => now(),
        ]);
      } elseif (!$lessonCompleted && $progress->slides_viewed > 0 && $progress->status === 'not_started') {
        $progress->update([
          'status' => 'in_progress',
          'started_at' => now(),
        ]);
      }

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'objectives' => $lesson->objectives,
            'slides' => $lesson->presentation?->slides?->map(fn($s) => [
              'id' => $s->id,
              'order' => $s->order,
              'title' => $s->title,
              'content' => $s->content,
              'notes' => $s->notes,
              'layout' => $s->layout,
              'image_url' => $s->image_url,
            ]) ?? [],
            'quizzes' => $quizzes,
            'progress' => [
              'status' => $progress->status,
              'slides_viewed' => $progress->slides_viewed,
              'total_slides' => $totalSlides,
              'slides_completed' => $slidesCompleted,
              'quiz_completed' => $quizCompleted,
              'lesson_completed' => $lessonCompleted,
              'time_spent' => $progress->time_spent,
              'started_at' => $progress->started_at,
              'completed_at' => $progress->completed_at,
            ],
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Student get lesson detail failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy chi tiết bài học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật tiến trình xem slide
   */
  public function updateSlideProgress(int $lessonId, int $studentId, int $slidesViewed, int $totalSlides): array
  {
    try {
      $progress = LessonProgress::firstOrCreate(
        ['student_id' => $studentId, 'lesson_id' => $lessonId],
        [
          'status' => 'not_started',
          'slides_viewed' => 0,
          'total_slides' => $totalSlides,
          'time_spent' => 0,
        ]
      );

      $updateData = [
        'slides_viewed' => max($progress->slides_viewed, $slidesViewed),
        'total_slides' => $totalSlides,
      ];

      if ($progress->status === 'not_started') {
        $updateData['status'] = 'in_progress';
        $updateData['started_at'] = now();
      }

      // Nếu đã xem hết slide
      if ($slidesViewed >= $totalSlides) {
        $updateData['slides_viewed'] = $totalSlides;
      }

      $progress->update($updateData);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => [
            'slides_viewed' => $progress->slides_viewed,
            'total_slides' => $progress->total_slides,
            'slides_completed' => $progress->slides_viewed >= $totalSlides,
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Update slide progress failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật tiến trình: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Bắt đầu làm quiz - tạo attempt mới
   */
  public function startQuizAttempt(int $quizId, int $studentId): array
  {
    try {
      $quiz = Quiz::with('questions.options')->findOrFail($quizId);

      // Kiểm tra số lần làm
      $attemptCount = QuizAttempt::where('quiz_id', $quizId)
        ->where('student_id', $studentId)
        ->count();

      if ($quiz->max_attempts && $attemptCount >= $quiz->max_attempts) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => "Bạn đã hết lượt làm bài (tối đa {$quiz->max_attempts} lần)",
          ],
        ];
      }

      // Kiểm tra student đã xem hết slides chưa
      $lesson = $quiz->lesson;
      if ($lesson) {
        $progress = LessonProgress::where('student_id', $studentId)
          ->where('lesson_id', $lesson->id)
          ->first();

        $totalSlides = $lesson->presentation?->slides?->count() ?? 0;
        if ($totalSlides > 0 && (!$progress || $progress->slides_viewed < $totalSlides)) {
          return [
            'status' => 400,
            'data' => [
              'success' => false,
              'message' => 'Bạn cần xem hết slide trước khi làm quiz',
            ],
          ];
        }
      }

      $attempt = QuizAttempt::create([
        'quiz_id' => $quizId,
        'student_id' => $studentId,
        'attempt_number' => $attemptCount + 1,
        'started_at' => now(),
        'status' => 'in_progress',
      ]);

      // Trả về câu hỏi (không gửi is_correct)
      $questions = $quiz->questions->map(function ($q) use ($quiz) {
        $options = $q->options->map(fn($o) => [
          'id' => $o->id,
          'order' => $o->order,
          'option_text' => $o->option_text,
        ]);

        // Shuffle options nếu cài đặt
        if ($quiz->shuffle_options) {
          $options = $options->shuffle()->values();
        }

        return [
          'id' => $q->id,
          'order' => $q->order,
          'content' => $q->content,
          'question_type' => $q->question_type,
          'points' => $q->points,
          'options' => $options,
        ];
      });

      // Shuffle questions nếu cài đặt
      if ($quiz->shuffle_questions) {
        $questions = $questions->shuffle()->values();
      }

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'data' => [
            'attempt_id' => $attempt->id,
            'quiz_title' => $quiz->title,
            'time_limit' => $quiz->time_limit,
            'started_at' => $attempt->started_at,
            'questions' => $questions,
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Start quiz attempt failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi bắt đầu quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Nộp bài quiz - chấm điểm tự động (MCQ)
   */
  public function submitQuiz(int $quizId, int $studentId, int $attemptId, array $answers): array
  {
    DB::beginTransaction();
    try {
      $attempt = QuizAttempt::where('id', $attemptId)
        ->where('quiz_id', $quizId)
        ->where('student_id', $studentId)
        ->where('status', 'in_progress')
        ->firstOrFail();

      $quiz = Quiz::with('questions.options')->findOrFail($quizId);

      $totalScore = 0;
      $totalPoints = 0;

      foreach ($quiz->questions as $question) {
        $totalPoints += $question->points;

        // Tìm câu trả lời của student cho câu hỏi này
        $studentAnswer = collect($answers)->firstWhere('question_id', $question->id);

        if (!$studentAnswer) {
          // Không trả lời → 0 điểm
          QuizAttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_type' => 'option',
            'answer_option_id' => null,
            'is_correct' => false,
            'points_earned' => 0,
            'submitted_at' => now(),
          ]);
          continue;
        }

        $selectedOptionId = $studentAnswer['option_id'];
        $correctOption = $question->options->firstWhere('is_correct', true);
        $isCorrect = $correctOption && $correctOption->id === $selectedOptionId;

        $pointsEarned = $isCorrect ? $question->points : 0;
        $totalScore += $pointsEarned;

        QuizAttemptAnswer::create([
          'attempt_id' => $attempt->id,
          'question_id' => $question->id,
          'answer_type' => 'option',
          'answer_option_id' => $selectedOptionId,
          'is_correct' => $isCorrect,
          'points_earned' => $pointsEarned,
          'submitted_at' => now(),
        ]);
      }

      $percentage = $totalPoints > 0 ? round(($totalScore / $totalPoints) * 100, 2) : 0;

      $attempt->update([
        'score' => $totalScore,
        'percentage' => $percentage,
        'status' => 'submitted',
        'submitted_at' => now(),
      ]);

      // Cập nhật lesson progress nếu pass
      $lesson = $quiz->lesson;
      if ($lesson) {
        $progress = LessonProgress::where('student_id', $studentId)
          ->where('lesson_id', $lesson->id)
          ->first();

        $totalSlides = $lesson->presentation?->slides?->count() ?? 0;
        $slidesCompleted = $progress && $progress->slides_viewed >= $totalSlides && $totalSlides > 0;

        if ($slidesCompleted && $progress->status !== 'completed') {
          $progress->update([
            'status' => 'completed',
            'completed_at' => now(),
          ]);
        }
      }

      DB::commit();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Nộp bài thành công!',
          'data' => [
            'attempt_id' => $attempt->id,
            'score' => $totalScore,
            'total_points' => $totalPoints,
            'percentage' => $percentage,
            'status' => 'submitted',
          ],
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Submit quiz failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi nộp bài: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy kết quả chi tiết của quiz attempt
   */
  public function getQuizResult(int $attemptId, int $studentId): array
  {
    try {
      $attempt = QuizAttempt::with(['answers.question.options', 'answers.selectedOption', 'quiz'])
        ->where('id', $attemptId)
        ->where('student_id', $studentId)
        ->firstOrFail();

      $quiz = $attempt->quiz;
      $showAnswers = $quiz->show_answers_after;

      $questions = $attempt->answers->map(function ($answer) use ($showAnswers) {
        $question = $answer->question;
        $options = $question->options->map(function ($option) use ($answer, $showAnswers) {
          $data = [
            'id' => $option->id,
            'order' => $option->order,
            'option_text' => $option->option_text,
            'is_selected' => $option->id === $answer->answer_option_id,
          ];

          if ($showAnswers) {
            $data['is_correct'] = $option->is_correct;
          }

          return $data;
        });

        $data = [
          'question_id' => $question->id,
          'content' => $question->content,
          'points' => $question->points,
          'points_earned' => $answer->points_earned,
          'is_correct' => $answer->is_correct,
          'options' => $options,
        ];

        if ($showAnswers) {
          $data['explanation'] = $question->explanation;
        }

        return $data;
      });

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => [
            'attempt_id' => $attempt->id,
            'quiz_title' => $quiz->title,
            'score' => $attempt->score,
            'total_points' => $quiz->questions->sum('points'),
            'percentage' => $attempt->percentage,
            'started_at' => $attempt->started_at,
            'submitted_at' => $attempt->submitted_at,
            'questions' => $questions,
            'show_answers' => $showAnswers,
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get quiz result failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy kết quả quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }
}
