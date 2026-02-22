<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterStudentRequest;
use App\Http\Requests\Auth\RegisterTeacherOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
  public function __construct(
    private readonly AuthService $authService
  ) {}

  public function registerTeacherSendOtp(RegisterTeacherOtpRequest $request): JsonResponse
  {
    $result = $this->authService->sendTeacherRegistrationOtp(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }

  public function registerTeacherVerifyOtp(VerifyOtpRequest $request): JsonResponse
  {
    $result = $this->authService->verifyTeacherRegistrationOtp(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }

  public function registerStudent(RegisterStudentRequest $request): JsonResponse
  {
    $result = $this->authService->registerStudent(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }

  public function login(LoginRequest $request): JsonResponse
  {
    $result = $this->authService->login(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }

  public function logout(Request $request): JsonResponse
  {
    $this->authService->logout();

    return response()->json([
      'success' => true,
      'message' => 'Đăng xuất thành công'
    ]);
  }

  public function me(Request $request): JsonResponse
  {
    return response()->json([
      'success' => true,
      'user' => auth()->user()
    ]);
  }

  public function refresh(): JsonResponse
  {
    $result = $this->authService->refreshToken();

    return response()->json($result['data'], $result['status']);
  }
}
