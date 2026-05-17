<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'current_password' => ['required', 'string'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
    ];
  }

  public function messages(): array
  {
    return [
      'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại',
      'password.required' => 'Vui lòng nhập mật khẩu mới',
      'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự',
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
