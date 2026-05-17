<?php

namespace App\Repositories;

use App\Models\AssignmentSubmission;
use App\Repositories\Contracts\AssignmentSubmissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AssignmentSubmissionRepository extends BaseRepository implements AssignmentSubmissionRepositoryInterface
{
  protected function getModelClass(): string
  {
    return AssignmentSubmission::class;
  }

  public function findByAssignment(int $assignmentId): Collection
  {
    return $this->query()
      ->where('assignment_id', $assignmentId)
      ->with(['student', 'attachments', 'grading'])
      ->orderBy('submitted_at', 'desc')
      ->get();
  }

  public function findByStudent(int $studentId): Collection
  {
    return $this->query()
      ->where('student_id', $studentId)
      ->with(['assignment', 'attachments', 'grading'])
      ->orderBy('submitted_at', 'desc')
      ->get();
  }

  public function findByAssignmentAndStudent(int $assignmentId, int $studentId): mixed
  {
    return $this->query()
      ->where('assignment_id', $assignmentId)
      ->where('student_id', $studentId)
      ->with(['attachments', 'grading'])
      ->first();
  }

  public function getSubmissionWithRelations(int $id, array $relations = []): mixed
  {
    $query = $this->query();
    if (!empty($relations)) {
      $query->with($relations);
    }
    return $query->findOrFail($id);
  }
}
