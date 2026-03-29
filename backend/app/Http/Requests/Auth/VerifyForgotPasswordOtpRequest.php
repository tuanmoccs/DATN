<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyForgotPasswordOtpRequest extends FormRequest
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
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'otp.required' => 'Vui lòng nhập mã OTP',
      'otp.size' => 'Mã OTP phải gồm 6 chữ số',
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
