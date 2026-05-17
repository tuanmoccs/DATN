<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface AssignmentSubmissionRepositoryInterface extends BaseRepositoryInterface
{
  public function findByAssignment(int $assignmentId): Collection;
  public function findByStudent(int $studentId): Collection;
  public function findByAssignmentAndStudent(int $assignmentId, int $studentId): mixed;
  public function getSubmissionWithRelations(int $id, array $relations = []): mixed;
}
