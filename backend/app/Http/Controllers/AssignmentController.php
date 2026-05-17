<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assignment\CreateAssignmentRequest;
use App\Http\Requests\Assignment\UpdateAssignmentRequest;
use App\Http\Requests\Assignment\SubmitAssignmentRequest;
use App\Http\Requests\Assignment\GradeAssignmentRequest;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
  public function __construct(
    private readonly AssignmentService $assignmentService
  ) {}

  // ================================================================
  // TEACHER: CRUD bài tập
  // ================================================================

  /**
   * Danh sách bài tập theo lớp
   */
  public function index(int $classId): JsonResponse
  {
    $result = $this->assignmentService->getAssignmentsByClass($classId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Chi tiết bài tập
   */
  public function show(int $id): JsonResponse
  {
    $result = $this->assignmentService->getAssignmentDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Tạo bài tập mới
   */
  public function store(CreateAssignmentRequest $request): JsonResponse
  {
    $files = $request->file('files', []);
    $result = $this->assignmentService->createAssignment(
      $request->validated(),
      auth()->id(),
      $files
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật bài tập
   */
  public function update(UpdateAssignmentRequest $request, int $id): JsonResponse
  {
    $files = $request->file('files', []);
    $result = $this->assignmentService->updateAssignment(
      $id,
      $request->validated(),
      auth()->id(),
      $files
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xoá bài tập
   */
  public function destroy(int $id): JsonResponse
  {
    $result = $this->assignmentService->deleteAssignment($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  // ================================================================
  // TEACHER: Quản lý bài nộp & chấm điểm
  // ================================================================

  /**
   * Danh sách bài nộp của bài tập
   */
  public function getSubmissions(int $assignmentId): JsonResponse
  {
    $result = $this->assignmentService->getSubmissions($assignmentId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Chi tiết bài nộp
   */
  public function getSubmissionDetail(int $submissionId): JsonResponse
  {
    $result = $this->assignmentService->getSubmissionDetail($submissionId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Yêu cầu AI chấm điểm
   */
  public function requestAIGrading(int $submissionId): JsonResponse
  {
    $result = $this->assignmentService->requestAIGrading($submissionId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Giáo viên chốt điểm cuối cùng
   */
  public function finalizeGrading(GradeAssignmentRequest $request, int $submissionId): JsonResponse
  {
    $result = $this->assignmentService->finalizeGrading(
      $submissionId,
      auth()->id(),
      $request->validated()
    );
    return response()->json($result['data'], $result['status']);
  }

  // ================================================================
  // STUDENT: Nộp bài & xem kết quả
  // ================================================================

  /**
   * Học sinh xem danh sách bài tập của lớp
   */
  public function studentAssignments(int $classId): JsonResponse
  {
    $result = $this->assignmentService->getStudentAssignments($classId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Học sinh nộp bài
   */
  public function submitAssignment(SubmitAssignmentRequest $request, int $assignmentId): JsonResponse
  {
    $files = $request->file('files', []);
    $result = $this->assignmentService->submitAssignment(
      $assignmentId,
      auth()->id(),
      $files,
      $request->input('text_content')
    );
    return response()->json($result['data'], $result['status']);
  }


  public function search(int $classId, Request $request): JsonResponse
  {
    $query = $request->input('q', '');
    $result = $this->assignmentService->searchAssignments($classId, $query, auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
