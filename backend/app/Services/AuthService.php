<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
  private const CACHE_TTL = 300; // 10 minutes
  private const CACHE_PREFIX = 'teacher_registration_';

  public function sendTeacherRegistrationOtp(array $data): array
  {
    try {
      $otpCode = Otp::generate($data['email'], 'registration');

      Mail::to($data['email'])->send(new OtpMail($otpCode, $data['name']));

      Cache::put(
        self::CACHE_PREFIX . $data['email'],
        [
          'name' => $data['name'],
          'email' => $data['email'],
          'password' => Hash::make($data['password']),
        ],
        self::CACHE_TTL
      );

      return [
        'data' => [
          'success' => true,
          'message' => 'Mã OTP đã được gửi đến email của bạn'
        ],
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra khi gửi email: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }

  public function verifyTeacherRegistrationOtp(array $data): array
  {
    if (!Otp::verify($data['email'], $data['otp'], 'registration')) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn'
        ],
        'status' => 400
      ];
    }

    $userData = Cache::get(self::CACHE_PREFIX . $data['email']);

    if (!$userData) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Phiên đăng ký đã hết hạn, vui lòng thử lại'
        ],
        'status' => 400
      ];
    }

    try {
      $user = User::create([
        'name' => $userData['name'],
        'email' => $userData['email'],
        'password' => $userData['password'],
        'role' => 'teacher',
        'email_verified_at' => now(),
        'is_active' => true,
      ]);

      Cache::forget(self::CACHE_PREFIX . $data['email']);

      $token = JWTAuth::fromUser($user);

      return [
        'data' => [
          'success' => true,
          'message' => 'Đăng ký thành công',
          'user' => $user,
          'access_token' => $token,
          'token_type' => 'bearer',
          'expires_in' => config('jwt.ttl') * 60
        ],
        'status' => 201
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }

  public function registerStudent(array $data): array
  {
    try {
      $user = User::create([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'role' => 'student',
        'is_active' => true,
      ]);

      $token = JWTAuth::fromUser($user);

      return [
        'data' => [
          'success' => true,
          'message' => 'Đăng ký thành công',
          'user' => $user,
          'access_token' => $token,
          'token_type' => 'bearer',
          'expires_in' => config('jwt.ttl') * 60
        ],
        'status' => 201
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }

  public function login(array $credentials): array
  {
    $user = User::where('email', $credentials['email'])
      ->where('role', $credentials['role'])
      ->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Email hoặc mật khẩu không chính xác'
        ],
        'status' => 401
      ];
    }

    if (!$user->is_active) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Tài khoản đã bị khóa'
        ],
        'status' => 403
      ];
    }

    $token = JWTAuth::fromUser($user);

    return [
      'data' => [
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl') * 60
      ],
      'status' => 200
    ];
  }

  public function logout(): void
  {
    JWTAuth::invalidate(JWTAuth::getToken());
  }

  public function refreshToken(): array
  {
    try {
      $newToken = JWTAuth::refresh(JWTAuth::getToken());

      return [
        'data' => [
          'success' => true,
          'access_token' => $newToken,
          'token_type' => 'bearer',
          'expires_in' => config('jwt.ttl') * 60
        ],
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Token không hợp lệ'
        ],
        'status' => 401
      ];
    }
  }
}
