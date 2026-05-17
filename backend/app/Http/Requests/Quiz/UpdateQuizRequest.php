<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuizRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'title' => ['sometimes', 'string', 'max:255'],
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

  public function messages(): array
  {
    return [
      'title.max' => 'Tiêu đề không quá 255 ký tự',
      'description.max' => 'Mô tả không quá 2000 ký tự',
      'quiz_type.in' => 'Loại quiz không hợp lệ',
      'time_limit.min' => 'Thời gian tối thiểu 1 phút',
      'time_limit.max' => 'Thời gian tối đa 180 phút',
      'max_attempts.min' => 'Số lần thử tối thiểu là 1',
      'max_attempts.max' => 'Số lần thử tối đa là 10',
      'status.in' => 'Trạng thái không hợp lệ',
      'end_time.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu',
    ];
  }

  protected function failedValidation(Validator $validator): void
  {
    throw new HttpResponseException(
      response()->json([
        'success' => false,
        'errors' => $validator->errors()
      ], 422)
    );
  }
}
