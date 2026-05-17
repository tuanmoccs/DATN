<?php

namespace App\Services;

use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Repositories\Contracts\QuizRepositoryInterface;
use App\Repositories\Contracts\LessonRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizService
{
  public function __construct(
    private readonly QuizRepositoryInterface $quizRepository,
    private readonly LessonRepositoryInterface $lessonRepository,
  ) {}

  /**
   * Lấy danh sách quiz theo bài học
   */
  public function getQuizzesByLesson(int $lessonId, int $teacherId): array
  {
    try {
      $lesson = $this->lessonRepository->findOrFail($lessonId);

      // Kiểm tra quyền
      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xem quiz của bài học này',
            ],
          ];
        }
      }

      $quizzes = $this->quizRepository->findByLesson($lessonId);
      $quizzes->load('questions.options');

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $quizzes,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get quizzes by lesson failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy chi tiết quiz
   */
  public function getQuizDetail(int $quizId, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->getQuizWithQuestions($quizId);

      // Kiểm tra quyền
      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xem quiz này',
            ],
          ];
        }
      }

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get quiz detail failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy chi tiết quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật thông tin quiz
   */
  public function updateQuiz(int $quizId, array $data, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền sửa quiz này',
            ],
          ];
        }
      }

      $this->quizRepository->update($quizId, $data);
      $quiz = $this->quizRepository->getQuizWithQuestions($quizId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Cập nhật quiz thành công',
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Update quiz failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Xóa quiz
   */
  public function deleteQuiz(int $quizId, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xóa quiz này',
            ],
          ];
        }
      }

      $this->quizRepository->delete($quizId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xóa quiz thành công',
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Delete quiz failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xóa quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật câu hỏi và đáp án
   */
  public function updateQuestion(int $quizId, int $questionId, array $data, int $teacherId): array
  {
    DB::beginTransaction();
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền sửa câu hỏi này',
            ],
          ];
        }
      }

      $question = QuizQuestion::where('quiz_id', $quizId)
        ->where('id', $questionId)
        ->firstOrFail();

      // Update question
      $questionData = array_filter([
        'content' => $data['content'] ?? null,
        'question_type' => $data['question_type'] ?? null,
        'explanation' => $data['explanation'] ?? null,
        'points' => $data['points'] ?? null,
        'order' => $data['order'] ?? null,
      ], fn($v) => $v !== null);

      $question->update($questionData);

      // Update options nếu có
      if (isset($data['options'])) {
        // Xóa options cũ
        $question->options()->forceDelete();

        // Tạo options mới
        foreach ($data['options'] as $index => $optionData) {
          QuizOption::create([
            'question_id' => $question->id,
            'option_text' => $optionData['option_text'],
            'is_correct' => $optionData['is_correct'] ?? false,
            'order' => $optionData['order'] ?? ($index + 1),
            'explanation' => $optionData['explanation'] ?? null,
          ]);
        }
      }

      DB::commit();

      $question->load('options');

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Cập nhật câu hỏi thành công',
          'data' => $question,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Update question failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật câu hỏi: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Thêm câu hỏi mới vào quiz
   */
  public function addQuestion(int $quizId, array $data, int $teacherId): array
  {
    DB::beginTransaction();
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền thêm câu hỏi',
            ],
          ];
        }
      }

      // Tính order tiếp theo
      $maxOrder = $quiz->questions()->max('order') ?? 0;

      $question = QuizQuestion::create([
        'quiz_id' => $quizId,
        'question_type' => $data['question_type'] ?? 'multiple_choice',
        'content' => $data['content'],
        'explanation' => $data['explanation'] ?? null,
        'order' => $data['order'] ?? ($maxOrder + 1),
        'points' => $data['points'] ?? 10,
      ]);

      // Tạo options nếu có
      if (isset($data['options'])) {
        foreach ($data['options'] as $index => $optionData) {
          QuizOption::create([
            'question_id' => $question->id,
            'option_text' => $optionData['option_text'],
            'is_correct' => $optionData['is_correct'] ?? false,
            'order' => $optionData['order'] ?? ($index + 1),
            'explanation' => $optionData['explanation'] ?? null,
          ]);
        }
      }

      DB::commit();

      $question->load('options');

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Thêm câu hỏi thành công',
          'data' => $question,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Add question failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi thêm câu hỏi: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Xóa câu hỏi
   */
  public function deleteQuestion(int $quizId, int $questionId, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xóa câu hỏi',
            ],
          ];
        }
      }

      $question = QuizQuestion::where('quiz_id', $quizId)
        ->where('id', $questionId)
        ->firstOrFail();

      // Xóa options trước
      $question->options()->forceDelete();
      $question->forceDelete();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xóa câu hỏi thành công',
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Delete question failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xóa câu hỏi: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Publish quiz
   */
  public function publishQuiz(int $quizId, array $data, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền publish quiz này',
            ],
          ];
        }
      }

      // Kiểm tra quiz có câu hỏi không
      if ($quiz->questions()->count() === 0) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => 'Quiz phải có ít nhất 1 câu hỏi trước khi publish',
          ],
        ];
      }

      $updateData = [
        'status' => 'published',
      ];

      if (!empty($data['start_time'])) {
        $updateData['start_time'] = $data['start_time'];
      }
      if (!empty($data['end_time'])) {
        $updateData['end_time'] = $data['end_time'];
      }

      $this->quizRepository->update($quizId, $updateData);
      $quiz = $this->quizRepository->getQuizWithQuestions($quizId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã publish quiz thành công',
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Publish quiz failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi publish quiz: ' . $e->getMessage(),
        ],
      ];
    }
  }
}
