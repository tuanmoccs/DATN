<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
  public function __construct(
    private readonly ProfileService $profileService
  ) {}

  public function getProfile(Request $request): JsonResponse
  {
    return response()->json([
      'success' => true,
      'user' => auth()->user()
    ]);
  }

  public function updateProfile(UpdateProfileRequest $request): JsonResponse
  {
    $result = $this->profileService->updateProfile(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }

  public function uploadAvatar(Request $request): JsonResponse
  {
    $request->validate([
      'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
    ], [
      'avatar.required' => 'Vui lòng chọn một hình ảnh',
      'avatar.image' => 'File phải là hình ảnh hợp lệ',
      'avatar.mimes' => 'Chỉ chấp nhận: jpeg, png, jpg, gif',
      'avatar.max' => 'Kích thước hình ảnh không được vượt quá 5MB',
    ]);

    $result = $this->profileService->uploadAvatar($request->file('avatar'));

    return response()->json($result['data'], $result['status']);
  }

  public function changePassword(ChangePasswordRequest $request): JsonResponse
  {
    $result = $this->profileService->changePassword(
      $request->validated()
    );

    return response()->json($result['data'], $result['status']);
  }
}
