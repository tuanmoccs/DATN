<?php

namespace App\Repositories\Contracts;

interface OtpRepositoryInterface extends BaseRepositoryInterface
{
  public function generate(string $email, string $type = 'registration'): string;

  public function verify(string $email, string $otp, string $type = 'registration'): bool;

  public function deleteUnusedByEmail(string $email, string $type): int;
}
