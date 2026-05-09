import apiClient from './apiClient';

export interface UserProfile {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar: string | null;
}

export interface ProfileResponse {
  success: boolean;
  message?: string;
  user: UserProfile;
}

export interface UpdateProfileParams {
  name: string;
  email: string;
}

export interface ChangePasswordParams {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface BasicProfileResponse {
  success: boolean;
  message: string;
  user?: UserProfile;
  avatar?: string;
}

const profileService = {
  getProfile: async (): Promise<ProfileResponse> => {
    const response = await apiClient.get('/profile');
    return response.data;
  },

  updateProfile: async (
    params: UpdateProfileParams,
  ): Promise<BasicProfileResponse> => {
    const response = await apiClient.post('/profile/update', params);
    return response.data;
  },

  changePassword: async (
    params: ChangePasswordParams,
  ): Promise<BasicProfileResponse> => {
    const response = await apiClient.post('/profile/change-password', params);
    return response.data;
  },

  uploadAvatar: async (asset: {
    uri: string;
    type?: string;
    fileName?: string;
  }): Promise<BasicProfileResponse> => {
    const formData = new FormData();
    formData.append('avatar', {
      uri: asset.uri,
      type: asset.type || 'image/jpeg',
      name: asset.fileName || 'avatar.jpg',
    } as never);

    const response = await apiClient.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },
};

export default profileService;
