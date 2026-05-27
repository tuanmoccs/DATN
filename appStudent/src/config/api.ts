// Cấu hình API
// Thay đổi BASE_URL phù hợp với môi trường
// Android emulator: 10.0.2.2 | iOS simulator: localhost | Device thật: IP LAN máy chủ
const API_CONFIG = {
  BASE_URL: 'https://doantnta.site/api',
  TIMEOUT: 10000,
  STORAGE_URL: 'https://doantnta.site/',
};

export const getImageUrl = (path: string | undefined | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:')) return path;
  
  let finalPath = path;
  if (finalPath.startsWith('http')) {
    // Tự động chuyển đổi localhost/127.0.0.1 sang domain production và dùng https
    if (finalPath.includes('localhost') || finalPath.includes('127.0.0.1') || finalPath.includes('10.0.2.2')) {
      finalPath = finalPath.replace(/localhost:\d+|127\.0\.0\.1:\d+|10\.0\.2\.2:\d+/, 'doantnta.site');
      finalPath = finalPath.replace('http://', 'https://');
    }
    return finalPath;
  }
  
  const baseUrl = API_CONFIG.STORAGE_URL.endsWith('/') 
    ? API_CONFIG.STORAGE_URL.slice(0, -1) 
    : API_CONFIG.STORAGE_URL;
    
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  
  return `${baseUrl}/${cleanPath}`;
};

export default API_CONFIG;
