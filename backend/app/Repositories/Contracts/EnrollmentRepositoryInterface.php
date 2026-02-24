<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface extends BaseRepositoryInterface
{
  public function findByClassAndUser(int $classId, int $userId): mixed;

  public function getStudentsByClass(int $classId): Collection;

  public function getClassesByStudent(int $userId): Collection;

  public function isEnrolled(int $classId, int $userId): bool;
}
