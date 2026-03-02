<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateQuizQuestionRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'content' => ['required', 'string', 'max:5000'],
      'question_type' => ['nullable', 'in:multiple_choice,short_answer,essay'],
      'explanation' => ['nullable', 'string', 'max:2000'],
      'points' => ['nullable', 'integer', 'min:1', 'max:100'],
      'order' => ['nullable', 'integer', 'min:1'],
      'options' => ['nullable', 'array', 'min:2', 'max:6'],
      'options.*.option_text' => ['required_with:options', 'string', 'max:1000'],
      'options.*.is_correct' => ['required_with:options', 'boolean'],
      'options.*.order' => ['nullable', 'integer'],
      'options.*.explanation' => ['nullable', 'string', 'max:1000'],
    ];
  }

  public function messages(): array
  {
    return [
      'content.required' => 'Vui lòng nhập nội dung câu hỏi',
      'content.max' => 'Nội dung câu hỏi không quá 5000 ký tự',
      'question_type.in' => 'Loại câu hỏi không hợp lệ',
      'explanation.max' => 'Giải thích không quá 2000 ký tự',
      'points.min' => 'Điểm tối thiểu là 1',
      'points.max' => 'Điểm tối đa là 100',
      'options.min' => 'Cần ít nhất 2 đáp án',
      'options.max' => 'Tối đa 6 đáp án',
      'options.*.option_text.required_with' => 'Vui lòng nhập nội dung đáp án',
      'options.*.option_text.max' => 'Nội dung đáp án không quá 1000 ký tự',
      'options.*.is_correct.required_with' => 'Vui lòng đánh dấu đáp án đúng/sai',
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
