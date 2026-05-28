<?php

namespace App\Jobs;

use App\Services\AssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GradeSubmissionJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public int $timeout = 600; // 10 minutes timeout for AI grading
  public int $tries = 1;

  /**
   * Create a new job instance.
   */
  public function __construct(
    private readonly int $submissionId,
  ) {}

  /**
   * Execute the job.
   */
  public function handle(AssignmentService $assignmentService): void
  {
    Log::info('Start background AI grading for submission', ['submission_id' => $this->submissionId]);
    $assignmentService->performAIGradingById($this->submissionId);
  }

  /**
   * Handle a job failure.
   */
  public function failed(\Throwable $exception): void
  {
    Log::error('Background AI grading failed', [
      'submission_id' => $this->submissionId,
      'error' => $exception->getMessage()
    ]);
  }
}
