<?php

namespace App\Services;

use App\Jobs\GenerateClassCompetencyReportsJob;
use App\Models\AiCompetencyReportBatch;
use App\Models\AiCompetencyReport;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classz;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
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

  public function queueGenerateForClass(int $classId, int $teacherId): array
  {
    try {
      $class = Classz::with([
        'enrollment' => fn($q) => $q->where('status', 'active')->with('user:id,role')->orderBy('id'),
      ])->find($classId);

      if (!$class || $class->teacher_id !== $teacherId) {
        return $this->forbidden();
      }

      $students = $class->enrollment
        ->pluck('user')
        ->filter(fn($student) => $student && $student->role === 'student')
        ->values();

      if ($students->isEmpty()) {
        return [
          'status' => 400,
          'data' => [
            'success' => false,
            'message' => 'Lớp chưa có học sinh active để tạo báo cáo',
          ],
        ];
      }

      $batch = AiCompetencyReportBatch::create([
        'class_id' => $classId,
        'teacher_id' => $teacherId,
        'status' => 'queued',
        'total_students' => $students->count(),
        'results' => [],
      ]);

      GenerateClassCompetencyReportsJob::dispatch($batch->id);

      return [
        'status' => 202,
        'data' => [
          'success' => true,
          'message' => 'Đã đưa yêu cầu tạo report cả lớp vào hàng đợi',
          'data' => $batch->fresh(),
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Queue AI competency reports for class failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi đưa yêu cầu tạo báo cáo cả lớp vào hàng đợi: ' . $e->getMessage());
    }
  }

  public function getGenerateBatchStatus(int $batchId, int $teacherId): array
  {
    try {
      $batch = AiCompetencyReportBatch::with('class:id,name,code')
        ->where('teacher_id', $teacherId)
        ->findOrFail($batchId);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => $batch,
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get AI competency report batch status failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi lấy trạng thái tạo báo cáo cả lớp');
    }
  }

  public function getClassRiskAlerts(int $classId, int $teacherId): array
  {
    try {
      $class = Classz::with([
        'enrollment' => fn($q) => $q->where('status', 'active')->with('user:id,name,email')->orderBy('id'),
      ])->find($classId);

      if (!$class || $class->teacher_id !== $teacherId) {
        return $this->forbidden();
      }

      $latestReports = AiCompetencyReport::where('class_id', $classId)
        ->orderByDesc('generated_at')
        ->get()
        ->groupBy('student_id')
        ->map(fn($reports) => $reports->first());

      $students = $class->enrollment
        ->pluck('user')
        ->filter()
        ->values();

      $alerts = $students->map(function ($student) use ($classId, $latestReports) {
        $report = $latestReports->get($student->id);
        $lowScoreAlert = $this->buildLowScoreRisk($report);
        $missingWorkAlert = $this->buildMissingWorkRisk($student->id, $classId);
        $declineAlert = $this->buildDecliningProgressRisk($student->id, $classId);

        $studentAlerts = collect([$lowScoreAlert, $missingWorkAlert, $declineAlert])
          ->filter()
          ->values();

        return [
          'student_id' => $student->id,
          'student_name' => $student->name,
          'student_email' => $student->email,
          'average_score' => $report?->average_score !== null ? (float) $report->average_score : null,
          'risk_level' => $this->resolveRiskLevel($studentAlerts->pluck('severity')->all()),
          'alerts' => $studentAlerts,
        ];
      })->filter(fn($item) => count($item['alerts']) > 0)
        ->sortBy(fn($item) => ['high' => 0, 'medium' => 1, 'low' => 2][$item['risk_level']] ?? 3)
        ->values();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => [
            'class_id' => $classId,
            'total_students' => $students->count(),
            'students_at_risk' => $alerts->count(),
            'high_risk' => $alerts->where('risk_level', 'high')->count(),
            'medium_risk' => $alerts->where('risk_level', 'medium')->count(),
            'low_risk' => $alerts->where('risk_level', 'low')->count(),
            'alerts' => $alerts,
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Get class risk alerts failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi lấy cảnh báo rủi ro học sinh');
    }
  }

  public function processClassGenerateBatch(int $batchId): void
  {
    $batch = AiCompetencyReportBatch::findOrFail($batchId);
    if (!in_array($batch->status, ['queued', 'processing'], true)) {
      return;
    }

    $batch->update([
      'status' => 'processing',
      'started_at' => $batch->started_at ?? now(),
      'error_message' => null,
    ]);

    $class = Classz::with([
      'enrollment' => fn($q) => $q->where('status', 'active')->with('user:id,name,email,role')->orderBy('id'),
    ])->findOrFail($batch->class_id);

    $students = $class->enrollment
      ->pluck('user')
      ->filter(fn($student) => $student && $student->role === 'student')
      ->values();

    $results = $batch->results ?? [];
    $generated = 0;
    $skipped = 0;
    $failed = 0;
    $processed = 0;

    foreach ($students as $student) {
      $result = $this->generate([
        'class_id' => $batch->class_id,
        'student_id' => $student->id,
        'report_type' => 'class',
      ], $batch->teacher_id);

      $success = (bool) ($result['data']['success'] ?? false);
      $message = $result['data']['message'] ?? null;
      $report = $result['data']['data'] ?? null;
      $status = $success ? 'generated' : ($result['status'] === 400 ? 'skipped' : 'failed');

      if ($status === 'generated') {
        $generated++;
      } elseif ($status === 'skipped') {
        $skipped++;
      } else {
        $failed++;
      }

      $processed++;
      $results[] = [
        'student_id' => $student->id,
        'student_name' => $student->name,
        'status' => $status,
        'message' => $message,
        'report_id' => $report?->id,
      ];

      $batch->update([
        'processed' => $processed,
        'generated' => $generated,
        'skipped' => $skipped,
        'failed' => $failed,
        'results' => $results,
      ]);
    }

    $batch->update([
      'status' => 'completed',
      'processed' => $processed,
      'generated' => $generated,
      'skipped' => $skipped,
      'failed' => $failed,
      'finished_at' => now(),
    ]);
  }

  public function markClassGenerateBatchFailed(int $batchId, string $message): void
  {
    AiCompetencyReportBatch::where('id', $batchId)->update([
      'status' => 'failed',
      'error_message' => $message,
      'finished_at' => now(),
    ]);
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

  public function getClassReportExportData(int $classId, int $teacherId): array
  {
    try {
      $class = Classz::with([
        'teacher:id,name,email',
        'enrollment' => fn($q) => $q->where('status', 'active')->with('user:id,name,email')->orderBy('id'),
      ])->find($classId);

      if (!$class || $class->teacher_id !== $teacherId) {
        return $this->forbidden();
      }

      $reports = AiCompetencyReport::with(['student:id,name,email', 'lesson:id,title', 'generatedBy:id,name'])
        ->where('class_id', $classId)
        ->orderByDesc('generated_at')
        ->get()
        ->groupBy('student_id')
        ->map(fn($studentReports) => $studentReports->first());

      $students = $class->enrollment->map(function ($enrollment) use ($reports) {
        $student = $enrollment->user;

        return [
          'student' => $student,
          'report' => $student ? $reports->get($student->id) : null,
        ];
      })->values();

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'data' => [
            'class' => $class,
            'students' => $students,
            'generated_at' => now(),
          ],
        ],
      ];
    } catch (\Exception $e) {
      Log::error('Export AI competency class report data failed', ['error' => $e->getMessage()]);
      return $this->serverError('Lỗi khi chuẩn bị dữ liệu xuất PDF báo cáo lớp');
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

  private function buildLowScoreRisk(?AiCompetencyReport $report): ?array
  {
    if (!$report || $report->average_score === null) {
      return null;
    }

    $averageScore = (float) $report->average_score;
    if ($averageScore >= 60) {
      return null;
    }

    return [
      'type' => 'low_score',
      'severity' => $averageScore < 50 ? 'high' : 'medium',
      'title' => 'Low average score',
      'message' => "Latest report average is {$averageScore}%.",
      'metrics' => [
        'average_score' => $averageScore,
        'threshold' => 60,
      ],
    ];
  }

  private function buildMissingWorkRisk(int $studentId, int $classId): ?array
  {
    $overdueAssignmentIds = Assignment::where('class_id', $classId)
      ->where('status', 'published')
      ->whereNotNull('due_date')
      ->where('due_date', '<', now())
      ->pluck('id');

    $submittedAssignmentIds = AssignmentSubmission::where('student_id', $studentId)
      ->whereIn('assignment_id', $overdueAssignmentIds)
      ->pluck('assignment_id')
      ->unique();

    $missingAssignments = max(0, $overdueAssignmentIds->count() - $submittedAssignmentIds->count());

    $endedQuizIds = Quiz::where('status', 'published')
      ->whereNotNull('end_time')
      ->where('end_time', '<', now())
      ->whereHas('lesson', fn($q) => $q->where('class_id', $classId))
      ->pluck('id');

    $attemptedQuizIds = QuizAttempt::where('student_id', $studentId)
      ->whereIn('quiz_id', $endedQuizIds)
      ->whereIn('status', ['submitted', 'graded'])
      ->pluck('quiz_id')
      ->unique();

    $missingQuizzes = max(0, $endedQuizIds->count() - $attemptedQuizIds->count());
    $totalMissing = $missingAssignments + $missingQuizzes;

    if ($totalMissing === 0) {
      return null;
    }

    return [
      'type' => 'missing_work',
      'severity' => $totalMissing >= 3 ? 'high' : 'medium',
      'title' => 'Missing overdue work',
      'message' => "Missing {$missingAssignments} assignments and {$missingQuizzes} quizzes.",
      'metrics' => [
        'missing_assignments' => $missingAssignments,
        'missing_quizzes' => $missingQuizzes,
        'total_missing' => $totalMissing,
      ],
    ];
  }

  private function buildDecliningProgressRisk(int $studentId, int $classId): ?array
  {
    $timeline = $this->buildPerformanceTimeline($studentId, $classId);

    if ($timeline->count() < 6) {
      return null;
    }

    $recentAverage = round($timeline->take(3)->avg('percentage'), 2);
    $previousAverage = round($timeline->slice(3, 3)->avg('percentage'), 2);
    $drop = round($previousAverage - $recentAverage, 2);

    if ($drop < 15) {
      return null;
    }

    return [
      'type' => 'declining_progress',
      'severity' => $drop >= 25 ? 'high' : 'medium',
      'title' => 'Declining progress',
      'message' => "Recent average dropped by {$drop} percentage points.",
      'metrics' => [
        'recent_average' => $recentAverage,
        'previous_average' => $previousAverage,
        'drop' => $drop,
      ],
    ];
  }

  private function buildPerformanceTimeline(int $studentId, int $classId)
  {
    $quizItems = QuizAttempt::with('quiz.lesson')
      ->where('student_id', $studentId)
      ->whereIn('status', ['submitted', 'graded'])
      ->whereNotNull('percentage')
      ->whereHas('quiz.lesson', fn($q) => $q->where('class_id', $classId))
      ->get()
      ->map(fn($attempt) => [
        'type' => 'quiz',
        'title' => $attempt->quiz?->title,
        'percentage' => (float) $attempt->percentage,
        'date' => $attempt->submitted_at ?? $attempt->updated_at,
      ]);

    $assignmentItems = AssignmentSubmission::with(['assignment', 'grading'])
      ->where('student_id', $studentId)
      ->whereHas('assignment', fn($q) => $q->where('class_id', $classId))
      ->whereHas('grading', fn($q) => $q->whereNotNull('percentage')->orWhereNotNull('ai_suggested_score'))
      ->get()
      ->map(function ($submission) {
        $grading = $submission->grading;
        $maxScore = (int) ($grading?->max_score ?? $submission->assignment?->max_score ?? 0);
        $percentage = $grading?->percentage;
        $score = $grading?->score ?? $grading?->ai_suggested_score;

        if ($percentage === null && $score !== null && $maxScore > 0) {
          $percentage = round(((float) $score / $maxScore) * 100, 2);
        }

        return [
          'type' => 'assignment',
          'title' => $submission->assignment?->title,
          'percentage' => $percentage !== null ? (float) $percentage : null,
          'date' => $submission->submitted_at ?? $submission->updated_at,
        ];
      })
      ->filter(fn($item) => $item['percentage'] !== null);

    return $quizItems
      ->merge($assignmentItems)
      ->filter(fn($item) => $item['date'] !== null)
      ->sortByDesc('date')
      ->values();
  }

  private function resolveRiskLevel(array $severities): string
  {
    if (in_array('high', $severities, true)) {
      return 'high';
    }

    if (in_array('medium', $severities, true)) {
      return 'medium';
    }

    return 'low';
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
