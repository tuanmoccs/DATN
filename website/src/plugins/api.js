import { inject } from 'vue'
import auth from '../services/auth'
import classService from '../services/class'


export const API_KEY = Symbol('api')

export const createApi = ($axios) => {
  return {
    auth: auth($axios),
    class: classService($axios),
  }
}

// Composable để sử dụng trong Composition API
export const useApi = () => {
  const api = inject(API_KEY)
  if (!api) {
    throw new Error('API plugin chưa được cài đặt. Vui lòng thêm plugin vào main.js')
  }
  return api
}

// Plugin install cho Vue 3
export default {
  install(app) {
    // Lấy axios từ globalProperties
    const $axios = app.config.globalProperties.$axios

    if (!$axios) {
      throw new Error('Axios plugin phải được cài đặt trước API plugin')
    }

    const api = createApi($axios)

    // Provide cho Composition API
    app.provide(API_KEY, api)

    // Global cho Options API
    app.config.globalProperties.$api = api
  },
}