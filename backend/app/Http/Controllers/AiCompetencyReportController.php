<?php

namespace App\Http\Controllers;

use App\Services\AiCompetencyReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiCompetencyReportController extends Controller
{
  public function __construct(
    private readonly AiCompetencyReportService $reportService,
  ) {}

  public function index(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'class_id' => ['nullable', 'integer', 'exists:classes,id'],
      'student_id' => ['nullable', 'integer', 'exists:users,id'],
    ]);

    $result = $this->reportService->listReports($validated, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  public function show(int $id): JsonResponse
  {
    $result = $this->reportService->show($id, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  public function generate(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'student_id' => ['required', 'integer', 'exists:users,id'],
      'class_id' => ['required', 'integer', 'exists:classes,id'],
      'report_type' => ['nullable', 'in:lesson,class,overall'],
      'lesson_id' => ['nullable', 'required_if:report_type,lesson', 'integer', 'exists:lessons,id'],
    ]);

    $result = $this->reportService->generate($validated, auth()->id());
    return response()->json($result['data'], $result['status']);
  }

  public function update(Request $request, int $id): JsonResponse
  {
    $validated = $request->validate([
      'strengths' => ['nullable', 'array'],
      'strengths.*' => ['nullable', 'string', 'max:1000'],
      'weaknesses' => ['nullable', 'array'],
      'weaknesses.*' => ['nullable', 'string', 'max:1000'],
      'recommendations' => ['nullable', 'array'],
      'recommendations.*' => ['nullable', 'string', 'max:1000'],
      'overall_summary' => ['nullable', 'string', 'max:10000'],
    ]);

    $result = $this->reportService->update($id, $validated, auth()->id());
    return response()->json($result['data'], $result['status']);
  }
}
