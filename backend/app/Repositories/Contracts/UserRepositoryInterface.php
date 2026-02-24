<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
  public function findByEmail(string $email): ?User;

  public function findByEmailAndRole(string $email, string $role): ?User;

  public function createUser(array $data): User;
}
