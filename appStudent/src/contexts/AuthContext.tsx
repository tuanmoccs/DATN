import React, {createContext, useContext, useEffect, useState} from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import authService, {
  AuthResponse,
  LoginParams,
  RegisterParams,
} from '../services/authService';

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
  await AsyncStorage.removeItem('access_token');
  await AsyncStorage.removeItem('user_info');
  await AsyncStorage.removeItem('token_expired_at');
};

export const AuthProvider: React.FC<{children: React.ReactNode}> = ({
  children,
}) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Kiểm tra trạng thái đăng nhập khi mở app
  useEffect(() => {
    const checkAuth = async () => {
      try {
        const token = await AsyncStorage.getItem('access_token');
        const userInfo = await AsyncStorage.getItem('user_info');
        if (token && userInfo) {
          setUser(JSON.parse(userInfo));
        }
      } catch {
        await clearAuthData();
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
      // Bỏ qua lỗi logout API
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
    throw new Error('useAuth phải được sử dụng trong AuthProvider');
  }
  return context;
};
