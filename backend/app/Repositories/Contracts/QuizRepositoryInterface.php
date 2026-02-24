<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface QuizRepositoryInterface extends BaseRepositoryInterface
{
  public function findByLesson(int $lessonId): Collection;

  public function findPublishedByLesson(int $lessonId): Collection;

  public function getQuizWithQuestions(int $id): mixed;
}
