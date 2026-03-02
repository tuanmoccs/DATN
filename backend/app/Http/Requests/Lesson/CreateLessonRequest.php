<?php

namespace App\Http\Requests\Lesson;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateLessonRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'class_id' => ['required', 'integer', 'exists:classes,id'],
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string', 'max:2000'],
      'objectives' => ['nullable', 'string', 'max:2000'],
      'content_text' => ['nullable', 'string'],
      'status' => ['nullable', 'in:draft,published'],
      'file' => ['nullable', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,txt'],
      'generate_slides' => ['nullable', 'boolean'],
      'generate_quiz' => ['nullable', 'boolean'],
      'slide_count' => ['nullable', 'integer', 'min:3', 'max:30'],
      'question_count' => ['nullable', 'integer', 'min:1', 'max:20'],
    ];
  }

  public function messages(): array
  {
    return [
      'class_id.required' => 'Vui lòng chọn lớp học',
      'class_id.exists' => 'Lớp học không tồn tại',
      'title.required' => 'Vui lòng nhập tiêu đề bài học',
      'title.max' => 'Tiêu đề không quá 255 ký tự',
      'description.max' => 'Mô tả không quá 2000 ký tự',
      'objectives.max' => 'Mục tiêu không quá 2000 ký tự',
      'status.in' => 'Trạng thái không hợp lệ',
      'file.max' => 'File không quá 20MB',
      'file.mimes' => 'Chỉ hỗ trợ file PDF, DOC, DOCX, PPT, PPTX, TXT',
      'slide_count.min' => 'Số slide tối thiểu là 3',
      'slide_count.max' => 'Số slide tối đa là 30',
      'question_count.min' => 'Số câu hỏi tối thiểu là 1',
      'question_count.max' => 'Số câu hỏi tối đa là 20',
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
