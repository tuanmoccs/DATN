<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitAssignmentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'files' => ['required', 'array', 'min:1', 'max:10'],
      'files.*' => ['file', 'max:20480', 'mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif,zip,rar'],
      'text_content' => ['nullable', 'string'],
    ];
  }

  public function messages(): array
  {
    return [
      'files.required' => 'Vui lòng đính kèm ít nhất 1 file',
      'files.min' => 'Vui lòng đính kèm ít nhất 1 file',
      'files.max' => 'Tối đa 10 file đính kèm',
      'files.*.max' => 'Mỗi file không quá 20MB',
      'files.*.mimes' => 'Loại file không được hỗ trợ (pdf, doc, docx, txt, jpg, png, gif)',
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
