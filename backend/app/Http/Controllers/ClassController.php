<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassRoom\CreateClassRequest;
use App\Http\Requests\ClassRoom\UpdateClassRequest;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
  public function __construct(
    private readonly ClassService $classService
  ) {}

  /**
   * Lấy danh sách lớp học của giáo viên
   */
  public function index(Request $request): JsonResponse
  {
    $result = $this->classService->getClassesByTeacher(auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Tạo lớp học mới
   */
  public function store(CreateClassRequest $request): JsonResponse
  {
    $result = $this->classService->createClass(
      $request->validated(),
      auth()->id()
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Lấy chi tiết lớp học
   */
  public function show(int $id): JsonResponse
  {
    $result = $this->classService->getClassDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật lớp học
   */
  public function update(UpdateClassRequest $request, int $id): JsonResponse
  {
    $result = $this->classService->updateClass($id, $request->validated(), auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xóa lớp học
   */
  public function destroy(int $id): JsonResponse
  {
    $result = $this->classService->deleteClass($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Học sinh gửi yêu cầu tham gia lớp
   */
  public function requestJoin(Request $request): JsonResponse
  {
    $request->validate([
      'code' => 'required|string|size:6',
    ], [
      'code.required' => 'Vui lòng nhập mã lớp học',
      'code.size' => 'Mã lớp học phải có 6 ký tự',
    ]);

    $result = $this->classService->requestJoinClass(
      strtoupper($request->code),
      auth()->id()
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Giáo viên duyệt yêu cầu tham gia
   */
  public function approveEnrollment(int $enrollmentId): JsonResponse
  {
    $result = $this->classService->approveEnrollment($enrollmentId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Giáo viên từ chối yêu cầu tham gia
   */
  public function rejectEnrollment(int $enrollmentId): JsonResponse
  {
    $result = $this->classService->rejectEnrollment($enrollmentId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Giáo viên xóa học sinh khỏi lớp
   */
  public function removeStudent(int $enrollmentId): JsonResponse
  {
    $result = $this->classService->removeStudent($enrollmentId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Học sinh - Lấy danh sách lớp đã tham gia
   */
  public function studentClasses(): JsonResponse
  {
    $result = $this->classService->getStudentClasses(auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Học sinh - Lấy chi tiết lớp học
   */
  public function studentClassDetail(int $id): JsonResponse
  {
    $result = $this->classService->getStudentClassDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
