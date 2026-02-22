<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterStudentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Vui lòng nhập họ tên',
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'email.unique' => 'Email đã tồn tại',
      'password.required' => 'Vui lòng nhập mật khẩu',
      'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
      'password.confirmed' => 'Xác nhận mật khẩu không khớp',
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
