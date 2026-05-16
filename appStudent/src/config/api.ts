// Cấu hình API
// Thay đổi BASE_URL phù hợp với môi trường
// Android emulator: 10.0.2.2 | iOS simulator: localhost | Device thật: IP LAN máy chủ
const API_CONFIG = {
  BASE_URL: 'http://10.0.2.2:8000/api',
  TIMEOUT: 10000,
};

export const getImageUrl = (path: string | undefined | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('http')) return path;
  
  const baseUrl = API_CONFIG.BASE_URL.replace('/api', '');
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  return `${baseUrl}/${cleanPath}`;
};

export default API_CONFIG;
