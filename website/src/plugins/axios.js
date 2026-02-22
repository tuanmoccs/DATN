// src/plugins/axios.js
import axios from "axios"
import router from "../router"
import errorMessages from "../constants/error"

// Tạo axios instance
const $axios = axios.create({
  baseURL: import.meta.env.VITE_API_ENDPOINT || "http://localhost:8000/api",
  timeout: 10000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
})

// Thêm method tiện ích như Nuxt.js
$axios.$get = async (url, config = {}) => {
  const response = await $axios.get(url, config)
  return response.data
}

$axios.$post = async (url, data, config = {}) => {
  const response = await $axios.post(url, data, config)
  return response.data
}

$axios.$put = async (url, data, config = {}) => {
  const response = await $axios.put(url, data, config)
  return response.data
}

$axios.$delete = async (url, config = {}) => {
  const response = await $axios.delete(url, config)
  return response.data
}

$axios.$patch = async (url, data, config = {}) => {
  const response = await $axios.patch(url, data, config)
  return response.data
}

let isRefreshing = false
let failedQueue = []

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error)
    } else {
      prom.resolve(token)
    }
  })
  failedQueue = []
}

// Request interceptor
$axios.interceptors.request.use(
  (config) => {
    // Xử lý client options
    const configData = config.data || {}
    const { clientOptions } = configData

    if (clientOptions) {
      config.headers["Client-Error-Handler"] = clientOptions.errorHandler
      if (typeof clientOptions === "object") {
        delete configData.clientOptions
      }
    }

    // Thêm token vào header
    const accessToken = localStorage.getItem("access_token")
    if (accessToken) {
      config.headers.Authorization = `Bearer ${accessToken}`
    }

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
$axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config
    const statusCode = error.response?.status
    const errorCode = error.response?.data?.error_code
    const errorMessage = error.response?.data?.message

    // Xử lý lỗi 401 - Token hết hạn hoặc không hợp lệ
    if (statusCode === 401 && !originalRequest._retry) {
      // Nếu đang refresh token, đưa request vào queue
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        })
          .then(token => {
            originalRequest.headers.Authorization = `Bearer ${token}`
            return $axios(originalRequest)
          })
          .catch(err => {
            return Promise.reject(err)
          })
      }

      originalRequest._retry = true
      isRefreshing = true

      // Thử refresh token
      try {
        const response = await $axios.post('/auth/refresh')
        
        if (response.data.success && response.data.access_token) {
          const newToken = response.data.access_token
          
          // Lưu token mới
          localStorage.setItem('access_token', newToken)
          
          // Cập nhật thời gian hết hạn
          const expiresAt = Date.now() + response.data.expires_in * 1000
          localStorage.setItem('token_expired_at', expiresAt.toString())

          // Cập nhật token cho request gốc
          originalRequest.headers.Authorization = `Bearer ${newToken}`
          $axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`

          // Process queue
          processQueue(null, newToken)

          return $axios(originalRequest)
        }
      } catch (refreshError) {
        // Refresh token thất bại
        processQueue(refreshError, null)
        
        // Xóa token và redirect về login
        localStorage.removeItem("access_token")
        localStorage.removeItem("user_info")
        localStorage.removeItem("token_expired_at")
        
        router.push("/login")
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    // Xử lý lỗi 403 - Forbidden
    if (statusCode === 403) {
      console.error("Bạn không có quyền truy cập tài nguyên này")
      return Promise.reject(error)
    }

    // Xử lý lỗi 429 - Too Many Requests
    if (statusCode === 429) {
      console.error("Bạn đã gửi quá nhiều yêu cầu trong thời gian ngắn")
      return Promise.reject(error)
    }

    // Xử lý lỗi validation 422
    if (statusCode === 422) {
      return Promise.reject(error)
    }

    // Xử lý các lỗi khác
    let message = errorMessage || errorCode || "Đã xảy ra lỗi. Vui lòng thử lại."
    message = errorMessages[errorCode] ? errorMessages[errorCode] : message
    console.error(message)

    return Promise.reject(error)
  }
)

// Export axios instance
export { $axios }

// Plugin install cho Vue 3
export default {
  install(app) {
    // Provide axios cho toàn bộ app
    app.provide("axios", $axios)
    
    // Thêm vào global properties (tương thích với Options API)
    app.config.globalProperties.$axios = $axios
  },
}