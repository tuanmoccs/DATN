<?php

namespace App\Services;

use App\Repositories\Contracts\LessonRepositoryInterface;
use App\Repositories\Contracts\PresentationRepositoryInterface;
use App\Repositories\Contracts\QuizRepositoryInterface;
use App\Models\LessonContent;
use App\Models\PresentationSlide;
use App\Models\QuizQuestion;
use App\Models\QuizOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LessonService
{
  public function __construct(
    private readonly LessonRepositoryInterface $lessonRepository,
    private readonly PresentationRepositoryInterface $presentationRepository,
    private readonly QuizRepositoryInterface $quizRepository,
    private readonly OpenAIService $openAIService,
  ) {}

  /**
   * Lấy danh sách bài học của lớp
   */
  public function getLessonsByClass(int $classId, int $teacherId): array
  {
    try {
      $lessons = $this->lessonRepository->findByClass($classId);

      // Kiểm tra quyền sở hữu lớp
      if ($lessons->isNotEmpty()) {
        $class = $lessons->first()->class;
        if ($class && $class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xem bài học của lớp này',
            ],
          ];
        }
      }

      $lessons->load(['content', 'presentation.slides', 'quizzes']);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $lessons,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get lessons by class failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách bài học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Lấy chi tiết bài học
   */
  public function getLessonDetail(int $lessonId, int $teacherId): array
  {
    try {
      $lesson = $this->lessonRepository->getLessonWithRelations($lessonId, [
        'content',
        'presentation.slides',
        'quizzes.questions.options',
        'creator',
      ]);

      if ($lesson->created_by !== $teacherId) {
        // Check class ownership
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xem bài học này',
            ],
          ];
        }
      }

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $lesson,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get lesson detail failed', ['error' => $e->getMessage()]);
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
   * Tạo bài học mới + upload content + gọi AI sinh slide & quiz
   */
  public function createLesson(array $data, int $teacherId, $file = null): array
  {
    DB::beginTransaction();
    try {
      // Tính order tiếp theo
      $maxOrder = DB::table('lessons')
        ->where('class_id', $data['class_id'])
        ->whereNull('deleted_at')
        ->max('order');

      // Tạo lesson
      $lesson = $this->lessonRepository->create([
        'class_id' => $data['class_id'],
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'objectives' => $data['objectives'] ?? null,
        'order' => ($maxOrder ?? 0) + 1,
        'status' => $data['status'] ?? 'draft',
        'created_by' => $teacherId,
      ]);

      // Xử lý nội dung bài học
      $contentText = $data['content_text'] ?? null;

      // Upload file nếu có
      if ($file) {
        $filePath = $file->store('lesson_contents', 'local');
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Lưu file content
        LessonContent::create([
          'lesson_id' => $lesson->id,
          'content_type' => $this->getContentType($mimeType),
          'content_text' => null,
          'file_path' => $filePath,
          'file_size' => $fileSize,
          'mime_type' => $mimeType,
          'is_primary' => empty($contentText),
        ]);

        // Extract text từ file để gửi cho AI
        try {
          $fileText = $this->openAIService->extractTextFromFile($filePath, $mimeType);
          if (!empty($contentText)) {
            $contentText = $contentText . "\n\n" . $fileText;
          } else {
            $contentText = $fileText;
          }
        } catch (\Exception $e) {
          Log::warning('File text extraction failed, using text content only', [
            'error' => $e->getMessage(),
          ]);
        }
      }

      // Lưu text content nếu có
      if (!empty($data['content_text'])) {
        LessonContent::create([
          'lesson_id' => $lesson->id,
          'content_type' => 'text',
          'content_text' => $data['content_text'],
          'file_path' => null,
          'file_size' => null,
          'mime_type' => null,
          'is_primary' => true,
        ]);
      }

      DB::commit();

      // Sau khi commit, gọi AI sinh slide & quiz (ngoài transaction)
      $aiResult = null;
      if (!empty($contentText)) {
        $aiResult = $this->generateAIContent($lesson, $contentText, $data);
      }

      $lesson->load(['content', 'presentation.slides', 'quizzes.questions.options']);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Tạo bài học thành công' . ($aiResult ? '. AI đã sinh slide và câu hỏi.' : ''),
          'data' => $lesson,
          'ai_generation' => $aiResult,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Create lesson failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi tạo bài học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Cập nhật bài học
   */
  public function updateLesson(int $lessonId, array $data, int $teacherId, $file = null): array
  {
    DB::beginTransaction();
    try {
      $lesson = $this->lessonRepository->findOrFail($lessonId);

      // Kiểm tra quyền
      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền sửa bài học này',
            ],
          ];
        }
      }

      // Update lesson info
      $updateData = array_filter([
        'title' => $data['title'] ?? null,
        'description' => $data['description'] ?? null,
        'objectives' => $data['objectives'] ?? null,
        'status' => $data['status'] ?? null,
        'order' => $data['order'] ?? null,
      ], fn($v) => $v !== null);

      if (!empty($updateData)) {
        $this->lessonRepository->update($lessonId, $updateData);
      }

      // Update content text nếu có
      if (isset($data['content_text'])) {
        $primaryContent = $lesson->content()->where('content_type', 'text')->where('is_primary', true)->first();
        if ($primaryContent) {
          $primaryContent->update(['content_text' => $data['content_text']]);
        } else {
          LessonContent::create([
            'lesson_id' => $lesson->id,
            'content_type' => 'text',
            'content_text' => $data['content_text'],
            'is_primary' => true,
          ]);
        }
      }

      // Upload file mới nếu có
      if ($file) {
        $filePath = $file->store('lesson_contents', 'local');
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        LessonContent::create([
          'lesson_id' => $lesson->id,
          'content_type' => $this->getContentType($mimeType),
          'content_text' => null,
          'file_path' => $filePath,
          'file_size' => $fileSize,
          'mime_type' => $mimeType,
          'is_primary' => false,
        ]);
      }

      DB::commit();

      $lesson = $this->lessonRepository->getLessonWithRelations($lessonId, [
        'content',
        'presentation.slides',
        'quizzes.questions.options',
      ]);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Cập nhật bài học thành công',
          'data' => $lesson,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Update lesson failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi cập nhật bài học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Xóa bài học (soft delete)
   */
  public function deleteLesson(int $lessonId, int $teacherId): array
  {
    try {
      $lesson = $this->lessonRepository->findOrFail($lessonId);

      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xóa bài học này',
            ],
          ];
        }
      }

      $this->lessonRepository->delete($lessonId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Xóa bài học thành công',
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Delete lesson failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi xóa bài học: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Gọi AI sinh lại slide cho bài học
   */
  public function regenerateSlides(int $lessonId, int $teacherId, int $slideCount = 10): array
  {
    try {
      $lesson = $this->lessonRepository->getLessonWithRelations($lessonId, ['content', 'presentation.slides']);

      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền thực hiện',
            ],
          ];
        }
      }

      $contentText = $this->gatherLessonContent($lesson);
      if (empty($contentText)) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => 'Bài học chưa có nội dung để sinh slide',
          ],
        ];
      }

      // Xóa slides cũ nếu có
      if ($lesson->presentation) {
        $lesson->presentation->slides()->forceDelete();
      }

      // Sinh slides mới
      $slides = $this->openAIService->generatePresentationSlides(
        $contentText,
        $lesson->title,
        $slideCount
      );

      // Sinh hình ảnh cho slides có image_prompt
      $slideImages = $this->openAIService->generateSlideImages($slides);

      $presentation = $lesson->presentation;
      if (!$presentation) {
        $presentation = $this->presentationRepository->create([
          'lesson_id' => $lesson->id,
          'current_version' => 1,
          'status' => 'draft',
          'generated_by' => 'ai',
          'ai_prompt' => substr($contentText, 0, 1000),
        ]);
      }

      foreach ($slides as $slideData) {
        PresentationSlide::create([
          'presentation_id' => $presentation->id,
          'order' => $slideData['order'],
          'title' => $slideData['title'],
          'content' => $slideData['content'],
          'notes' => $slideData['notes'] ?? null,
          'layout' => $slideData['layout'] ?? 'content',
          'image_url' => $slideImages[$slideData['order']] ?? null,
        ]);
      }

      $presentation->load('slides');

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã sinh lại slide thành công',
          'data' => $presentation,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Regenerate slides failed', ['error' => $e->getMessage()]);

      $isRateLimit = str_contains(strtolower($e->getMessage()), 'rate limit')
        || str_contains($e->getMessage(), '429')
        || str_contains(strtolower($e->getMessage()), 'quota');

      return [
        'status' => $isRateLimit ? 429 : 500,
        'data' => [
          'success' => false,
          'message' => $isRateLimit
            ? 'OpenAI rate limit exceeded. Please wait a moment and try again.'
            : 'Lỗi khi sinh slide: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Gọi AI sinh lại quiz cho bài học
   */
  public function regenerateQuiz(int $lessonId, int $teacherId, int $questionCount = 5): array
  {
    try {
      $lesson = $this->lessonRepository->getLessonWithRelations($lessonId, ['content', 'quizzes']);

      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền thực hiện',
            ],
          ];
        }
      }

      $contentText = $this->gatherLessonContent($lesson);
      if (empty($contentText)) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => 'Bài học chưa có nội dung để sinh câu hỏi',
          ],
        ];
      }

      // Sinh câu hỏi mới
      $questions = $this->openAIService->generateQuizQuestions(
        $contentText,
        $lesson->title,
        $questionCount
      );

      // Tạo quiz mới
      $quiz = $this->quizRepository->create([
        'lesson_id' => $lesson->id,
        'title' => $lesson->title . ' - Quiz',
        'description' => 'Auto-generated quiz for: ' . $lesson->title,
        'quiz_type' => 'online',
        'auto_generated' => true,
        'ai_prompt' => substr($contentText, 0, 1000),
        'time_limit' => $questionCount * 2, // 2 phút mỗi câu
        'shuffle_questions' => true,
        'shuffle_options' => true,
        'show_answers_after' => true,
        'max_attempts' => 3,
        'status' => 'draft',
        'created_by' => $teacherId,
      ]);

      foreach ($questions as $questionData) {
        $question = QuizQuestion::create([
          'quiz_id' => $quiz->id,
          'question_type' => $questionData['question_type'] ?? 'multiple_choice',
          'content' => $questionData['content'],
          'explanation' => $questionData['explanation'] ?? null,
          'order' => $questionData['order'],
          'points' => $questionData['points'] ?? 10,
        ]);

        if (isset($questionData['options'])) {
          foreach ($questionData['options'] as $optionData) {
            QuizOption::create([
              'question_id' => $question->id,
              'option_text' => $optionData['option_text'],
              'is_correct' => $optionData['is_correct'] ?? false,
              'order' => $optionData['order'],
              'explanation' => $optionData['explanation'] ?? null,
            ]);
          }
        }
      }

      $quiz->load('questions.options');

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Đã sinh câu hỏi quiz thành công',
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Regenerate quiz failed', ['error' => $e->getMessage()]);

      $isRateLimit = str_contains(strtolower($e->getMessage()), 'rate limit')
        || str_contains($e->getMessage(), '429')
        || str_contains(strtolower($e->getMessage()), 'quota');

      return [
        'status' => $isRateLimit ? 429 : 500,
        'data' => [
          'success' => false,
          'message' => $isRateLimit
            ? 'OpenAI rate limit exceeded. Please wait a moment and try again.'
            : 'Lỗi khi sinh câu hỏi: ' . $e->getMessage(),
        ],
      ];
    }
  }

  /**
   * Gọi AI sinh slide + quiz cùng lúc
   */
  private function generateAIContent($lesson, string $contentText, array $data): array
  {
    $result = [
      'slides' => false,
      'quiz' => false,
    ];

    $generateSlides = $data['generate_slides'] ?? true;
    $generateQuiz = $data['generate_quiz'] ?? true;
    $slideCount = $data['slide_count'] ?? 10;
    $questionCount = $data['question_count'] ?? 5;

    // Sinh slides
    if ($generateSlides) {
      try {
        sleep(1); // Small delay to avoid rate limit
        $slides = $this->openAIService->generatePresentationSlides(
          $contentText,
          $lesson->title,
          $slideCount
        );

        $presentation = $this->presentationRepository->create([
          'lesson_id' => $lesson->id,
          'current_version' => 1,
          'status' => 'draft',
          'generated_by' => 'ai',
          'ai_prompt' => substr($contentText, 0, 1000),
        ]);

        // Sinh hình ảnh cho slides có image_prompt
        $slideImages = $this->openAIService->generateSlideImages($slides);

        foreach ($slides as $slideData) {
          PresentationSlide::create([
            'presentation_id' => $presentation->id,
            'order' => $slideData['order'],
            'title' => $slideData['title'],
            'content' => $slideData['content'],
            'notes' => $slideData['notes'] ?? null,
            'layout' => $slideData['layout'] ?? 'content',
            'image_url' => $slideImages[$slideData['order']] ?? null,
          ]);
        }

        $result['slides'] = true;
        $result['slides_count'] = count($slides);
      } catch (\Exception $e) {
        Log::error('AI slide generation failed during lesson creation', [
          'lesson_id' => $lesson->id,
          'error' => $e->getMessage(),
        ]);
        $result['slides_error'] = $e->getMessage();
      }
    }

    // Sinh quiz questions (delay để tránh rate limit sau khi sinh slides)
    if ($generateQuiz) {
      try {
        if ($generateSlides) {
          sleep(3); // Delay giữa 2 lần gọi AI
        }
        $questions = $this->openAIService->generateQuizQuestions(
          $contentText,
          $lesson->title,
          $questionCount
        );

        $quiz = $this->quizRepository->create([
          'lesson_id' => $lesson->id,
          'title' => $lesson->title . ' - Quiz',
          'description' => 'Auto-generated quiz for: ' . $lesson->title,
          'quiz_type' => 'online',
          'auto_generated' => true,
          'ai_prompt' => substr($contentText, 0, 1000),
          'time_limit' => $questionCount * 2,
          'shuffle_questions' => true,
          'shuffle_options' => true,
          'show_answers_after' => true,
          'max_attempts' => 3,
          'status' => 'draft',
          'created_by' => $lesson->created_by,
        ]);

        foreach ($questions as $questionData) {
          $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_type' => $questionData['question_type'] ?? 'multiple_choice',
            'content' => $questionData['content'],
            'explanation' => $questionData['explanation'] ?? null,
            'order' => $questionData['order'],
            'points' => $questionData['points'] ?? 10,
          ]);

          if (isset($questionData['options'])) {
            foreach ($questionData['options'] as $optionData) {
              QuizOption::create([
                'question_id' => $question->id,
                'option_text' => $optionData['option_text'],
                'is_correct' => $optionData['is_correct'] ?? false,
                'order' => $optionData['order'],
                'explanation' => $optionData['explanation'] ?? null,
              ]);
            }
          }
        }

        $result['quiz'] = true;
        $result['questions_count'] = count($questions);
      } catch (\Exception $e) {
        Log::error('AI quiz generation failed during lesson creation', [
          'lesson_id' => $lesson->id,
          'error' => $e->getMessage(),
        ]);
        $result['quiz_error'] = $e->getMessage();
      }
    }

    return $result;
  }

  /**
   * Gom nội dung bài học từ tất cả sources
   */
  private function gatherLessonContent($lesson): string
  {
    $contentParts = [];

    foreach ($lesson->content as $content) {
      if ($content->content_type === 'text' && !empty($content->content_text)) {
        $contentParts[] = $content->content_text;
      } elseif (!empty($content->file_path)) {
        try {
          $fileText = $this->openAIService->extractTextFromFile(
            $content->file_path,
            $content->mime_type
          );
          $contentParts[] = $fileText;
        } catch (\Exception $e) {
          Log::warning('Cannot extract text from content file', [
            'content_id' => $content->id,
            'error' => $e->getMessage(),
          ]);
        }
      }
    }

    return implode("\n\n", $contentParts);
  }

  /**
   * Map MIME type to content_type
   */
  private function getContentType(string $mimeType): string
  {
    return match (true) {
      str_contains($mimeType, 'pdf') => 'pdf',
      str_contains($mimeType, 'word') || str_contains($mimeType, 'document') => 'document',
      str_contains($mimeType, 'presentation') || str_contains($mimeType, 'powerpoint') => 'presentation',
      str_contains($mimeType, 'text') => 'text',
      str_contains($mimeType, 'image') => 'image',
      default => 'other',
    };
  }
}
