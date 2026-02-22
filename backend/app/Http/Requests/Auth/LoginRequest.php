<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => ['required', 'email'],
      'password' => ['required'],
      'role' => ['required', Rule::in(['teacher', 'student'])],
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'password.required' => 'Vui lòng nhập mật khẩu',
      'role.required' => 'Vui lòng chọn vai trò',
      'role.in' => 'Vai trò không hợp lệ',
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
