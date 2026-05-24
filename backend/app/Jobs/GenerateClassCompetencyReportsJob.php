<?php

namespace App\Jobs;

use App\Services\AiCompetencyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateClassCompetencyReportsJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public int $timeout = 900;
  public int $tries = 1;

  public function __construct(
    private readonly int $batchId,
  ) {}

  public function handle(AiCompetencyReportService $reportService): void
  {
    $reportService->processClassGenerateBatch($this->batchId);
  }

  public function failed(\Throwable $exception): void
  {
    app(AiCompetencyReportService::class)->markClassGenerateBatchFailed($this->batchId, $exception->getMessage());
  }
}
