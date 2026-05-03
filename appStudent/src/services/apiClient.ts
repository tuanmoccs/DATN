import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import API_CONFIG from '../config/api';

const apiClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

const refreshClient = axios.create({
  baseURL: API_CONFIG.BASE_URL,
  timeout: API_CONFIG.TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

let isRefreshing = false;
let authFailureHandler: (() => void | Promise<void>) | null = null;
let failedQueue: Array<{
  resolve: (token: string) => void;
  reject: (error: unknown) => void;
}> = [];

export const setAuthFailureHandler = (
  handler: (() => void | Promise<void>) | null,
) => {
  authFailureHandler = handler;
};

const clearStoredAuthData = async () => {
  await AsyncStorage.multiRemove([
    'access_token',
    'user_info',
    'token_expired_at',
  ]);
};

const handleAuthFailure = async () => {
  await clearStoredAuthData();
  if (authFailureHandler) {
    await authFailureHandler();
  }
};

const processQueue = (error: unknown, token: string | null = null) => {
  failedQueue.forEach(request => {
    if (error) {
      request.reject(error);
      return;
    }

    if (token) {
      request.resolve(token);
    } else {
      request.reject(new Error('Missing refreshed access token'));
    }
  });

  failedQueue = [];
};

apiClient.interceptors.request.use(
  async config => {
    const token = await AsyncStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => Promise.reject(error),
);

apiClient.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;
    const status = error.response?.status;
    const requestUrl = originalRequest?.url || '';
    const isRefreshRequest = requestUrl.includes('/auth/refresh');

    if (status === 401 && isRefreshRequest) {
      processQueue(error, null);
      isRefreshing = false;
      await handleAuthFailure();
      return Promise.reject(error);
    }

    if (status === 401 && originalRequest && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise<string>((resolve, reject) => {
          failedQueue.push({resolve, reject});
        }).then(token => {
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return apiClient(originalRequest);
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const currentToken = await AsyncStorage.getItem('access_token');
        const response = await refreshClient.post(
          '/auth/refresh',
          {},
          {
            headers: currentToken
              ? {Authorization: `Bearer ${currentToken}`}
              : undefined,
          },
        );
        const {access_token, expires_in} = response.data;

        await AsyncStorage.setItem('access_token', access_token);
        const expiresAt = Date.now() + expires_in * 1000;
        await AsyncStorage.setItem('token_expired_at', expiresAt.toString());

        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        processQueue(null, access_token);

        return apiClient(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);
        await handleAuthFailure();
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  },
);

export default apiClient;
