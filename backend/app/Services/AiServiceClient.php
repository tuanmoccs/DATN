<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiServiceClient
{
  private string $baseUrl;
  private string $secret;
  private int $timeout;

  public function __construct()
  {
    $this->baseUrl = rtrim(config('services.ai_service.url'), '/');
    $this->secret = config('services.ai_service.secret');
    $this->timeout = config('services.ai_service.timeout');
  }

  public function invokeAgent(string $agent, array $payload): ?array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->post("{$this->baseUrl}/api/agents/execute", [
        'agent' => $agent,
        'payload' => $payload,
      ]);

    if (!$response->successful()) {
      Log::error('AI Service: agent execution failed', [
        'agent' => $agent,
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      return null;
    }

    return $response->json();
  }

  /**
   * Gửi nội dung text đã trích xuất tới Python AI Service để chunking + embedding
   */
  public function processDocumentText(int $lessonId, string $text): array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->post("{$this->baseUrl}/api/documents/process-text", [
        'lesson_id' => $lessonId,
        'text' => $text,
      ]);

    if (!$response->successful()) {
      Log::error('AI Service: document processing failed', [
        'lesson_id' => $lessonId,
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      throw new \RuntimeException('AI Service document processing failed: ' . $response->body());
    }

    return $response->json();
  }

  /**
   * Upload file trực tiếp tới Python AI Service
   */
  public function processDocumentFile(int $lessonId, string $filePath, string $fileName): array
  {
    $fullPath = storage_path('app/public/' . $filePath);

    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->attach('file', file_get_contents($fullPath), $fileName)
      ->post("{$this->baseUrl}/api/documents/process", [
        'lesson_id' => $lessonId,
      ]);

    if (!$response->successful()) {
      Log::error('AI Service: file processing failed', [
        'lesson_id' => $lessonId,
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      throw new \RuntimeException('AI Service file processing failed: ' . $response->body());
    }

    return $response->json();
  }

  /**
   * Extract text from a file using the Python AI Service (PyMuPDF / python-docx).
   * Handles PDF, DOCX, and TXT correctly including UTF-8/Vietnamese content.
   */
  public function extractFileText(string $filePath, string $fileName): string
  {
    $fullPath = storage_path('app/public/' . $filePath);

    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->attach('file', file_get_contents($fullPath), $fileName)
      ->post("{$this->baseUrl}/api/documents/extract");

    if (!$response->successful()) {
      Log::error('AI Service: text extraction failed', [
        'file' => $fileName,
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      throw new \RuntimeException('AI Service text extraction failed: ' . $response->body());
    }

    return (string) ($response->json('text') ?? '');
  }

  /**
   * Send an assignment submission to the Python AI Service for extraction + grading.
   */
  public function gradeAssignmentSubmission(array $assignment, iterable $attachments): array
  {
    $request = Http::timeout($this->timeout)
      ->withHeaders($this->headers());

    foreach ($attachments as $attachment) {
      $fullPath = Storage::disk('public')->path($attachment->file_path);

      if (!file_exists($fullPath)) {
        Log::warning('AI Service: assignment attachment file not found', [
          'attachment_id' => $attachment->id,
          'file_path' => $attachment->file_path,
        ]);
        continue;
      }

      $request = $request->attach(
        'files',
        file_get_contents($fullPath),
        $attachment->file_name,
        ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream']
      );
    }

    $response = $request->post("{$this->baseUrl}/api/assignments/grade", [
      'assignment_title' => $assignment['title'],
      'assignment_description' => $assignment['description'] ?? '',
      'assignment_instructions' => $assignment['instructions'] ?? '',
      'max_score' => (string) $assignment['max_score'],
    ]);

    if (!$response->successful()) {
      Log::error('AI Service: assignment grading failed', [
        'assignment_title' => $assignment['title'],
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      throw new \RuntimeException('AI Service assignment grading failed: ' . $response->body());
    }

    return $response->json();
  }

  /**
   * Xóa document chunks trong vector DB
   */
  public function deleteDocumentChunks(int $lessonId): array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->delete("{$this->baseUrl}/api/documents/delete", [
        'lesson_id' => $lessonId,
      ]);

    if (!$response->successful()) {
      Log::warning('AI Service: delete chunks failed', [
        'lesson_id' => $lessonId,
        'status' => $response->status(),
      ]);
      return ['success' => false];
    }

    return $response->json();
  }

  /**
   * Sinh slides qua RAG pipeline
   */
  public function generateSlides(int $lessonId, int $numSlides = 10, string $language = 'English'): ?array
  {
    $response = $this->invokeAgent('slides', [
      'lesson_id' => $lessonId,
      'num_slides' => $numSlides,
      'language' => $language,
    ]);

    return $response['result'] ?? null;
  }

  /**
   * Sinh quiz qua RAG pipeline
   */
  public function generateQuiz(int $lessonId, int $numQuestions = 10, string $language = 'English', string $difficulty = 'medium'): ?array
  {
    $response = $this->invokeAgent('quiz', [
      'lesson_id' => $lessonId,
      'num_questions' => $numQuestions,
      'language' => $language,
      'difficulty' => $difficulty,
    ]);

    return $response['result'] ?? null;
  }

  /**
   * Kiểm tra AI Service có hoạt động không
   */
  /**
   * Sinh báo cáo năng lực học sinh từ dữ liệu quiz và assignment qua Python AI Service.
   */
  public function generateCompetencyReport(array $payload): ?array
  {
    $response = $this->invokeAgent('competency_report', $payload);
    return $response['result'] ?? null;
  }

  public function healthCheck(): bool
  {
    try {
      $response = Http::timeout(5)->get("{$this->baseUrl}/health");
      return $response->successful();
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Gọi AI service để lấy gợi ý autocomplete cho giáo án
   */
  public function getAutocompleteSuggestion(string $text, ?int $lessonId = null): array
  {
    try {
      $payload = ['text' => $text];
      if ($lessonId) {
        $payload['lesson_id'] = $lessonId;
      }

      $response = $this->invokeAgent('autocomplete', $payload);

      if (!$response) {
        Log::warning('AI Service: autocomplete suggestion failed');
        return ['suggestion' => ''];
      }

      return $response['result'] ?? ['suggestion' => ''];
    } catch (\Exception $e) {
      Log::warning('AI Service: autocomplete request failed', [
        'error' => $e->getMessage(),
      ]);
      return ['suggestion' => ''];
    }
  }

  private function headers(): array
  {
    return [
      'Accept' => 'application/json',
      'X-API-Secret' => $this->secret,
    ];
  }
}
