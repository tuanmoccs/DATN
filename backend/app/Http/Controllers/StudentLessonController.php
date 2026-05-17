<?php

namespace App\Http\Controllers;

use App\Services\StudentLessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentLessonController extends Controller
{
  public function __construct(
    private readonly StudentLessonService $studentLessonService
  ) {}

  /**
   * Lấy chi tiết bài học cho học sinh (slides + quiz info + progress)
   */
  public function show(int $lessonId): JsonResponse
  {
    $result = $this->studentLessonService->getLessonDetail($lessonId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật tiến trình xem slide
   */
  public function updateSlideProgress(Request $request, int $lessonId): JsonResponse
  {
    $request->validate([
      'slides_viewed' => 'required|integer|min:0',
      'total_slides' => 'required|integer|min:1',
    ]);

    $result = $this->studentLessonService->updateSlideProgress(
      $lessonId,
      auth()->id(),
      $request->input('slides_viewed'),
      $request->input('total_slides')
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Bắt đầu làm quiz
   */
  public function startQuiz(int $quizId): JsonResponse
  {
    $result = $this->studentLessonService->startQuizAttempt($quizId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Nộp bài quiz
   */
  public function submitQuiz(Request $request, int $quizId): JsonResponse
  {
    $request->validate([
      'attempt_id' => 'required|integer',
      'answers' => 'required|array',
      'answers.*.question_id' => 'required|integer',
      'answers.*.option_id' => 'required|integer',
    ]);

    $result = $this->studentLessonService->submitQuiz(
      $quizId,
      auth()->id(),
      $request->input('attempt_id'),
      $request->input('answers')
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Lấy kết quả quiz attempt
   */
  public function getQuizResult(int $attemptId): JsonResponse
  {
    $result = $this->studentLessonService->getQuizResult($attemptId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
