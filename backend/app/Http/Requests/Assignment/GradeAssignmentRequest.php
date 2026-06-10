<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GradeAssignmentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'score' => ['required', 'numeric', 'min:0'],
      'feedback' => ['nullable', 'string', 'max:10000'],
      'ai_review' => ['nullable', 'array'],
      'ai_review.reviewed_score' => ['nullable', 'numeric', 'min:0'],
      'ai_review.decisions' => ['nullable', 'array'],
      'ai_review.decisions.*' => ['required', 'in:accepted,rejected'],
    ];
  }

  public function messages(): array
  {
    return [
      'score.required' => 'Vui lòng nhập điểm',
      'score.numeric' => 'Điểm phải là số',
      'score.min' => 'Điểm không được âm',
      'feedback.max' => 'Nhận xét không quá 10000 ký tự',
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
