<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateQuizRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string', 'max:2000'],
      'quiz_type' => ['nullable', 'in:online,offline'],
      'time_limit' => ['nullable', 'integer', 'min:1', 'max:180'],
      'shuffle_questions' => ['nullable', 'boolean'],
      'shuffle_options' => ['nullable', 'boolean'],
      'show_answers_after' => ['nullable', 'boolean'],
      'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
      'status' => ['nullable', 'in:draft,published'],
      'start_time' => ['nullable', 'date'],
      'end_time' => ['nullable', 'date', 'after:start_time'],
    ];
  }

  protected function failedValidation(Validator $validator): void
  {
    throw new HttpResponseException(
      response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422)
    );
  }
}
