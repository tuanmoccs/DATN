<?php

return [
  'api_key' => env('OPENAI_API_KEY'),
  'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
  'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
  'temperature' => env('OPENAI_TEMPERATURE', 0.7),

  // Image generation settings (DALL-E)
  'image_model' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
  'image_size' => env('OPENAI_IMAGE_SIZE', '1792x1024'),
  'image_quality' => env('OPENAI_IMAGE_QUALITY', 'standard'),
  'generate_images' => env('OPENAI_GENERATE_IMAGES', false),
];
