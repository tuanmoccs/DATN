<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface AssignmentRepositoryInterface extends BaseRepositoryInterface
{
  public function findByClass(int $classId): Collection;
  public function findPublishedByClass(int $classId): Collection;
  public function getAssignmentWithRelations(int $id, array $relations = []): mixed;
}
