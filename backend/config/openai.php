<?php

return [
  'api_key' => env('OPENAI_API_KEY'),
  'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
  'max_tokens' => env('OPENAI_MAX_TOKENS', 4096),
  'temperature' => env('OPENAI_TEMPERATURE', 0.7),

  // Image generation settings
  'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
  'image_size' => env('OPENAI_IMAGE_SIZE', '1536x1024'),
  'image_quality' => env('OPENAI_IMAGE_QUALITY', 'medium'),
  'generate_images' => env('OPENAI_GENERATE_IMAGES', false),
];
