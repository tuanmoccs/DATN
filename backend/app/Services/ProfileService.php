<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
  public function __construct(
    private readonly UserRepositoryInterface $userRepository
  ) {}

  public function updateProfile(array $data): array
  {
    try {
      /** @var User $user */
      $user = auth()->user();

      if (!$user) {
        return [
          'data' => [
            'success' => false,
            'message' => 'Người dùng không tồn tại'
          ],
          'status' => 404
        ];
      }

      $updateData = [];

      if (isset($data['name'])) {
        $updateData['name'] = $data['name'];
      }

      if (isset($data['email'])) {
        $updateData['email'] = $data['email'];
      }

      if (isset($data['avatar'])) {
        // Xóa avatar cũ nếu tồn tại
        if ($user->avatar) {
          Storage::disk('public')->delete($user->avatar);
        }

        // Lưu avatar mới
        $avatarPath = $data['avatar']->store('avatars', 'public');
        $updateData['avatar'] = $avatarPath;
      }

      if (!empty($updateData)) {
        $user->update($updateData);
      }

      return [
        'data' => [
          'success' => true,
          'message' => 'Cập nhật thông tin cá nhân thành công',
          'user' => $user
        ],
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }

  public function uploadAvatar($avatarFile): array
  {
    try {
      /** @var User $user */
      $user = auth()->user();

      if (!$user) {
        return [
          'data' => [
            'success' => false,
            'message' => 'Người dùng không tồn tại'
          ],
          'status' => 404
        ];
      }

      // Xóa avatar cũ nếu tồn tại
      if ($user->avatar) {
        Storage::disk('public')->delete($user->avatar);
      }

      // Lưu avatar mới
      $avatarPath = $avatarFile->store('avatars', 'public');
      $user->update(['avatar' => $avatarPath]);

      return [
        'data' => [
          'success' => true,
          'message' => 'Cập nhật ảnh đại diện thành công',
          'avatar' => asset('storage/' . $avatarPath),
          'user' => $user
        ],
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }

  public function changePassword(array $data): array
  {
    try {
      /** @var User $user */
      $user = auth()->user();

      if (!$user) {
        return [
          'data' => [
            'success' => false,
            'message' => 'Người dùng không tồn tại'
          ],
          'status' => 404
        ];
      }

      if (!Hash::check($data['current_password'], $user->password)) {
        return [
          'data' => [
            'success' => false,
            'message' => 'Mật khẩu hiện tại không chính xác'
          ],
          'status' => 400
        ];
      }

      $user->update(['password' => Hash::make($data['password'])]);

      return [
        'data' => [
          'success' => true,
          'message' => 'Đổi mật khẩu thành công'
        ],
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'data' => [
          'success' => false,
          'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ],
        'status' => 500
      ];
    }
  }
}
