<?php

namespace App\Jobs;

use App\Services\LessonService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLessonAiContentJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public int $timeout = 1200;
  public int $tries = 1;
  public bool $failOnTimeout = true;

  public function __construct(
    private readonly int $batchId,
  ) {}

  public function handle(LessonService $lessonService): void
  {
    $lessonService->processAiGenerationBatch($this->batchId);
  }

  public function failed(\Throwable $exception): void
  {
    app(LessonService::class)->markAiGenerationBatchFailed($this->batchId, $exception->getMessage());
  }
}
