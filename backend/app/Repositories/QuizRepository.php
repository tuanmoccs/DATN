<?php

namespace App\Repositories;

use App\Models\Quiz;
use App\Repositories\Contracts\QuizRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuizRepository extends BaseRepository implements QuizRepositoryInterface
{
  protected function getModelClass(): string
  {
    return Quiz::class;
  }

  public function findByLesson(int $lessonId): Collection
  {
    return $this->query()
      ->where('lesson_id', $lessonId)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function findPublishedByLesson(int $lessonId): Collection
  {
    return $this->query()
      ->where('lesson_id', $lessonId)
      ->where('status', 'published')
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function getQuizWithQuestions(int $id): mixed
  {
    return $this->query()
      ->with(['questions.options'])
      ->findOrFail($id);
  }
}
