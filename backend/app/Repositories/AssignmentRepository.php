<?php

namespace App\Repositories;

use App\Models\Assignment;
use App\Repositories\Contracts\AssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssignmentRepository extends BaseRepository implements AssignmentRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Assignment::class;
  }

  public function findByClass(int $classId): Collection
  {
    return $this->query()
      ->where('class_id', $classId)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function findPublishedByClass(int $classId): Collection
  {
    return $this->query()
      ->where('class_id', $classId)
      ->where('status', 'published')
      ->orderBy('due_date', 'asc')
      ->get();
  }

  public function getAssignmentWithRelations(int $id, array $relations = []): mixed
  {
    $query = $this->query();
    if (!empty($relations)) {
      $query->with($relations);
    }
    return $query->findOrFail($id);
  }
}
