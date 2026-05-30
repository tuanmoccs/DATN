<?php

namespace App\Http\Controllers;

use App\Services\AiServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RagSandboxController extends Controller
{
  public function __construct(
    private readonly AiServiceClient $aiServiceClient
  ) {}

  public function process(Request $request): JsonResponse
  {
    $data = $this->validateSandboxRequest($request, includeFile: true);
    $result = $this->aiServiceClient->processRagSandboxFile($request->file('file'), $data['settings']);
    return response()->json(['success' => true, 'data' => $result]);
  }

  public function retrieve(Request $request): JsonResponse
  {
    $data = $this->validateSandboxRequest($request);
    $result = $this->aiServiceClient->retrieveRagSandbox(
      $data['sandbox_id'],
      $data['query'] ?? '',
      $data['settings']
    );
    return response()->json(['success' => true, 'data' => $result]);
  }

  public function slides(Request $request): JsonResponse
  {
    $data = $this->validateSandboxRequest($request);
    $result = $this->aiServiceClient->generateRagSandboxSlides(
      $data['sandbox_id'],
      $data['query'] ?? '',
      $data['settings'],
      (int) ($data['count'] ?? 5),
      $data['language'] ?? 'Vietnamese'
    );
    return response()->json(['success' => true, 'data' => $result]);
  }

  public function quiz(Request $request): JsonResponse
  {
    $data = $this->validateSandboxRequest($request);
    $result = $this->aiServiceClient->generateRagSandboxQuiz(
      $data['sandbox_id'],
      $data['query'] ?? '',
      $data['settings'],
      (int) ($data['count'] ?? 5),
      $data['language'] ?? 'Vietnamese',
      $data['difficulty'] ?? 'medium'
    );
    return response()->json(['success' => true, 'data' => $result]);
  }

  public function destroy(string $sandboxId): JsonResponse
  {
    $result = $this->aiServiceClient->deleteRagSandbox($sandboxId);
    return response()->json(['success' => true, 'data' => $result]);
  }

  private function validateSandboxRequest(Request $request, bool $includeFile = false): array
  {
    $rules = [
      'sandbox_id' => [$includeFile ? 'nullable' : 'required', 'string', 'max:120'],
      'query' => ['nullable', 'string', 'max:2000'],
      'settings' => ['required', 'array'],
      'settings.chunk_size' => ['nullable', 'integer', 'min:300', 'max:3000'],
      'settings.chunk_overlap' => ['nullable', 'integer', 'min:0', 'max:1000'],
      'settings.top_k' => ['nullable', 'integer', 'min:1', 'max:12'],
      'settings.score_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
      'settings.max_context_chars' => ['nullable', 'integer', 'min:1000', 'max:30000'],
      'settings.low_confidence_fallback' => ['nullable', 'boolean'],
      'count' => ['nullable', 'integer', 'min:1', 'max:30'],
      'language' => ['nullable', 'string', 'max:40'],
      'difficulty' => ['nullable', 'in:easy,medium,hard'],
    ];

    if ($includeFile) {
      $rules['file'] = ['required', 'file', 'max:20480', 'mimes:pdf,docx,txt'];
    }

    return $request->validate($rules);
  }
}
