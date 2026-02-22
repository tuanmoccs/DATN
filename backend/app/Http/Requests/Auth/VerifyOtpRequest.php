<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyOtpRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => ['required', 'email'],
      'otp' => ['required', 'string', 'size:6'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'otp.required' => 'Vui lòng nhập mã OTP',
      'otp.size' => 'Mã OTP phải có 6 ký tự',
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
