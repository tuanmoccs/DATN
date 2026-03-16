<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\StudentLessonController;
use App\Http\Controllers\AssignmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    // Teacher registration với OTP
    Route::post('/register/teacher/send-otp', [AuthController::class, 'registerTeacherSendOtp']);
    Route::post('/register/teacher/verify-otp', [AuthController::class, 'registerTeacherVerifyOtp']);
    Route::post('/register/student', [AuthController::class, 'registerStudent']);
    Route::post('/login', [AuthController::class, 'login']);
});
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // ==========================================
    // Teacher - Dashboard
    // ==========================================
    Route::get('/teacher/dashboard', [DashboardController::class, 'teacherDashboard']);

    // ==========================================
    // Teacher - Quản lý lớp học
    // ==========================================
    Route::prefix('teacher/classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::put('/{id}', [ClassController::class, 'update']);
        Route::delete('/{id}', [ClassController::class, 'destroy']);
        Route::get('/{classId}/students/search', [ClassController::class, 'searchStudents']);

        // Quản lý yêu cầu tham gia
        Route::post('/enrollments/{enrollmentId}/approve', [ClassController::class, 'approveEnrollment']);
        Route::post('/enrollments/{enrollmentId}/reject', [ClassController::class, 'rejectEnrollment']);
        Route::delete('/enrollments/{enrollmentId}', [ClassController::class, 'removeStudent']);
    });

    // ==========================================
    // Student - Tham gia lớp học
    // ==========================================
    Route::post('/student/classes/join', [ClassController::class, 'requestJoin']);
    Route::get('/student/classes', [ClassController::class, 'studentClasses']);
    Route::get('/student/classes/{id}', [ClassController::class, 'studentClassDetail']);

    // ==========================================
    // Student - Bài học & Quiz
    // ==========================================
    Route::prefix('student/lessons')->group(function () {
        Route::get('/{lessonId}', [StudentLessonController::class, 'show']);
        Route::post('/{lessonId}/slide-progress', [StudentLessonController::class, 'updateSlideProgress']);
    });
    Route::prefix('student/quizzes')->group(function () {
        Route::post('/{quizId}/start', [StudentLessonController::class, 'startQuiz']);
        Route::post('/{quizId}/submit', [StudentLessonController::class, 'submitQuiz']);
        Route::get('/attempts/{attemptId}/result', [StudentLessonController::class, 'getQuizResult']);
    });

    // ==========================================
    // Teacher - Quản lý bài học
    // ==========================================
    Route::prefix('teacher/lessons')->group(function () {
        // CRUD bài học
        Route::get('/class/{classId}', [LessonController::class, 'index']);
        Route::post('/', [LessonController::class, 'store']);
        Route::get('/{id}', [LessonController::class, 'show']);
        Route::post('/{id}', [LessonController::class, 'update']); // POST thay PUT vì có file upload
        Route::delete('/{id}', [LessonController::class, 'destroy']);
        Route::get('/class/{classId}/search', [LessonController::class, 'search']);

        // AI Generation (throttle riêng, cho phép request chạy lâu)
        Route::middleware('throttle:ai')->group(function () {
            Route::post('/{id}/regenerate-slides', [LessonController::class, 'regenerateSlides']);
            Route::post('/{id}/regenerate-quiz', [LessonController::class, 'regenerateQuiz']);
        });
    });

    // ==========================================
    // Teacher - Quản lý Quiz & Câu hỏi
    // ==========================================
    Route::prefix('teacher/quizzes')->group(function () {
        Route::get('/lesson/{lessonId}', [QuizController::class, 'index']);
        Route::get('/{id}', [QuizController::class, 'show']);
        Route::get('/{id}/export-pdf', [QuizController::class, 'exportPDF']);
        Route::put('/{id}', [QuizController::class, 'update']);
        Route::delete('/{id}', [QuizController::class, 'destroy']);
        Route::post('/{id}/publish', [QuizController::class, 'publish']);

        // Quản lý câu hỏi
        Route::post('/{quizId}/questions', [QuizController::class, 'addQuestion']);
        Route::put('/{quizId}/questions/{questionId}', [QuizController::class, 'updateQuestion']);
        Route::delete('/{quizId}/questions/{questionId}', [QuizController::class, 'deleteQuestion']);
    });

    // ==========================================
    // Teacher - Quản lý bài tập (Assignment)
    // ==========================================
    Route::prefix('teacher/assignments')->group(function () {
        Route::get('/class/{classId}', [AssignmentController::class, 'index']);
        Route::post('/', [AssignmentController::class, 'store']);
        Route::get('/{id}', [AssignmentController::class, 'show']);
        Route::post('/{id}', [AssignmentController::class, 'update']); // POST vì có file upload
        Route::delete('/{id}', [AssignmentController::class, 'destroy']);
        Route::get('/class/{classId}/search', [AssignmentController::class, 'search']);

        // Quản lý bài nộp & chấm điểm
        Route::get('/{assignmentId}/submissions', [AssignmentController::class, 'getSubmissions']);
        Route::get('/submissions/{submissionId}', [AssignmentController::class, 'getSubmissionDetail']);
        Route::post('/submissions/{submissionId}/ai-grade', [AssignmentController::class, 'requestAIGrading']);
        Route::post('/submissions/{submissionId}/grade', [AssignmentController::class, 'finalizeGrading']);
    });

    // ==========================================
    // Student - Bài tập
    // ==========================================
    Route::prefix('student/assignments')->group(function () {
        Route::get('/class/{classId}', [AssignmentController::class, 'studentAssignments']);
        Route::post('/{assignmentId}/submit', [AssignmentController::class, 'submitAssignment']);
    });
});
