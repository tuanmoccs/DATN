<?php

namespace App\Repositories;

use App\Models\Otp;
use App\Repositories\Contracts\OtpRepositoryInterface;

class OtpRepository extends BaseRepository implements OtpRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Otp::class;
  }

  public function generate(string $email, string $type = 'registration'): string
  {
    // Xóa các OTP cũ chưa dùng của email này
    $this->deleteUnusedByEmail($email, $type);

    // Tạo mã OTP 6 số
    $otpCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    // Lưu vào database với thời gian hết hạn 10 phút
    $this->create([
      'email' => $email,
      'otp' => $otpCode,
      'type' => $type,
      'expires_at' => now()->addMinutes(10),
      'is_used' => false,
    ]);

    return $otpCode;
  }

  public function verify(string $email, string $otp, string $type = 'registration'): bool
  {
    $otpRecord = $this->query()
      ->where('email', $email)
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

  public function deleteUnusedByEmail(string $email, string $type): int
  {
    return $this->query()
      ->where('email', $email)
      ->where('type', $type)
      ->where('is_used', false)
      ->delete();
  }
}
