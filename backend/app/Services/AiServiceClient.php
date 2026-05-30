<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    $fullPath = $this->resolveStoredFilePath($filePath);
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
   * Extract text from a file using the Python AI Service (PyMuPDF / python-docx) fallback OCR GPT V4 vision.
   * Handles PDF, DOCX, and TXT correctly including UTF-8/Vietnamese content.
   */
  public function extractFileText(string $filePath, string $fileName): string
  {

    $fullPath = $this->resolveStoredFilePath($filePath);

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
  public function gradeAssignmentSubmission(array $assignment, iterable $attachments, iterable $referenceFiles = []): array
  {
    $request = Http::timeout($this->timeout)
      ->withHeaders($this->headers());

    foreach ($referenceFiles as $file) {
      $fullPath = Storage::disk('public')->path($file->file_path);

      if (!file_exists($fullPath)) {
        Log::warning('AI Service: assignment reference file not found', [
          'file_id' => $file->id,
          'file_path' => $file->file_path,
        ]);
        continue;
      }

      $request = $request->attach(
        'reference_files',
        file_get_contents($fullPath),
        $file->file_name,
        ['Content-Type' => $file->mime_type ?: 'application/octet-stream']
      );
    }

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

  public function processRagSandboxFile($file, array $settings): array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
      ->post("{$this->baseUrl}/api/rag-sandbox/process", [
        'chunk_size' => (string) ($settings['chunk_size'] ?? 1000),
        'chunk_overlap' => (string) ($settings['chunk_overlap'] ?? 200),
        'top_k' => (string) ($settings['top_k'] ?? 5),
        'score_threshold' => (string) ($settings['score_threshold'] ?? 0.45),
        'max_context_chars' => (string) ($settings['max_context_chars'] ?? 12000),
        'low_confidence_fallback' => ($settings['low_confidence_fallback'] ?? true) ? 'true' : 'false',
      ]);

    if (!$response->successful()) {
      throw new \RuntimeException('AI Service sandbox processing failed: ' . $response->body());
    }

    return $response->json();
  }

  public function retrieveRagSandbox(string $sandboxId, string $query, array $settings): array
  {
    return $this->postRagSandbox("/api/rag-sandbox/retrieve", [
      'sandbox_id' => $sandboxId,
      'query' => $query,
      'settings' => $settings,
    ]);
  }

  public function generateRagSandboxSlides(string $sandboxId, string $query, array $settings, int $count, string $language): array
  {
    return $this->postRagSandbox("/api/rag-sandbox/slides", [
      'sandbox_id' => $sandboxId,
      'query' => $query,
      'settings' => $settings,
      'count' => $count,
      'language' => $language,
    ]);
  }

  public function generateRagSandboxQuiz(string $sandboxId, string $query, array $settings, int $count, string $language, string $difficulty): array
  {
    return $this->postRagSandbox("/api/rag-sandbox/quiz", [
      'sandbox_id' => $sandboxId,
      'query' => $query,
      'settings' => $settings,
      'count' => $count,
      'language' => $language,
      'difficulty' => $difficulty,
    ]);
  }

  public function deleteRagSandbox(string $sandboxId): array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->delete("{$this->baseUrl}/api/rag-sandbox/{$sandboxId}");

    return $response->successful() ? $response->json() : ['success' => false];
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
   * Sinh hình ảnh cho slide thông qua Python AI Service.
   */
  public function generateSlideImage(string $prompt): ?string
  {
    $response = $this->invokeAgent('image', [
      'prompt' => $prompt,
    ]);

    $result = $response['result'] ?? null;
    if (!$result || !($result['success'] ?? false)) {
      Log::warning('AI Service: slide image generation failed', [
        'prompt' => mb_substr($prompt, 0, 200),
        'message' => $result['message'] ?? 'Unknown error',
      ]);
      return null;
    }

    $imageUrl = $result['image_url'] ?? null;
    if (empty($imageUrl)) {
      return null;
    }

    try {
      if (str_starts_with($imageUrl, 'data:image')) {
        $imageContent = $this->decodeDataImage($imageUrl);
      } else {
        $imageContent = Http::timeout($this->timeout)->get($imageUrl)->body();
      }

      $fileName = 'slides/' . Str::uuid() . '.png';
      Storage::disk('public')->put($fileName, $imageContent);

      return '/storage/' . $fileName;
    } catch (\Exception $e) {
      Log::warning('AI Service: slide image download failed', [
        'error' => $e->getMessage(),
        'image_url' => $imageUrl,
      ]);
      return null;
    }
  }

  /**
   * Sinh hình ảnh cho nhiều slide thông qua Python AI Service.
   */
  public function generateSlideImages(array $slides): array
  {
    $results = [];

    foreach ($slides as $index => $slide) {
      if (empty($slide['image_prompt'])) {
        continue;
      }

      if ($index > 0) {
        sleep(2);
      }

      $results[$slide['order']] = $this->generateSlideImage($slide['image_prompt']);
    }

    return $results;
  }

  /**
   * Sinh báo cáo năng lực học sinh từ dữ liệu quiz và assignment qua Python AI Service.
   */
  public function generateCompetencyReport(array $payload): ?array
  {
    $response = $this->invokeAgent('competency_report', $payload);
    return $response['result'] ?? null;
  }
   /**
   * Kiểm tra AI Service có hoạt động không
   */
  /**
   * Student lesson-aware assistant. Payload quiz context must be sanitized and
   * must not contain answer keys or quiz explanations.
   */
  public function chatWithLesson(array $payload): ?array
  {
    $response = $this->invokeAgent('chat', $payload);
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

  private function postRagSandbox(string $path, array $payload): array
  {
    $response = Http::timeout($this->timeout)
      ->withHeaders($this->headers())
      ->post("{$this->baseUrl}{$path}", $payload);

    if (!$response->successful()) {
      throw new \RuntimeException('AI Service sandbox request failed: ' . $response->body());
    }

    return $response->json();
  }

  private function resolveStoredFilePath(string $filePath): string
  {
    $publicPath = Storage::disk('public')->path($filePath);
    if (file_exists($publicPath)) {
      return $publicPath;
    }

    $localPath = Storage::disk('local')->path($filePath);
    if (file_exists($localPath)) {
      return $localPath;
    }

    throw new \RuntimeException("Stored file not found: {$filePath}");
  }

  private function decodeDataImage(string $dataUrl): string
  {
    if (!preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,(.+)$/', $dataUrl, $matches)) {
      throw new \RuntimeException('Invalid image data URL returned by AI service.');
    }

    $decoded = base64_decode($matches[1], true);
    if ($decoded === false) {
      throw new \RuntimeException('Invalid base64 image returned by AI service.');
    }

    return $decoded;
  }
}
