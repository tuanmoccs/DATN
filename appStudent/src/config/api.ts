// Cấu hình API
// Thay đổi BASE_URL phù hợp với môi trường
// Android emulator: 10.0.2.2 | iOS simulator: localhost | Device thật: IP LAN máy chủ
const API_CONFIG = {
  BASE_URL: 'http://10.0.2.2:8000/api',
  TIMEOUT: 10000,
  STORAGE_URL: 'http://10.0.2.2:8000/',
};

export const getImageUrl = (path: string | undefined | null): string | undefined => {
  if (!path) return undefined;
  if (path.startsWith('data:')) return path;
  
  let finalPath = path;
  if (finalPath.startsWith('http')) {
    // Thay thế localhost thành 10.0.2.2 cho Android emulator
    if (finalPath.includes('localhost')) {
      finalPath = finalPath.replace('localhost', '10.0.2.2');
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
