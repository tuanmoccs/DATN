<?php

namespace App\Repositories;

use App\Models\LessonPlan;
use App\Repositories\Contracts\LessonPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LessonPlanRepository extends BaseRepository implements LessonPlanRepositoryInterface
{
  protected function getModelClass(): string
  {
    return LessonPlan::class;
  }

  public function findByTeacher(int $teacherId): Collection
  {
    return $this->query()
      ->where('created_by', $teacherId)
      ->orderBy('updated_at', 'desc')
      ->get();
  }

  public function searchByTeacher(int $teacherId, string $query): Collection
  {
    return $this->query()
      ->where('created_by', $teacherId)
      ->where(function ($q) use ($query) {
        $q->where('title', 'like', "%{$query}%")
          ->orWhere('subject', 'like', "%{$query}%");
      })
      ->orderBy('updated_at', 'desc')
      ->get();
  }
}
