<?php

namespace App\Repositories;

use App\Models\Presentation;
use App\Repositories\Contracts\PresentationRepositoryInterface;

class PresentationRepository extends BaseRepository implements PresentationRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Presentation::class;
  }

  public function findByLesson(int $lessonId): mixed
  {
    return $this->query()
      ->where('lesson_id', $lessonId)
      ->first();
  }

  public function getPresentationWithSlides(int $id): mixed
  {
    return $this->query()
      ->with(['slides' => function ($query) {
        $query->orderBy('order');
      }, 'versions'])
      ->findOrFail($id);
  }
}
