<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => ['required', 'string', 'email'],
      'otp' => ['required', 'string', 'size:6'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'otp.required' => 'Vui lòng nhập mã OTP',
      'otp.size' => 'Mã OTP phải gồm 6 chữ số',
      'password.required' => 'Vui lòng nhập mật khẩu mới',
      'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự',
      'password.confirmed' => 'Xác nhận mật khẩu không trùng khớp',
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
