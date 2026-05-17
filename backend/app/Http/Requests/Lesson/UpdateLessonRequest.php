<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLessonRequest extends FormRequest
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
      'objectives' => ['nullable', 'string', 'max:2000'],
      'content_text' => ['nullable', 'string'],
      'status' => ['nullable', 'in:draft,published'],
      'order' => ['nullable', 'integer', 'min:1'],
      'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,txt'],
    ];
  }

  public function messages(): array
  {
    return [
      'title.max' => 'Tiêu đề không quá 255 ký tự',
      'description.max' => 'Mô tả không quá 2000 ký tự',
      'objectives.max' => 'Mục tiêu không quá 2000 ký tự',
      'status.in' => 'Trạng thái không hợp lệ',
      'order.integer' => 'Thứ tự phải là số nguyên',
      'file.max' => 'File không quá 20MB',
      'file.mimes' => 'Chỉ hỗ trợ file PDF, DOC, DOCX, PPT, PPTX, TXT',
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
