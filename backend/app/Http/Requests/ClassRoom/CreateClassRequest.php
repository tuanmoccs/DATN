<?php

namespace App\Http\Requests\ClassRoom;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateClassRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string', 'max:1000'],
      'semester' => ['nullable', 'string', 'max:50'],
      'max_students' => ['nullable', 'integer', 'min:1', 'max:500'],
      'status' => ['nullable', 'in:draft,active'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Vui lòng nhập tên lớp học',
      'name.max' => 'Tên lớp học không quá 255 ký tự',
      'description.max' => 'Mô tả không quá 1000 ký tự',
      'semester.max' => 'Học kỳ không quá 50 ký tự',
      'max_students.integer' => 'Số học sinh tối đa phải là số nguyên',
      'max_students.min' => 'Số học sinh tối đa phải lớn hơn 0',
      'max_students.max' => 'Số học sinh tối đa không quá 500',
      'status.in' => 'Trạng thái không hợp lệ',
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
