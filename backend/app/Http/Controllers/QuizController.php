<?php

namespace App\Http\Controllers;

use App\Services\QuizService;
use App\Http\Requests\Quiz\UpdateQuizRequest;
use App\Http\Requests\Quiz\UpdateQuizQuestionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
  public function __construct(
    private readonly QuizService $quizService
  ) {}

  /**
   * Lấy danh sách quiz của bài học
   */
  public function index(int $lessonId): JsonResponse
  {
    $result = $this->quizService->getQuizzesByLesson($lessonId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Lấy chi tiết quiz với câu hỏi và đáp án
   */
  public function show(int $id): JsonResponse
  {
    $result = $this->quizService->getQuizDetail($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật thông tin quiz
   */
  public function update(UpdateQuizRequest $request, int $id): JsonResponse
  {
    $result = $this->quizService->updateQuiz($id, $request->validated(), auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xóa quiz
   */
  public function destroy(int $id): JsonResponse
  {
    $result = $this->quizService->deleteQuiz($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Cập nhật câu hỏi và đáp án
   */
  public function updateQuestion(UpdateQuizQuestionRequest $request, int $quizId, int $questionId): JsonResponse
  {
    $result = $this->quizService->updateQuestion(
      $quizId,
      $questionId,
      $request->validated(),
      auth()->id()
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Thêm câu hỏi mới vào quiz
   */
  public function addQuestion(UpdateQuizQuestionRequest $request, int $quizId): JsonResponse
  {
    $result = $this->quizService->addQuestion(
      $quizId,
      $request->validated(),
      auth()->id()
    );
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Xóa câu hỏi khỏi quiz
   */
  public function deleteQuestion(int $quizId, int $questionId): JsonResponse
  {
    $result = $this->quizService->deleteQuestion($quizId, $questionId, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  /**
   * Publish quiz (chuyển trạng thái sang published)
   */
  public function publish(Request $request, int $id): JsonResponse
  {
    $result = $this->quizService->publishQuiz($id, $request->all(), auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
