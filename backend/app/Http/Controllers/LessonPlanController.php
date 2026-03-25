<?php

namespace App\Http\Controllers;

use App\Services\LessonPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonPlanController extends Controller
{
  public function __construct(
    private readonly LessonPlanService $lessonPlanService
  ) {}

  /**
   * Danh sách giáo án của giáo viên
   */
  public function index(): JsonResponse
  {
    $result = $this->lessonPlanService->getByTeacher(auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Tạo giáo án mới
   */
  public function store(Request $request): JsonResponse
  {
    $request->validate([
      'title' => 'required|string|max:255',
      'subject' => 'nullable|string|max:255',
      'grade_level' => 'nullable|string|max:100',
      'content' => 'nullable|string',
    ]);

    $result = $this->lessonPlanService->create($request->only([
      'title',
      'subject',
      'grade_level',
      'content',
    ]), auth()->id());

    return response()->json($result['data'], $result['status']);
  }

  /**
   * Chi tiết giáo án
   */
  public function show(int $id): JsonResponse
  {
    $result = $this->lessonPlanService->getDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật giáo án (lưu nội dung editor)
   */
  public function update(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'title' => 'sometimes|string|max:255',
      'subject' => 'nullable|string|max:255',
      'grade_level' => 'nullable|string|max:100',
      'content' => 'nullable|string',
      'status' => 'nullable|in:draft,completed',
    ]);

    $result = $this->lessonPlanService->update(
      $id,
      $request->only(['title', 'subject', 'grade_level', 'content', 'status']),
      auth()->id()
    );

    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xóa giáo án
   */
  public function destroy(int $id): JsonResponse
  {
    $result = $this->lessonPlanService->delete($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Upload tài liệu tham khảo (file) để index vào vector DB
   */
  public function uploadReference(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'file' => 'required|file|max:20480|mimes:pdf,doc,docx,txt',
    ]);

    $result = $this->lessonPlanService->uploadReference(
      $id,
      auth()->id(),
      $request->file('file')
    );

    return response()->json($result['data'], $result['status']);
  }

  /**
   * Upload tài liệu tham khảo (text) để index vào vector DB
   */
  public function uploadReferenceText(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'text' => 'required|string|min:50',
    ]);

    $result = $this->lessonPlanService->uploadReferenceText(
      $id,
      auth()->id(),
      $request->input('text')
    );

    return response()->json($result['data'], $result['status']);
  }

  /**
   * AI autocomplete suggestion
   */
  public function aiSuggest(Request $request, int $id): JsonResponse
  {
    $request->validate([
      'text' => 'required|string|max:5000',
    ]);

    $result = $this->lessonPlanService->aiSuggest(
      $id,
      $request->input('text'),
      auth()->id()
    );

    return response()->json($result);
  }

  /**
   * Tìm kiếm giáo án
   */
  public function search(Request $request): JsonResponse
  {
    $query = $request->input('q', '');
    $result = $this->lessonPlanService->search(auth()->id(), $query);
    return response()->json($result['data'], $result['status']);
  }
}
