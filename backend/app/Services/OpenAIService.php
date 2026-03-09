<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIService
{
  private $client;
  private int $maxRetries = 3;
  private int $baseDelay = 5; // seconds

  public function __construct()
  {
    $this->client = OpenAI::client(config('openai.api_key'));
  }

  /**
   * Gọi OpenAI API với retry + exponential backoff khi bị rate limit
   */
  private function chatWithRetry(array $params): string
  {
    $lastException = null;

    for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
      try {
        $response = $this->client->chat()->create($params);
        return $response->choices[0]->message->content;
      } catch (\Exception $e) {
        $lastException = $e;
        $message = $e->getMessage();

        // Kiểm tra rate limit error (HTTP 429 hoặc message chứa 'rate limit')
        $isRateLimit = str_contains(strtolower($message), 'rate limit')
          || str_contains($message, '429')
          || str_contains(strtolower($message), 'too many requests')
          || str_contains(strtolower($message), 'quota');

        if ($isRateLimit && $attempt < $this->maxRetries) {
          $delay = $this->baseDelay * pow(2, $attempt - 1); // 5s, 10s, 20s
          Log::warning("OpenAI rate limit hit, retrying in {$delay}s (attempt {$attempt}/{$this->maxRetries})", [
            'error' => $message,
          ]);
          sleep($delay);
          continue;
        }

        // Không phải rate limit hoặc đã hết retry → throw
        throw $e;
      }
    }

    throw $lastException;
  }

  /**
   * Sinh slide bài giảng bằng tiếng Anh từ nội dung bất kỳ ngôn ngữ
   */
  public function generatePresentationSlides(string $content, string $title, int $slideCount = 10): array
  {
    try {
      $prompt = $this->buildSlidePrompt($content, $title, $slideCount);

      $result = $this->chatWithRetry([
        'model' => config('openai.model', 'gpt-4o-mini'),
        'messages' => [
          [
            'role' => 'system',
            'content' => 'You are an expert educational content creator. You create clear, well-structured presentation slides for teaching. Always respond in English regardless of the input language. Return ONLY valid JSON, no markdown formatting.'
          ],
          [
            'role' => 'user',
            'content' => $prompt,
          ],
        ],
        'max_tokens' => config('openai.max_tokens', 4096),
        'temperature' => config('openai.temperature', 0.7),
      ]);

      // Clean up potential markdown code block wrapping
      $result = preg_replace('/^```(?:json)?\s*/', '', $result);
      $result = preg_replace('/\s*```$/', '', $result);

      $slides = json_decode($result, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('OpenAI slide generation JSON parse error', [
          'error' => json_last_error_msg(),
          'raw_response' => $result,
        ]);
        throw new \Exception('Không thể parse kết quả từ AI: ' . json_last_error_msg());
      }

      return $slides;
    } catch (\Exception $e) {
      Log::error('OpenAI slide generation failed', [
        'error' => $e->getMessage(),
        'title' => $title,
      ]);
      throw $e;
    }
  }

  /**
   * Sinh câu hỏi quiz bằng tiếng Anh từ nội dung bài học
   */
  public function generateQuizQuestions(string $content, string $title, int $questionCount = 5): array
  {
    try {
      $prompt = $this->buildQuizPrompt($content, $title, $questionCount);

      $result = $this->chatWithRetry([
        'model' => config('openai.model', 'gpt-4o-mini'),
        'messages' => [
          [
            'role' => 'system',
            'content' => 'You are an expert educational assessment creator. You create clear, well-structured quiz questions to test student understanding. Always respond in English regardless of the input language. Return ONLY valid JSON, no markdown formatting.'
          ],
          [
            'role' => 'user',
            'content' => $prompt,
          ],
        ],
        'max_tokens' => config('openai.max_tokens', 4096),
        'temperature' => config('openai.temperature', 0.7),
      ]);

      // Clean up potential markdown code block wrapping
      $result = preg_replace('/^```(?:json)?\s*/', '', $result);
      $result = preg_replace('/\s*```$/', '', $result);

      $questions = json_decode($result, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('OpenAI quiz generation JSON parse error', [
          'error' => json_last_error_msg(),
          'raw_response' => $result,
        ]);
        throw new \Exception('Không thể parse kết quả từ AI: ' . json_last_error_msg());
      }

      return $questions;
    } catch (\Exception $e) {
      Log::error('OpenAI quiz generation failed', [
        'error' => $e->getMessage(),
        'title' => $title,
      ]);
      throw $e;
    }
  }

  /**
   * Sinh hình ảnh cho slide bằng DALL-E, download và lưu vào storage
   */
  public function generateSlideImage(string $imagePrompt): ?string
  {
    if (!config('openai.generate_images', true)) {
      return null;
    }

    try {
      $response = $this->client->images()->create([
        'model' => config('openai.image_model', 'dall-e-3'),
        'prompt' => $imagePrompt,
        'n' => 1,
        'size' => config('openai.image_size', '1792x1024'),
        'quality' => config('openai.image_quality', 'standard'),
        'response_format' => 'url',
      ]);

      $imageUrl = $response->data[0]->url;

      // Download image và lưu vào storage/app/public/slides/
      $imageContent = Http::timeout(30)->get($imageUrl)->body();

      $fileName = 'slides/' . Str::uuid() . '.png';
      Storage::disk('public')->put($fileName, $imageContent);

      // Trả về URL public để frontend hiển thị
      return '/storage/' . $fileName;
    } catch (\Exception $e) {
      Log::warning('DALL-E image generation failed', [
        'error' => $e->getMessage(),
        'prompt' => substr($imagePrompt, 0, 200),
      ]);
      return null;
    }
  }

  /**
   * Sinh hình ảnh cho nhiều slides (batch), có delay giữa các lần gọi
   */
  public function generateSlideImages(array $slides): array
  {
    $results = [];

    foreach ($slides as $index => $slide) {
      if (!empty($slide['image_prompt'])) {
        // Delay giữa các lần gọi để tránh rate limit
        if ($index > 0) {
          sleep(2);
        }

        $imageUrl = $this->generateSlideImage($slide['image_prompt']);
        $results[$slide['order']] = $imageUrl;
      }
    }

    return $results;
  }

  /**
   * Trích xuất text từ file upload (PDF, DOCX, TXT, etc.)
   */
  public function extractTextFromFile(string $filePath, string $mimeType): string
  {
    $fullPath = storage_path('app/' . $filePath);

    if (!file_exists($fullPath)) {
      throw new \Exception('File không tồn tại: ' . $filePath);
    }

    return match (true) {
      str_contains($mimeType, 'text/plain') => file_get_contents($fullPath),
      str_contains($mimeType, 'pdf') => $this->extractTextFromPdf($fullPath),
      str_contains($mimeType, 'wordprocessingml') ||
        str_contains($mimeType, 'msword') => $this->extractTextFromDocx($fullPath),
      str_contains($mimeType, 'presentationml') ||
        str_contains($mimeType, 'powerpoint') => $this->extractTextFromPptx($fullPath),
      default => throw new \Exception('Không hỗ trợ loại file này: ' . $mimeType),
    };
  }

  /**
   * Extract text from PDF using simple method
   */
  private function extractTextFromPdf(string $filePath): string
  {
    // Simple PDF text extraction - read raw content
    $content = file_get_contents($filePath);

    // Try to extract text between stream/endstream
    $text = '';
    $segments = [];

    // Extract all text between BT and ET markers
    if (preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches)) {
      foreach ($matches[1] as $match) {
        // Extract text within parentheses (Tj operator)
        if (preg_match_all('/\((.*?)\)/s', $match, $textMatches)) {
          $segments = array_merge($segments, $textMatches[1]);
        }
      }
      $text = implode(' ', $segments);
    }

    // If no text extracted, try alternate method
    if (empty(trim($text))) {
      // Use content as-is, stripping binary data
      $text = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $content);
      $text = preg_replace('/\s+/', ' ', $text);
      $text = substr($text, 0, 10000); // Limit size
    }

    if (empty(trim($text))) {
      throw new \Exception('Không thể trích xuất nội dung từ file PDF. Vui lòng nhập nội dung bài học trực tiếp.');
    }

    return $text;
  }

  /**
   * Extract text from DOCX
   */
  private function extractTextFromDocx(string $filePath): string
  {
    $zip = new \ZipArchive();
    if ($zip->open($filePath) === true) {
      $content = $zip->getFromName('word/document.xml');
      $zip->close();

      if ($content !== false) {
        // Strip XML tags to get plain text
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
      }
    }

    throw new \Exception('Không thể đọc file DOCX. Vui lòng nhập nội dung bài học trực tiếp.');
  }

  /**
   * Extract text from PPTX
   */
  private function extractTextFromPptx(string $filePath): string
  {
    $zip = new \ZipArchive();
    if ($zip->open($filePath) === true) {
      $text = '';
      $slideNum = 1;

      while (true) {
        $slideContent = $zip->getFromName("ppt/slides/slide{$slideNum}.xml");
        if ($slideContent === false) break;

        $slideText = strip_tags($slideContent);
        $slideText = preg_replace('/\s+/', ' ', $slideText);
        $text .= trim($slideText) . "\n\n";
        $slideNum++;
      }

      $zip->close();

      if (!empty(trim($text))) {
        return $text;
      }
    }

    throw new \Exception('Không thể đọc file PPTX. Vui lòng nhập nội dung bài học trực tiếp.');
  }

  /**
   * Build prompt for slide generation
   */
  private function buildSlidePrompt(string $content, string $title, int $slideCount): string
  {
    return <<<PROMPT
You are an expert instructional designer and presentation creator.

Create a professional teaching presentation with exactly {$slideCount} slides based on the lesson below.

Lesson Title: {$title}

Lesson Content:
{$content}

========================
STRICT REQUIREMENTS
========================

1. ALL slides must be written in ENGLISH only.
2. Create EXACTLY {$slideCount} slides (no more, no less).
3. Slide 1 MUST be a title slide.
4. Last slide MUST be a summary/conclusion slide.
5. Content must be clear, structured, and pedagogically effective.
6. Each slide must contain meaningful teaching content (not too short).
7. Include speaker notes with detailed explanation for the teacher.
8. Add simple visual images to 2–3 appropriate slides (not all slides).
9. Images should be simple, clean, educational-style illustrations (not complex artwork).

========================
IMAGE RULES
========================

- Exactly 2–3 slides (excluding title and summary) should include images.
- For slides with images, provide a DALL-E image generation prompt in "image_prompt".
- The image_prompt must describe a clear, simple, flat-style educational illustration.
- Image prompts should follow this pattern: "A clean flat illustration of [subject], minimal style, white background, educational diagram, no text"
- For slides without images, set "image_prompt" to null.
- If a slide contains an image, use layout: "image" or "two_column".
- Do NOT overload slides with too much text when they contain images.
- Image prompts must be in English and descriptive enough for AI image generation.

========================
RETURN FORMAT (STRICT JSON ARRAY ONLY)
========================

Return ONLY a valid JSON array in this exact structure:

[
  {
    "order": 1,
    "title": "Slide title",
    "content": "Main slide content (use \\n for line breaks, bullet points start with •)",
    "notes": "Detailed speaker notes explaining the slide",
    "layout": "title",
    "image_prompt": null
  },
  {
    "order": 2,
    "title": "Concept explanation",
    "content": "• Key point 1\\n• Key point 2\\n• Key point 3",
    "notes": "Clear teaching explanation with examples",
    "layout": "image",
    "image_prompt": "Simple flat illustration of ... , minimal style, white background, educational diagram"
  }
]

========================
AVAILABLE LAYOUTS
========================

"title"         → Only for first slide
"content"       → Standard text slide
"bullet_points" → Bullet-focused slide
"two_column"    → Text + image side by side
"image"         → Image-focused slide with short text

========================
QUALITY GUIDELINES
========================

- Slides must progressively build knowledge.
- Avoid repeating the same wording.
- Keep slide text concise but informative.
- Put detailed explanations inside "notes".
- Make the presentation engaging and professional.

IMPORTANT:
- Return ONLY the JSON array.
- Do NOT include markdown.
- Do NOT include explanations.
- Do NOT wrap in code blocks.

PROMPT;
  }

  /**
   * Build prompt for quiz question generation
   */
  private function buildQuizPrompt(string $content, string $title, int $questionCount): string
  {
    return <<<PROMPT
Based on the following lesson content, create {$questionCount} quiz questions to test student understanding.

Lesson Title: {$title}
Lesson Content:
{$content}

Requirements:
1. ALL questions and answers must be in ENGLISH, even if the source content is in another language
2. Create exactly {$questionCount} questions
3. Each question should be multiple choice with 4 options
4. Only ONE option should be correct per question
5. Include an explanation for why the correct answer is right
6. Questions should cover different aspects of the lesson
7. Questions should range from easy to medium difficulty

Return a JSON array with this exact structure:
[
  {
    "order": 1,
    "content": "The question text here?",
    "explanation": "Explanation of why the correct answer is right",
    "points": 10,
    "question_type": "multiple_choice",
    "options": [
      {
        "order": 1,
        "option_text": "Option A text",
        "is_correct": false,
        "explanation": ""
      },
      {
        "order": 2,
        "option_text": "Option B text",
        "is_correct": true,
        "explanation": "This is correct because..."
      },
      {
        "order": 3,
        "option_text": "Option C text",
        "is_correct": false,
        "explanation": ""
      },
      {
        "order": 4,
        "option_text": "Option D text",
        "is_correct": false,
        "explanation": ""
      }
    ]
  }
]
PROMPT;
  }

  /**
   * AI chấm điểm bài tập tự luận
   * Đọc nội dung bài tập + đáp án học sinh → gợi ý điểm + nhận xét
   */
  public function gradeAssignmentSubmission(
    string $assignmentTitle,
    string $assignmentDescription,
    string $assignmentInstructions,
    int $maxScore,
    string $studentAnswer
  ): array {
    try {
      $prompt = $this->buildGradingPrompt(
        $assignmentTitle,
        $assignmentDescription,
        $assignmentInstructions,
        $maxScore,
        $studentAnswer
      );

      $result = $this->chatWithRetry([
        'model' => config('openai.model', 'gpt-4o-mini'),
        'messages' => [
          [
            'role' => 'system',
            'content' => 'You are an experienced and fair teacher who grades student assignments. You provide constructive, detailed feedback. Respond ONLY with valid JSON, no markdown formatting. Always respond in the same language as the assignment content. If the assignment is in Vietnamese, respond in Vietnamese.',
          ],
          [
            'role' => 'user',
            'content' => $prompt,
          ],
        ],
        'max_tokens' => config('openai.max_tokens', 4096),
        'temperature' => 0.3, // Lower temperature for more consistent grading
      ]);

      // Clean up potential markdown code block wrapping
      $result = preg_replace('/^```(?:json)?\s*/', '', $result);
      $result = preg_replace('/\s*```$/', '', $result);

      $grading = json_decode($result, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('OpenAI grading JSON parse error', [
          'error' => json_last_error_msg(),
          'raw_response' => $result,
        ]);
        throw new \Exception('Không thể parse kết quả chấm điểm từ AI: ' . json_last_error_msg());
      }

      // Validate score is within range
      $suggestedScore = min($maxScore, max(0, $grading['suggested_score'] ?? 0));

      return [
        'suggested_score' => $suggestedScore,
        'max_score' => $maxScore,
        'percentage' => $maxScore > 0 ? round(($suggestedScore / $maxScore) * 100, 2) : 0,
        'feedback' => $grading['feedback'] ?? '',
        'strengths' => $grading['strengths'] ?? [],
        'weaknesses' => $grading['weaknesses'] ?? [],
        'suggestions' => $grading['suggestions'] ?? [],
        'grade_letter' => $grading['grade_letter'] ?? '',
      ];
    } catch (\Exception $e) {
      Log::error('OpenAI grading failed', [
        'error' => $e->getMessage(),
        'assignment' => $assignmentTitle,
      ]);
      throw $e;
    }
  }

  /**
   * Trích xuất text từ file hình ảnh sử dụng GPT-4 Vision
   */
  public function extractTextFromImage(string $filePath): string
  {
    $fullPath = storage_path('app/' . $filePath);

    if (!file_exists($fullPath)) {
      throw new \Exception('File không tồn tại: ' . $filePath);
    }

    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);

    try {
      $response = $this->client->chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
          [
            'role' => 'user',
            'content' => [
              [
                'type' => 'text',
                'text' => 'Please extract all text content from this image. Return only the text content, preserving the original formatting and language as much as possible. If there are handwritten assignments, transcribe them accurately.',
              ],
              [
                'type' => 'image_url',
                'image_url' => [
                  'url' => "data:{$mimeType};base64,{$imageData}",
                ],
              ],
            ],
          ],
        ],
        'max_tokens' => 4096,
      ]);

      return $response->choices[0]->message->content;
    } catch (\Exception $e) {
      Log::error('Image text extraction failed', [
        'error' => $e->getMessage(),
        'file' => $filePath,
      ]);
      throw new \Exception('Không thể đọc nội dung từ hình ảnh: ' . $e->getMessage());
    }
  }

  /**
   * Build prompt for AI grading
   */
  private function buildGradingPrompt(
    string $title,
    string $description,
    string $instructions,
    int $maxScore,
    string $studentAnswer
  ): string {
    return <<<PROMPT
You are grading a student's assignment submission. Please evaluate it carefully and provide a fair score with detailed feedback.

========================
ASSIGNMENT INFORMATION
========================
Title: {$title}
Description: {$description}
Instructions: {$instructions}
Maximum Score: {$maxScore}

========================
STUDENT'S ANSWER
========================
{$studentAnswer}

========================
GRADING CRITERIA
========================
1. Understanding of the topic (25%)
2. Completeness of the answer (25%)
3. Accuracy of information (25%)
4. Clarity and organization (15%)
5. Creativity and critical thinking (10%)

========================
RETURN FORMAT (STRICT JSON ONLY)
========================
Return ONLY a valid JSON object with this exact structure:

{
  "suggested_score": <number between 0 and {$maxScore}>,
  "feedback": "Detailed overall feedback about the submission",
  "strengths": ["Strength 1", "Strength 2"],
  "weaknesses": ["Weakness 1", "Weakness 2"],
  "suggestions": ["Improvement suggestion 1", "Improvement suggestion 2"],
  "grade_letter": "A/B/C/D/F"
}

IMPORTANT:
- Be fair and constructive
- The score must be between 0 and {$maxScore}
- Provide specific, actionable feedback
- Respond in the same language as the assignment content
- Return ONLY JSON, no markdown or explanations
PROMPT;
  }
}
