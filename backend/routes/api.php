<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
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
    // Teacher - Quản lý lớp học
    // ==========================================
    Route::prefix('teacher/classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::put('/{id}', [ClassController::class, 'update']);
        Route::delete('/{id}', [ClassController::class, 'destroy']);

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
});
