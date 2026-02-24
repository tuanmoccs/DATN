<?php

namespace App\Repositories;

use App\Models\Classz;
use App\Repositories\Contracts\ClassRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ClassRepository extends BaseRepository implements ClassRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Classz::class;
  }

  public function findByTeacher(int $teacherId): Collection
  {
    return $this->query()
      ->where('teacher_id', $teacherId)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function findByCode(string $code)
  {
    return $this->query()->where('code', $code)->first();
  }

  public function getActiveClasses(): Collection
  {
    return $this->query()
      ->where('status', 'active')
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function getClassWithRelations(int $id, array $relations = []): mixed
  {
    $query = $this->query();

    if (!empty($relations)) {
      $query->with($relations);
    }

    return $query->findOrFail($id);
  }
}
