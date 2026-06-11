<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentFile;
use App\Models\AssignmentSubmission;
use App\Models\Classz;
use App\Models\Grading;
use App\Models\SubmissionAttachment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use App\Repositories\Contracts\AssignmentSubmissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AssignmentService
{
  public function __construct(
    private readonly AssignmentRepositoryInterface $assignmentRepository,
    private readonly AssignmentSubmissionRepositoryInterface $submissionRepository,
    private readonly AiServiceClient $aiServiceClient,
  ) {}

  // ================================================================
  // TEACHER: CRUD Assignments
  // ================================================================

  /**
   * Lấy danh sách bài tập theo lớp
   */
  public function getAssignmentsByClass(int $classId, int $teacherId): array
  {
    try {
      $assignments = $this->assignmentRepository->findByClass($classId);

      if ($assignments->isNotEmpty()) {
        $class = $assignments->first()->class;
        if ($class && $class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền xem bài tập của lớp này'],
          ];
        }
      }

      $assignments->load(['files', 'creator', 'submissions.student']);

      // Thêm thống kê cho mỗi bài tập
      $assignments->each(function ($assignment) {
        $assignment->submission_count = $assignment->submissions->count();
        $assignment->graded_count = $assignment->submissions->filter(fn($s) => $s->status === 'graded')->count();
      });

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $assignments],
      ];
    } catch (\Exception $e) {
      Log::error('Get assignments by class failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi lấy danh sách bài tập: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Lấy chi tiết bài tập
   */
  public function getAssignmentDetail(int $assignmentId, int $teacherId): array
  {
    try {
      $assignment = $this->assignmentRepository->getAssignmentWithRelations($assignmentId, [
        'files',
        'creator',
        'submissions.student',
        'submissions.attachments',
        'submissions.grading',
      ]);

      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền xem bài tập này'],
          ];
        }
      }

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $assignment],
      ];
    } catch (\Exception $e) {
      Log::error('Get assignment detail failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi lấy chi tiết bài tập: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Tạo bài tập mới
   */
  public function createAssignment(array $data, int $teacherId, array $files = []): array
  {
    DB::beginTransaction();
    try {
      $assignment = $this->assignmentRepository->create([
        'class_id' => $data['class_id'],
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'instructions' => $data['instructions'] ?? null,
        'due_date' => $data['due_date'] ?? null,
        'max_score' => $data['max_score'] ?? 100,
        'allow_late_submission' => $data['allow_late_submission'] ?? false,
        'late_penalty' => $data['late_penalty'] ?? 0,
        'submission_type' => $data['submission_type'] ?? 'both',
        'status' => $data['status'] ?? 'draft',
        'created_by' => $teacherId,
      ]);

      // Upload các file đính kèm
      if (!empty($files)) {
        foreach ($files as $index => $file) {
          $filePath = $file->store('assignment_files', 'public');
          AssignmentFile::create([
            'assignment_id' => $assignment->id,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $teacherId,
            'is_primary' => $index === 0,
          ]);
        }
      }

      DB::commit();

      $assignment->load(['files', 'creator']);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => 'Tạo bài tập thành công',
          'data' => $assignment,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Create assignment failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi tạo bài tập: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Cập nhật bài tập
   */
  public function updateAssignment(int $assignmentId, array $data, int $teacherId, array $files = []): array
  {
    DB::beginTransaction();
    try {
      $assignment = $this->assignmentRepository->findOrFail($assignmentId);

      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền sửa bài tập này'],
          ];
        }
      }

      $updateData = array_filter([
        'title' => $data['title'] ?? null,
        'description' => $data['description'] ?? null,
        'instructions' => $data['instructions'] ?? null,
        'due_date' => $data['due_date'] ?? null,
        'max_score' => $data['max_score'] ?? null,
        'allow_late_submission' => $data['allow_late_submission'] ?? null,
        'late_penalty' => $data['late_penalty'] ?? null,
        'submission_type' => $data['submission_type'] ?? null,
        'status' => $data['status'] ?? null,
      ], fn($v) => $v !== null);

      if (!empty($updateData)) {
        $this->assignmentRepository->update($assignmentId, $updateData);
      }

      // Xoá file cũ nếu có yêu cầu
      if (!empty($data['remove_files'])) {
        $filesToRemove = AssignmentFile::where('assignment_id', $assignmentId)
          ->whereIn('id', $data['remove_files'])
          ->get();

        foreach ($filesToRemove as $file) {
          Storage::disk('public')->delete($file->file_path);
          $file->delete();
        }
      }

      // Upload file mới
      if (!empty($files)) {
        foreach ($files as $file) {
          $filePath = $file->store('assignment_files', 'public');
          AssignmentFile::create([
            'assignment_id' => $assignmentId,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $teacherId,
            'is_primary' => false,
          ]);
        }
      }

      DB::commit();

      $assignment = $this->assignmentRepository->getAssignmentWithRelations($assignmentId, [
        'files',
        'creator',
        'submissions.student',
      ]);

      return [
        'status' => 200,
        'data' => ['success' => true, 'message' => 'Cập nhật bài tập thành công', 'data' => $assignment],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Update assignment failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi cập nhật bài tập: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Xoá bài tập (soft delete)
   */
  public function deleteAssignment(int $assignmentId, int $teacherId): array
  {
    try {
      $assignment = $this->assignmentRepository->findOrFail($assignmentId);

      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền xoá bài tập này'],
          ];
        }
      }

      $this->assignmentRepository->delete($assignmentId);

      return [
        'status' => 200,
        'data' => ['success' => true, 'message' => 'Xoá bài tập thành công'],
      ];
    } catch (\Exception $e) {
      Log::error('Delete assignment failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi xoá bài tập: ' . $e->getMessage()],
      ];
    }
  }

  // ================================================================
  // STUDENT: Nộp bài
  // ================================================================

  /**
   * Học sinh nộp bài tập
   */
  public function submitAssignment(int $assignmentId, int $studentId, array $files = [], ?string $textContent = null): array
  {
    DB::beginTransaction();
    try {
      $assignment = $this->assignmentRepository->findOrFail($assignmentId);

      // Kiểm tra bài tập đã publish chưa
      if ($assignment->status !== 'published') {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Bài tập chưa được mở nộp bài'],
        ];
      }

      // Kiểm tra đã nộp chưa
      $existingSubmission = $this->submissionRepository->findByAssignmentAndStudent($assignmentId, $studentId);
      if ($existingSubmission) {
        return [
          'status' => 400,
          'data' => ['success' => false, 'message' => 'Bạn đã nộp bài tập này rồi'],
        ];
      }

      // Kiểm tra hết hạn
      $isLate = false;
      if ($assignment->due_date && now()->gt($assignment->due_date)) {
        if (!$assignment->allow_late_submission) {
          return [
            'status' => 400,
            'data' => ['success' => false, 'message' => 'Bài tập đã hết hạn nộp'],
          ];
        }
        $isLate = true;
      }

      // Tạo submission
      $submission = $this->submissionRepository->create([
        'assignment_id' => $assignmentId,
        'student_id' => $studentId,
        'submitted_at' => now(),
        'is_late' => $isLate,
        'status' => 'submitted',
      ]);

      // Upload file đính kèm của học sinh
      if (!empty($files)) {
        foreach ($files as $file) {
          $filePath = $file->store('submission_attachments', 'public');
          $mimeType = $file->getMimeType();

          $fileType = 'other';
          if (str_contains($mimeType, 'image')) {
            $fileType = 'image';
          } elseif (str_contains($mimeType, 'pdf') || str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            $fileType = 'document';
          }

          SubmissionAttachment::create([
            'submission_id' => $submission->id,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $mimeType,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $fileType,
            'uploaded_at' => now(),
          ]);
        }
      }

      DB::commit();

      // Tự động gọi AI chấm điểm bằng background job sau khi gửi response
      try {
        \App\Jobs\GradeSubmissionJob::dispatch($submission->id)->afterResponse();
      } catch (\Exception $e) {
        Log::warning('AI auto-grading job dispatch failed', ['submission_id' => $submission->id, 'error' => $e->getMessage()]);
      }

      $submission->load(['attachments', 'grading']);

      return [
        'status' => 201,
        'data' => [
          'success' => true,
          'message' => $isLate ? 'Nộp bài thành công (trễ hạn)' : 'Nộp bài thành công',
          'data' => $submission,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Submit assignment failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi nộp bài: ' . $e->getMessage()],
      ];
    }
  }

  // ================================================================
  // TEACHER: Xem và chấm điểm
  // ================================================================

  /**
   * Lấy danh sách bài nộp của một bài tập
   */
  public function getSubmissions(int $assignmentId, int $teacherId): array
  {
    try {
      $assignment = $this->assignmentRepository->findOrFail($assignmentId);

      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền xem bài nộp'],
          ];
        }
      }

      $submissions = $this->submissionRepository->findByAssignment($assignmentId);

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $submissions],
      ];
    } catch (\Exception $e) {
      Log::error('Get submissions failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi lấy danh sách bài nộp: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Xem chi tiết bài nộp của học sinh
   */
  public function getSubmissionDetail(int $submissionId, int $teacherId): array
  {
    try {
      $submission = $this->submissionRepository->getSubmissionWithRelations($submissionId, [
        'student',
        'assignment.files',
        'attachments',
        'grading.gradedBy',
      ]);

      $assignment = $submission->assignment;
      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền xem bài nộp này'],
          ];
        }
      }

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $submission],
      ];
    } catch (\Exception $e) {
      Log::error('Get submission detail failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi lấy chi tiết bài nộp: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Yêu cầu AI chấm điểm bài nộp
   */
  public function requestAIGrading(int $submissionId, int $teacherId): array
  {
    try {
      $submission = $this->submissionRepository->getSubmissionWithRelations($submissionId, [
        'assignment',
        'attachments',
        'grading',
      ]);

      $assignment = $submission->assignment;
      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền thực hiện'],
          ];
        }
      }

      $grading = $submission->grading ?? new Grading([
        'submission_id' => $submission->id,
        'max_score' => $assignment->max_score,
      ]);

      if ($grading->exists && $grading->ai_status === 'processing') {
        return [
          'status' => 202,
          'data' => [
            'success' => true,
            'message' => 'AI is already grading this submission',
            'data' => $grading,
          ],
        ];
      }

      $grading->ai_status = 'processing';
      $grading->save();

      \App\Jobs\GradeSubmissionJob::dispatch($submission->id);

      return [
        'status' => 202,
        'data' => [
          'success' => true,
          'message' => 'AI grading has been queued',
          'data' => $grading->fresh(),
        ],
      ];
    } catch (\Exception $e) {
      Log::error('AI grading request failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi AI chấm điểm: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Giáo viên chốt điểm cuối cùng
   */
  public function finalizeGrading(int $submissionId, int $teacherId, array $data): array
  {
    DB::beginTransaction();
    try {
      $submission = $this->submissionRepository->getSubmissionWithRelations($submissionId, [
        'assignment',
        'grading',
      ]);

      $assignment = $submission->assignment;
      if ($assignment->created_by !== $teacherId) {
        if ($assignment->class && $assignment->class->teacher_id !== $teacherId) {
          return [
            'status' => 403,
            'data' => ['success' => false, 'message' => 'Bạn không có quyền chấm điểm'],
          ];
        }
      }

      $score = $data['score'];
      $maxScore = $assignment->max_score;
      $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

      // Áp dụng penalty nếu nộp trễ
      if ($submission->is_late && $assignment->allow_late_submission && $assignment->late_penalty > 0) {
        $penalty = ($score / 100) * $assignment->late_penalty;
        $score = max(0, $score - $penalty);
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;
      }

      $grading = $submission->grading;
      if ($grading) {
        $gradingData = [
          'graded_by' => $teacherId,
          'score' => $score,
          'max_score' => $maxScore,
          'percentage' => $percentage,
          'feedback' => $data['feedback'] ?? $grading->feedback,
          'graded_at' => now(),
        ];

        if (!empty($data['ai_review']) && $grading->ai_feedback) {
          $aiFeedback = json_decode($grading->ai_feedback, true);
          if (is_array($aiFeedback)) {
            $aiFeedback['teacher_review'] = [
              'decisions' => $data['ai_review']['decisions'] ?? [],
              'reviewed_score' => $data['ai_review']['reviewed_score'] ?? $score,
              'final_score' => $score,
              'reviewed_by' => $teacherId,
              'reviewed_at' => now()->toISOString(),
            ];
            $gradingData['ai_feedback'] = json_encode($aiFeedback, JSON_UNESCAPED_UNICODE);
          }
        }

        $grading->update($gradingData);
      } else {
        $grading = Grading::create([
          'submission_id' => $submissionId,
          'graded_by' => $teacherId,
          'score' => $score,
          'max_score' => $maxScore,
          'percentage' => $percentage,
          'feedback' => $data['feedback'] ?? null,
          'ai_status' => 'pending',
          'graded_at' => now(),
        ]);
      }

      // Cập nhật trạng thái submission
      $submission->update(['status' => 'graded']);

      DB::commit();

      $submission->load(['grading.gradedBy', 'student']);

      return [
        'status' => 200,
        'data' => [
          'success' => true,
          'message' => 'Chấm điểm thành công',
          'data' => $submission,
        ],
      ];
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Finalize grading failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi chấm điểm: ' . $e->getMessage()],
      ];
    }
  }

  // ================================================================
  // STUDENT: Xem bài tập & kết quả
  // ================================================================

  /**
   * Học sinh xem danh sách bài tập của lớp
   */
  public function getStudentAssignments(int $classId, int $studentId): array
  {
    try {
      $assignments = $this->assignmentRepository->findPublishedByClass($classId);
      $assignments->load(['files', 'submissions' => function ($query) use ($studentId) {
        $query->where('student_id', $studentId)->with(['attachments', 'grading']);
      }]);

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $assignments],
      ];
    } catch (\Exception $e) {
      Log::error('Get student assignments failed', ['error' => $e->getMessage()]);
      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi lấy danh sách bài tập: ' . $e->getMessage()],
      ];
    }
  }

  // ================================================================
  // AI GRADING INTERNAL
  // ================================================================

  /**
   * Trigger AI grading cho submission
   */
  private function triggerAIGrading(int $submissionId): void
  {
    try {
      $submission = $this->submissionRepository->getSubmissionWithRelations($submissionId, [
        'assignment',
        'attachments',
      ]);

      $this->performAIGrading($submission);
    } catch (\Exception $e) {
      Log::error('Trigger AI grading failed', ['submission_id' => $submissionId, 'error' => $e->getMessage()]);
    }
  }

  /**
   * Thực hiện AI chấm điểm theo submissionId (dùng cho Background Job)
   */
  public function performAIGradingById(int $submissionId): void
  {
    try {
      $submission = $this->submissionRepository->getSubmissionWithRelations($submissionId, [
        'assignment',
        'attachments',
        'grading',
      ]);

      if ($submission) {
        $this->performAIGrading($submission);
      }
    } catch (\Exception $e) {
      Log::error('performAIGradingById failed', ['submission_id' => $submissionId, 'error' => $e->getMessage()]);
    }
  }

  /**
   * Thực hiện AI chấm điểm
   */
  private function performAIGrading(AssignmentSubmission $submission): Grading
  {
    $assignment = $submission->assignment;
    $assignment->loadMissing('files');

    // Tạo hoặc cập nhật grading record
    $grading = $submission->grading ?? new Grading([
      'submission_id' => $submission->id,
      'max_score' => $assignment->max_score,
    ]);

    $grading->ai_status = 'processing';
    $grading->graded_by = null; // Chưa có người chấm, để null
    $grading->save();

    try {
      // Python AI Service handles extraction and grading.
      $aiResult = $this->aiServiceClient->gradeAssignmentSubmission([
        'title' => $assignment->title,
        'description' => $assignment->description,
        'instructions' => $assignment->instructions,
        'max_score' => $assignment->max_score,
      ], $submission->attachments, $assignment->files);

      // Cập nhật kết quả AI
      $grading->update([
        'ai_suggested_score' => $aiResult['suggested_score'] ?? null,
        'ai_feedback' => json_encode($aiResult, JSON_UNESCAPED_UNICODE),
        'ai_status' => 'completed',
        'ai_graded_at' => now(),
      ]);

      return $grading->fresh();
    } catch (\Exception $e) {
      $grading->update([
        'ai_status' => 'failed',
        'ai_feedback' => 'AI grading error: ' . $e->getMessage(),
      ]);

      Log::error('AI grading failed', [
        'submission_id' => $submission->id,
        'error' => $e->getMessage(),
      ]);

      return $grading->fresh();
    }
  }

  /**
   * Tìm kiếm bài tập theo tên trong một lớp học
   */
  public function searchAssignments(int $classId, string $query, int $teacherId): array
  {
    try {
      $class = Classz::find($classId);

      if (!$class || $class->teacher_id !== $teacherId) {
        return [
          'status' => 403,
          'data' => ['success' => false, 'message' => 'Bạn không có quyền truy cập lớp này'],
        ];
      }

      $assignments = Assignment::where('class_id', $classId)
        ->where('title', 'like', "%{$query}%")
        ->with(['files', 'creator', 'submissions.student'])
        ->orderBy('created_at', 'desc')
        ->get();

      // Thêm thống kê cho mỗi bài tập
      $assignments->each(function ($assignment) {
        $assignment->submission_count = $assignment->submissions->count();
        $assignment->graded_count = $assignment->submissions->filter(fn($s) => $s->status === 'graded')->count();
      });

      return [
        'status' => 200,
        'data' => ['success' => true, 'data' => $assignments],
      ];
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('Assignment search failed', [
        'class_id' => $classId,
        'error' => $e->getMessage(),
      ]);

      return [
        'status' => 500,
        'data' => ['success' => false, 'message' => 'Lỗi khi tìm kiếm bài tập'],
      ];
    }
  }
}
