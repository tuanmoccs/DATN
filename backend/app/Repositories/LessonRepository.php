<?php

namespace App\Repositories;

use App\Models\Lesson;
use App\Repositories\Contracts\LessonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LessonRepository extends BaseRepository implements LessonRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Lesson::class;
  }

  public function findByClass(int $classId): Collection
  {
    return $this->query()
      ->where('class_id', $classId)
      ->orderBy('order')
      ->get();
  }

  public function findPublishedByClass(int $classId): Collection
  {
    return $this->query()
      ->where('class_id', $classId)
      ->where('status', 'published')
      ->orderBy('order')
      ->get();
  }

  public function getLessonWithRelations(int $id, array $relations = []): mixed
  {
    $query = $this->query();

    if (!empty($relations)) {
      $query->with($relations);
    }

    return $query->findOrFail($id);
  }
}
