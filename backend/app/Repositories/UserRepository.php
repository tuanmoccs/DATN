<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
  protected function getModelClass(): string
  {
    return User::class;
  }

  public function findByEmail(string $email): ?User
  {
    return $this->query()->where('email', $email)->first();
  }

  public function findByEmailAndRole(string $email, string $role): ?User
  {
    return $this->query()
      ->where('email', $email)
      ->where('role', $role)
      ->first();
  }

  public function createUser(array $data): User
  {
    return $this->query()->create($data);
  }
}
