<?php

use App\Http\Controllers\AuthController;
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
});
