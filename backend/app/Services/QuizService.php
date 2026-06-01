<?php

namespace App\Services;

use App\Models\QuizQuestion;
use App\Models\QuizOption;
use App\Models\QuizAttempt;
use App\Repositories\Contracts\QuizRepositoryInterface;
use App\Repositories\Contracts\LessonRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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
   * Tao quiz thu cong theo bai hoc
   */
  public function createQuiz(int $lessonId, array $data, int $teacherId): array
  {
    try {
      $lesson = $this->lessonRepository->findOrFail($lessonId);

      if ($lesson->created_by !== $teacherId) {
        if ($lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Ban khong co quyen tao quiz cho bai hoc nay',
            ],
          ];
        }
      }

      $quiz = $this->quizRepository->create([
        'lesson_id' => $lessonId,
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'quiz_type' => $data['quiz_type'] ?? 'online',
        'auto_generated' => false,
        'time_limit' => $data['time_limit'] ?? null,
        'shuffle_questions' => $data['shuffle_questions'] ?? false,
        'shuffle_options' => $data['shuffle_options'] ?? false,
        'show_answers_after' => $data['show_answers_after'] ?? false,
        'max_attempts' => $data['max_attempts'] ?? 1,
        'status' => $data['status'] ?? 'draft',
        'start_time' => $data['start_time'] ?? null,
        'end_time' => $data['end_time'] ?? null,
        'created_by' => $teacherId,
      ]);

      $quiz->load('questions.options');

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Tao quiz thanh cong',
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Create quiz failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Loi khi tao quiz: ' . $e->getMessage(),
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
          DB::rollBack();
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
          DB::rollBack();
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
   * Import cau hoi tu Excel/CSV. Dong dau tien la header.
   */
  public function importQuestionsFromExcel(int $quizId, UploadedFile $file, int $teacherId): array
  {
    DB::beginTransaction();
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          DB::rollBack();
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'You do not have permission to import questions',
            ],
          ];
        }
      }

      $sheets = Excel::toArray(null, $file);
      $rows = $sheets[0] ?? [];

      if (count($rows) < 2) {
        DB::rollBack();
        return [
          'status' => 422,
          'data' => [
            'success' => false,
            'message' => 'The import file must include a header row and at least one question row',
          ],
        ];
      }

      $headers = $this->normalizeHeaders(array_shift($rows));
      $parsedQuestions = [];
      $errors = [];

      foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;
        if ($this->isEmptyRow($row)) {
          break;
        }

        $parsed = $this->parseImportRow($headers, $row, $rowNumber);
        if (!empty($parsed['errors'])) {
          if ($parsed['stop_import']) {
            break;
          }

          $errors = array_merge($errors, $parsed['errors']);
          continue;
        }

        $parsedQuestions[] = $parsed['question'];
      }

      if (!empty($errors)) {
        DB::rollBack();
        return [
          'status' => 422,
          'data' => [
            'success' => false,
            'message' => 'The import file contains invalid data',
            'errors' => $errors,
          ],
        ];
      }

      if (empty($parsedQuestions)) {
        DB::rollBack();
        return [
          'status' => 422,
          'data' => [
            'success' => false,
            'message' => 'No valid questions were found in the import file',
          ],
        ];
      }

      $maxOrder = $quiz->questions()->max('order') ?? 0;
      $imported = 0;

      foreach ($parsedQuestions as $questionData) {
        $question = QuizQuestion::create([
          'quiz_id' => $quizId,
          'question_type' => 'multiple_choice',
          'content' => $questionData['content'],
          'explanation' => $questionData['explanation'],
          'order' => ++$maxOrder,
          'points' => $questionData['points'],
        ]);

        foreach ($questionData['options'] as $optionIndex => $optionData) {
          QuizOption::create([
            'question_id' => $question->id,
            'option_text' => $optionData['option_text'],
            'is_correct' => $optionData['is_correct'],
            'order' => $optionIndex + 1,
          ]);
        }

        $imported++;
      }

      DB::commit();

      $quiz = $this->quizRepository->getQuizWithQuestions($quizId);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => "Successfully imported {$imported} question(s)",
          'imported' => $imported,
          'data' => $quiz,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Import quiz questions failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Failed to import questions: ' . $e->getMessage(),
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

  /**
   * Lấy danh sách học sinh đã làm quiz và điểm số
   */
  public function getQuizAttempts(int $quizId, int $teacherId): array
  {
    try {
      $quiz = $this->quizRepository->findOrFail($quizId);

      // Kiểm tra quyền
      if ($quiz->created_by !== $teacherId) {
        $lesson = $quiz->lesson;
        if ($lesson && $lesson->class && $lesson->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => [
              'success' => false,
              'message' => 'Bạn không có quyền xem điểm số của quiz này',
            ],
          ];
        }
      }

      $attempts = QuizAttempt::with('student:id,name,email')
        ->where('quiz_id', $quizId)
        ->orderByDesc('submitted_at')
        ->get();

      $totalPoints = $quiz->getTotalPoints();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'total_points' => $totalPoints,
          'data' => $attempts,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get quiz attempts failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => [
          'success' => false,
          'message' => 'Lỗi khi lấy danh sách điểm số: ' . $e->getMessage(),
        ],
      ];
    }
  }

  private function normalizeHeaders(array $headerRow): array
  {
    $headers = [];
    foreach ($headerRow as $index => $header) {
      $key = strtolower(trim((string) $header));
      $key = str_replace([' ', '-', '.', '/'], '_', $key);
      $headers[$key] = $index;
    }
    return $headers;
  }

  private function isEmptyRow(array $row): bool
  {
    foreach ($row as $value) {
      if (trim((string) $value) !== '') {
        return false;
      }
    }
    return true;
  }

  private function parseImportRow(array $headers, array $row, int $rowNumber): array
  {
    $content = $this->getImportValue($headers, $row, ['question', 'content', 'cau_hoi', 'noi_dung'], 0);
    $explanation = $this->getImportValue($headers, $row, ['explanation', 'giai_thich'], 6);
    $pointsValue = $this->getImportValue($headers, $row, ['points', 'point', 'diem'], 7);
    $correctAnswer = strtoupper(trim($this->getImportValue($headers, $row, ['correct_answer', 'correct', 'answer', 'dap_an_dung'], 5)));

    $optionValues = [
      $this->getImportValue($headers, $row, ['option_a', 'a', 'answer_a', 'dap_an_a'], 1),
      $this->getImportValue($headers, $row, ['option_b', 'b', 'answer_b', 'dap_an_b'], 2),
      $this->getImportValue($headers, $row, ['option_c', 'c', 'answer_c', 'dap_an_c'], 3),
      $this->getImportValue($headers, $row, ['option_d', 'd', 'answer_d', 'dap_an_d'], 4),
      $this->getImportValue($headers, $row, ['option_e', 'e', 'answer_e', 'dap_an_e'], null),
      $this->getImportValue($headers, $row, ['option_f', 'f', 'answer_f', 'dap_an_f'], null),
    ];

    $errors = [];

    $stopImport = false;

    if ($content === '') {
      $errors[] = "Row {$rowNumber}: missing question content";
      $stopImport = true;
    }

    $options = [];
    foreach ($optionValues as $optionIndex => $optionText) {
      $optionText = trim((string) $optionText);
      if ($optionText !== '') {
        $options[] = [
          'option_text' => $optionText,
          'is_correct' => false,
          'label' => chr(65 + $optionIndex),
          'position' => $optionIndex + 1,
        ];
      }
    }

    if (count($options) < 2) {
      $errors[] = "Row {$rowNumber}: at least 2 options are required";
      $stopImport = true;
    }

    if ($correctAnswer === '') {
      $errors[] = "Row {$rowNumber}: missing correct answer";
      $stopImport = true;
    }

    $correctMatched = false;
    foreach ($options as &$option) {
      $isCorrect = $correctAnswer === $option['label']
        || $correctAnswer === (string) $option['position']
        || mb_strtolower($correctAnswer) === mb_strtolower($option['option_text']);

      if ($isCorrect) {
        $option['is_correct'] = true;
        $correctMatched = true;
      }

      unset($option['label'], $option['position']);
    }
    unset($option);

    if ($correctAnswer !== '' && !$correctMatched) {
      $errors[] = "Row {$rowNumber}: correct answer does not match any option";
    }

    $points = $pointsValue === '' ? 10 : (int) $pointsValue;
    if ($points < 1 || $points > 100) {
      $errors[] = "Row {$rowNumber}: points must be between 1 and 100";
    }

    return [
      'errors' => $errors,
      'stop_import' => $stopImport,
      'question' => [
        'content' => $content,
        'explanation' => $explanation === '' ? null : $explanation,
        'points' => $points,
        'options' => $options,
      ],
    ];
  }

  private function getImportValue(array $headers, array $row, array $keys, ?int $fallbackIndex): string
  {
    foreach ($keys as $key) {
      if (array_key_exists($key, $headers)) {
        return trim((string) ($row[$headers[$key]] ?? ''));
      }
    }

    if ($fallbackIndex !== null) {
      return trim((string) ($row[$fallbackIndex] ?? ''));
    }

    return '';
  }
}
