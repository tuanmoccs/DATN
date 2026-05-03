import React, {createContext, useContext, useEffect, useState} from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import authService, {
  AuthResponse,
  LoginParams,
  RegisterParams,
} from '../services/authService';
import {setAuthFailureHandler} from '../services/apiClient';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar: string | null;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (params: LoginParams) => Promise<void>;
  register: (params: RegisterParams) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const saveAuthData = async (response: AuthResponse) => {
  await AsyncStorage.setItem('access_token', response.access_token);
  await AsyncStorage.setItem('user_info', JSON.stringify(response.user));
  const expiresAt = Date.now() + response.expires_in * 1000;
  await AsyncStorage.setItem('token_expired_at', expiresAt.toString());
};

const clearAuthData = async () => {
  await AsyncStorage.multiRemove([
    'access_token',
    'user_info',
    'token_expired_at',
  ]);
};

const isTokenExpired = (expiredAt: string | null) => {
  if (!expiredAt) {
    return false;
  }

  const expiredAtNumber = Number(expiredAt);
  return !Number.isFinite(expiredAtNumber) || expiredAtNumber <= Date.now();
};

export const AuthProvider: React.FC<{children: React.ReactNode}> = ({
  children,
}) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const syncLogout = async () => {
      await clearAuthData();
      setUser(null);
      setIsLoading(false);
    };

    setAuthFailureHandler(syncLogout);
    return () => {
      setAuthFailureHandler(null);
    };
  }, []);

  useEffect(() => {
    const checkAuth = async () => {
      try {
        const [token, userInfo, tokenExpiredAt] = await AsyncStorage.multiGet([
          'access_token',
          'user_info',
          'token_expired_at',
        ]);

        const accessToken = token[1];
        const storedUser = userInfo[1];
        const expiresAt = tokenExpiredAt[1];

        if (!accessToken || !storedUser || isTokenExpired(expiresAt)) {
          await clearAuthData();
          setUser(null);
          return;
        }

        setUser(JSON.parse(storedUser));
      } catch {
        await clearAuthData();
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    };

    checkAuth();
  }, []);

  const login = async (params: LoginParams) => {
    const response = await authService.login(params);
    if (response.success) {
      await saveAuthData(response);
      setUser(response.user);
    }
  };

  const register = async (params: RegisterParams) => {
    const response = await authService.register(params);
    if (response.success) {
      await saveAuthData(response);
      setUser(response.user);
    }
  };

  const logout = async () => {
    try {
      await authService.logout();
    } catch {
      // Ignore logout API failures and clear local session anyway.
    } finally {
      await clearAuthData();
      setUser(null);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        login,
        register,
        logout,
      }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
