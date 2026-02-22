<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
  use HasFactory;

  protected $fillable = [
    'email',
    'otp',
    'type',
    'expires_at',
    'is_used',
  ];

  protected $casts = [
    'expires_at' => 'datetime',
    'is_used' => 'boolean',
  ];

  // Tạo OTP mới
  public static function generate(string $email, string $type = 'registration'): string
  {
    // Xóa các OTP cũ chưa dùng của email này
    self::where('email', $email)
      ->where('type', $type)
      ->where('is_used', false)
      ->delete();

    // Tạo mã OTP 6 số
    $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Lưu vào database với thời gian hết hạn 10 phút
    self::create([
      'email' => $email,
      'otp' => $otpCode,
      'type' => $type,
      'expires_at' => now()->addMinutes(10),
      'is_used' => false,
    ]);

    return $otpCode;
  }

  // Xác thực OTP
  public static function verify(string $email, string $otp, string $type = 'registration'): bool
  {
    $otpRecord = self::where('email', $email)
      ->where('otp', $otp)
      ->where('type', $type)
      ->where('is_used', false)
      ->where('expires_at', '>', now())
      ->first();

    if ($otpRecord) {
      $otpRecord->update(['is_used' => true]);
      return true;
    }

    return false;
  }
}
