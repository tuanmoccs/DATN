<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['sometimes', 'required', 'string', 'max:255'],
      'email' => ['sometimes', 'required', 'string', 'email', 'unique:users,email,' . auth()->id()],
      'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Vui lòng nhập họ tên',
      'name.max' => 'Họ tên không được vượt quá 255 ký tự',
      'email.required' => 'Vui lòng nhập email',
      'email.email' => 'Email không hợp lệ',
      'email.unique' => 'Email này đã được sử dụng',
      'avatar.image' => 'File phải là hình ảnh hợp lệ',
      'avatar.mimes' => 'Chỉ chấp nhận: jpeg, png, jpg, gif',
      'avatar.max' => 'Kích thước hình ảnh không được vượt quá 5MB',
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
