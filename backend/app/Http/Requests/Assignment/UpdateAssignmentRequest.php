<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAssignmentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'title' => ['nullable', 'string', 'max:255'],
      'description' => ['nullable', 'string', 'max:5000'],
      'instructions' => ['nullable', 'string'],
      'due_date' => ['nullable', 'date'],
      'max_score' => ['nullable', 'integer', 'min:1', 'max:1000'],
      'allow_late_submission' => ['nullable', 'boolean'],
      'late_penalty' => ['nullable', 'integer', 'min:0', 'max:100'],
      'submission_type' => ['nullable', 'in:file,text,both'],
      'status' => ['nullable', 'in:draft,published,closed,archived'],
      'files' => ['nullable', 'array', 'max:10'],
      'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,txt,jpg,jpeg,png,gif,zip,rar'],
      'remove_files' => ['nullable', 'array'],
      'remove_files.*' => ['integer', 'exists:assignment_files,id'],
    ];
  }

  public function messages(): array
  {
    return [
      'title.max' => 'Tiêu đề không quá 255 ký tự',
      'description.max' => 'Mô tả không quá 5000 ký tự',
      'max_score.min' => 'Điểm tối đa phải ít nhất là 1',
      'max_score.max' => 'Điểm tối đa không quá 1000',
      'late_penalty.min' => 'Phần trăm trừ điểm không được âm',
      'late_penalty.max' => 'Phần trăm trừ điểm không quá 100',
      'submission_type.in' => 'Loại nộp bài không hợp lệ',
      'status.in' => 'Trạng thái không hợp lệ',
      'files.max' => 'Tối đa 10 file đính kèm',
      'files.*.max' => 'Mỗi file không quá 20MB',
      'files.*.mimes' => 'Loại file không được hỗ trợ',
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
