<?php

namespace App\Repositories\Contracts;

interface PresentationRepositoryInterface extends BaseRepositoryInterface
{
  public function findByLesson(int $lessonId): mixed;

  public function getPresentationWithSlides(int $id): mixed;
}
