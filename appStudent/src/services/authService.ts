import apiClient from './apiClient';

export interface LoginParams {
  email: string;
  password: string;
}

export interface RegisterParams {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  success: boolean;
  message: string;
  user: {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar: string | null;
  };
  access_token: string;
  token_type: string;
  expires_in: number;
}

export interface ApiValidationError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

export interface ForgotPasswordParams {
  email: string;
}

export interface VerifyForgotPasswordOtpParams {
  email: string;
  otp: string;
}

export interface ResetPasswordParams {
  email: string;
  otp: string;
  password: string;
  password_confirmation: string;
}

export interface BasicApiResponse {
  success: boolean;
  message: string;
}

const authService = {
  login: async (params: LoginParams): Promise<AuthResponse> => {
    const response = await apiClient.post('/auth/login', {
      ...params,
      role: 'student',
    });
    return response.data;
  },

  register: async (params: RegisterParams): Promise<AuthResponse> => {
    const response = await apiClient.post('/auth/register/student', params);
    return response.data;
  },

  logout: async (): Promise<void> => {
    await apiClient.post('/logout');
  },

  me: async () => {
    const response = await apiClient.get('/me');
    return response.data;
  },

  refresh: async (): Promise<AuthResponse> => {
    const response = await apiClient.post('/auth/refresh');
    return response.data;
  },

  sendForgotPasswordOtp: async (
    params: ForgotPasswordParams,
  ): Promise<BasicApiResponse> => {
    const response = await apiClient.post('/auth/forgot-password/send-otp', {
      ...params,
      role: 'student',
    });
    return response.data;
  },

  verifyForgotPasswordOtp: async (
    params: VerifyForgotPasswordOtpParams,
  ): Promise<BasicApiResponse> => {
    const response = await apiClient.post(
      '/auth/forgot-password/verify-otp',
      params,
    );
    return response.data;
  },

  resetPassword: async (
    params: ResetPasswordParams,
  ): Promise<BasicApiResponse> => {
    const response = await apiClient.post('/auth/forgot-password/reset', params);
    return response.data;
  },
};

export default authService;
