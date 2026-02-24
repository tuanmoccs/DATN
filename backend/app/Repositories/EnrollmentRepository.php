<?php

namespace App\Repositories;

use App\Models\Enrollment;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentRepository extends BaseRepository implements EnrollmentRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Enrollment::class;
  }

  public function findByClassAndUser(int $classId, int $userId): mixed
  {
    return $this->query()
      ->where('class_id', $classId)
      ->where('user_id', $userId)
      ->first();
  }

  public function getStudentsByClass(int $classId): Collection
  {
    return $this->query()
      ->where('class_id', $classId)
      ->where('status', 'active')
      ->with('user')
      ->get();
  }

  public function getClassesByStudent(int $userId): Collection
  {
    return $this->query()
      ->where('user_id', $userId)
      ->where('status', 'active')
      ->with('class')
      ->get();
  }

  public function isEnrolled(int $classId, int $userId): bool
  {
    return $this->query()
      ->where('class_id', $classId)
      ->where('user_id', $userId)
      ->where('status', 'active')
      ->exists();
  }
}
