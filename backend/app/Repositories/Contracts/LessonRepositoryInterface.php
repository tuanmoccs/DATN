<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface LessonRepositoryInterface extends BaseRepositoryInterface
{
  public function findByClass(int $classId): Collection;

  public function findPublishedByClass(int $classId): Collection;

  public function getLessonWithRelations(int $id, array $relations = []): mixed;
}
