<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lesson\CreateLessonRequest;
use App\Http\Requests\Lesson\UpdateLessonRequest;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
  public function __construct(
    private readonly LessonService $lessonService
  ) {}

  /**
   * Lấy danh sách bài học của lớp
   */
  public function index(int $classId): JsonResponse
  {
    $result = $this->lessonService->getLessonsByClass($classId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Tạo bài học mới (có thể kèm file upload + AI generation)
   */
  public function store(CreateLessonRequest $request): JsonResponse
  {
    $file = $request->file('file');

    $result = $this->lessonService->createLesson(
      $request->validated(),
      auth()->id(),
      $file
    );

    return response()->json($result['data'], $result['status']);
  }

  /**
   * Lấy chi tiết bài học
   */
  public function show(int $id): JsonResponse
  {
    $result = $this->lessonService->getLessonDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật bài học
   */
  public function update(UpdateLessonRequest $request, int $id): JsonResponse
  {
    $file = $request->file('file');

    $result = $this->lessonService->updateLesson(
      $id,
      $request->validated(),
      auth()->id(),
      $file
    );

    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xóa bài học
   */
  public function destroy(int $id): JsonResponse
  {
    $result = $this->lessonService->deleteLesson($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Sinh lại slide bằng AI
   */
  public function regenerateSlides(Request $request, int $id): JsonResponse
  {
    $slideCount = $request->input('slide_count', 10);

    $result = $this->lessonService->regenerateSlides($id, auth()->id(), $slideCount);
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Sinh lại quiz bằng AI
   */
  public function regenerateQuiz(Request $request, int $id): JsonResponse
  {
    $questionCount = $request->input('question_count', 5);

    $result = $this->lessonService->regenerateQuiz($id, auth()->id(), $questionCount);
    return response()->json($result['data'], $result['status']);
  }
}
