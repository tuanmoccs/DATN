<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ClassRepositoryInterface extends BaseRepositoryInterface
{
  public function findByTeacher(int $teacherId): Collection;

  public function findByCode(string $code);

  public function getActiveClasses(): Collection;

  public function getClassWithRelations(int $id, array $relations = []): mixed;
}
