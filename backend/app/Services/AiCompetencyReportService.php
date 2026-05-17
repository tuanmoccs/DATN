<?php

namespace App\Services;

use App\Models\AiCompetencyReport;
use App\Models\AssignmentSubmission;
use App\Models\Classz;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AiCompetencyReportService
{
  public function __construct(
    private readonly AiServiceClient $aiServiceClient,
  ) {}

  public function listReports(array $filters, int $teacherId): array
  {
    try {
      $classId = $filters['class_id'] ?? null;
      if ($classId && !$this->teacherOwnsClass((int) $classId, $teacherId)) {
        return $this->forbidden();
      }

      $query = AiCompetencyReport::with(['student:id,name,email', 'class:id,name,code', 'lesson:id,title', 'generatedBy:id,name'])
        ->whereHas('class', fn($q) => $q->where('teacher_id', $teacherId));

      if ($classId) {
        $query->where('class_id', $classId);
      }

      if (!empty($filters['student_id'])) {
        $query->where('student_id', $filters['student_id']);
      }

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $query->orderByDesc('generated_at')->get(),
        ],
      ];
    } catch (\Exception $e) {
      Log::error('List AI competency reports failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi lấy danh sách báo cáo năng lực');
    }
  }

  public function show(int $reportId, int $teacherId): array
  {
    try {
      $report = AiCompetencyReport::with(['student:id,name,email', 'class:id,name,code', 'lesson:id,title', 'generatedBy:id,name'])
        ->findOrFail($reportId);

      if (!$this->teacherOwnsClass((int) $report->class_id, $teacherId)) {
        return $this->forbidden();
      }

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $report],
      ];
    } catch (\Exception $e) {
      Log::error('Show AI competency report failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi lấy báo cáo năng lực');
    }
  }

  public function generate(array $data, int $teacherId): array
  {
    try {
      $classId = (int) $data['class_id'];
      $studentId = (int) $data['student_id'];
      $reportType = $data['report_type'] ?? 'class';
      $lessonId = $data['lesson_id'] ?? null;

      $class = Classz::find($classId);
      if (!$class || $class->teacher_id !== $teacherId) {
        return $this->forbidden();
      }

      $student = User::where('id', $studentId)->where('role', 'student')->first();
      if (!$student) {
        return [
          'status' => 404,
          'data' => ['success' => false, 'message' => 'Không tìm thấy học sinh'],
        ];
      }

      if (!$this->studentIsActiveInClass($studentId, $classId)) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Học sinh không thuộc lớp này'],
        ];
      }

      if ($lessonId && !Lesson::where('id', $lessonId)->where('class_id', $classId)->exists()) {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Bài học không thuộc lớp đã chọn'],
        ];
      }

      $evidence = $this->buildEvidence($studentId, $classId, $lessonId);
      if ($evidence['total_quizzes_taken'] === 0 && $evidence['total_assignments_completed'] === 0) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => 'Chưa có kết quả quiz hoặc bài tập để tạo báo cáo',
          ],
        ];
      }

      $aiResult = $this->aiServiceClient->generateCompetencyReport([
        'student' => [
          'id' => $student->id,
          'name' => $student->name,
          'email' => $student->email,
        ],
        'class' => [
          'id' => $class->id,
          'name' => $class->name,
          'code' => $class->code,
        ],
        'report_type' => $reportType,
        'average_score' => $evidence['average_score'],
        'quiz_results' => $evidence['quiz_results'],
        'assignment_results' => $evidence['assignment_results'],
      ]);

      if (!$aiResult || !($aiResult['success'] ?? false)) {
        return [
          'status' => 502,
          'data' => [
            'success' => false,
            'message' => 'AI service chưa tạo được báo cáo năng lực. Vui lòng kiểm tra ai-service và thử lại.',
          ],
        ];
      }

      $report = AiCompetencyReport::create([
        'student_id' => $studentId,
        'class_id' => $classId,
        'report_type' => $reportType,
        'lesson_id' => $lessonId,
        'average_score' => $evidence['average_score'],
        'total_quizzes_taken' => $evidence['total_quizzes_taken'],
        'total_assignments_completed' => $evidence['total_assignments_completed'],
        'strengths' => $aiResult['strengths'] ?? [],
        'weaknesses' => $aiResult['weaknesses'] ?? [],
        'recommendations' => $aiResult['recommendations'] ?? [],
        'overall_summary' => $aiResult['overall_summary'] ?? '',
        'ai_prompt_used' => null,
        'ai_model_used' => $aiResult['model_used'] ?? null,
        'generated_by' => $teacherId,
        'generated_at' => now(),
      ]);

      $report->load(['student:id,name,email', 'class:id,name,code', 'lesson:id,title', 'generatedBy:id,name']);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'AI đã tạo báo cáo năng lực. Giáo viên nên xem lại và có thể chỉnh sửa trước khi sử dụng.',
          'data' => $report,
          'evidence' => $evidence,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Generate AI competency report failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi tạo báo cáo năng lực: ' . $e->getMessage());
    }
  }

  public function update(int $reportId, array $data, int $teacherId): array
  {
    try {
      $report = AiCompetencyReport::findOrFail($reportId);
      if (!$this->teacherOwnsClass((int) $report->class_id, $teacherId)) {
        return $this->forbidden();
      }

      $report->update(array_filter([
        'strengths' => $data['strengths'] ?? null,
        'weaknesses' => $data['weaknesses'] ?? null,
        'recommendations' => $data['recommendations'] ?? null,
        'overall_summary' => $data['overall_summary'] ?? null,
      ], fn($value) => $value !== null));

      $report->load(['student:id,name,email', 'class:id,name,code', 'lesson:id,title', 'generatedBy:id,name']);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Đã lưu chỉnh sửa báo cáo',
          'data' => $report,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Update AI competency report failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi cập nhật báo cáo năng lực');
    }
  }

  private function buildEvidence(int $studentId, int $classId, ?int $lessonId = null): array
  {
    $quizQuery = QuizAttempt::with(['quiz.lesson'])
      ->where('student_id', $studentId)
      ->whereIn('status', ['submitted', 'graded'])
      ->whereHas('quiz.lesson', function ($q) use ($classId, $lessonId) {
        $q->where('class_id', $classId);
        if ($lessonId) {
          $q->where('id', $lessonId);
        }
      });

    $quizAttempts = $quizQuery->orderByDesc('submitted_at')->get();

    $assignmentQuery = AssignmentSubmission::with(['assignment', 'grading'])
      ->where('student_id', $studentId)
      ->whereHas('assignment', fn($q) => $q->where('class_id', $classId))
      ->whereHas('grading', fn($q) => $q->whereNotNull('percentage')->orWhereNotNull('ai_suggested_score'));

    $assignmentSubmissions = $assignmentQuery->orderByDesc('submitted_at')->get();

    $quizResults = $quizAttempts->map(fn($attempt) => [
      'lesson_title' => $attempt->quiz?->lesson?->title,
      'quiz_title' => $attempt->quiz?->title,
      'attempt_number' => $attempt->attempt_number,
      'score' => (float) $attempt->score,
      'percentage' => (float) $attempt->percentage,
      'submitted_at' => optional($attempt->submitted_at)->toDateTimeString(),
    ])->values()->toArray();

    $assignmentResults = $assignmentSubmissions->map(function ($submission) {
      $grading = $submission->grading;
      $maxScore = (int) ($grading?->max_score ?? $submission->assignment?->max_score ?? 0);
      $score = $grading?->score ?? $grading?->ai_suggested_score;
      $percentage = $grading?->percentage;

      if ($percentage === null && $score !== null && $maxScore > 0) {
        $percentage = round(((float) $score / $maxScore) * 100, 2);
      }

      return [
        'assignment_title' => $submission->assignment?->title,
        'score' => $score !== null ? (float) $score : null,
        'score_source' => $grading?->score !== null ? 'teacher_final' : 'ai_suggested',
        'max_score' => $maxScore,
        'percentage' => $percentage !== null ? (float) $percentage : null,
        'is_late' => (bool) $submission->is_late,
        'teacher_feedback' => $grading?->feedback,
        'ai_feedback' => $grading?->ai_feedback,
        'submitted_at' => optional($submission->submitted_at)->toDateTimeString(),
      ];
    })->values()->toArray();

    $percentages = collect($quizResults)->pluck('percentage')
      ->merge(collect($assignmentResults)->pluck('percentage'))
      ->filter(fn($value) => $value !== null);

    return [
      'average_score' => $percentages->isNotEmpty() ? round($percentages->avg(), 2) : null,
      'total_quizzes_taken' => count($quizResults),
      'total_assignments_completed' => count($assignmentResults),
      'quiz_results' => $quizResults,
      'assignment_results' => $assignmentResults,
    ];
  }

  private function teacherOwnsClass(int $classId, int $teacherId): bool
  {
    return Classz::where('id', $classId)->where('teacher_id', $teacherId)->exists();
  }

  private function studentIsActiveInClass(int $studentId, int $classId): bool
  {
    return Enrollment::where('user_id', $studentId)
      ->where('class_id', $classId)
      ->where('status', 'active')
      ->exists();
  }

  private function forbidden(): array
  {
    return [
      'status' => 403,
      'data' => ['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này'],
    ];
  }

  private function serverError(string $message): array
  {
    return [
      'status' => 500,
      'data' => ['success' => false, 'message' => $message],
    ];
  }
}
