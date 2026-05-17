<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ForgotPasswordRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => ['required', 'string', 'email', 'exists:users,email'],
      'role' => ['required', 'in:teacher,student'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'email.exists' => 'Email không tồn tại trong hệ thống',
      'role.required' => 'Vui lòng chọn vai trò',
      'role.in' => 'Vai trò không hợp lệ',
    ];
  }

  protected function failedValidation(Validator $validator)
  {
    throw new HttpResponseException(
      response()->json([
        'success' => false,
        'message' => 'Dữ liệu không hợp lệ',
        'errors' => $validator->errors()
      ], 422)
    );
  }
}
