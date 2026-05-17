<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface LessonPlanRepositoryInterface extends BaseRepositoryInterface
{
  public function findByTeacher(int $teacherId): Collection;
  public function searchByTeacher(int $teacherId, string $query): Collection;
}
