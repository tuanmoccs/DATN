<template>
  <div class="w-full max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Đăng nhập</h2>
        <p class="text-gray-600 mt-2">Chào mừng giáo viên trở lại</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-6">

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
            Email
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="text-gray-400">✉</i>
            </div>
            <input id="email" v-model="formData.email" type="email" required
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="example@email.com" :class="{ 'border-red-500': errors.email }" />
          </div>
          <p v-if="errors.email" class="mt-1 text-sm text-red-600">
            {{ errors.email[0] }}
          </p>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
            Mật khẩu
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="text-gray-400">🔒</i>
            </div>
            <input id="password" v-model="formData.password" :type="showPassword ? 'text' : 'password'" required
              class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="••••••••" :class="{ 'border-red-500': errors.password }" />
            <button type="button" @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center">
              <i class="text-gray-400">{{ showPassword ? '👁' : '👁‍🗨' }}</i>
            </button>
          </div>
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- Remember & Forgot -->
        <div class="flex items-center justify-between">
          <label class="flex items-center">
            <input v-model="formData.remember" type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" />
            <span class="ml-2 text-sm text-gray-600">Ghi nhớ đăng nhập</span>
          </label>
          <a href="/forgot-password" class="text-sm text-blue-600 hover:text-blue-700">
            Quên mật khẩu?
          </a>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>

        <!-- Submit Button -->
        <button type="submit" :disabled="loading"
          class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
          <span v-if="loading" class="flex items-center justify-center">
            <i class="animate-spin mr-2">⟳</i>
            Đang xử lý...
          </span>
          <span v-else>Đăng nhập</span>
        </button>
      </form>

      <!-- Register Link -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Chưa có tài khoản?
          <router-link to="/register" class="text-blue-600 hover:text-blue-700 font-medium">
            Đăng ký ngay
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const router = useRouter()
const api = useApi()

const formData = reactive({
  email: '',
  password: '',
  remember: false,
})

const showPassword = ref(false)
const loading = ref(false)
const errors = ref({})
const errorMessage = ref('')

const handleLogin = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''

  try {
    const response = await api.auth.login({
      email: formData.email,
      password: formData.password,
      role: 'teacher',
    })

    if (response.success) {
      // Lưu token và thông tin user
      localStorage.setItem('access_token', response.access_token)
      localStorage.setItem('user_info', JSON.stringify(response.user))

      // Tính thời gian hết hạn token (expires_in là giây)
      const expiresAt = Date.now() + response.expires_in * 1000
      localStorage.setItem('token_expired_at', expiresAt.toString())

      // Redirect về dashboard giáo viên
      router.push('/teacher/dashboard')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      errorMessage.value = error.response?.data?.message || 'Đăng nhập thất bại. Vui lòng thử lại.'
    }
  } finally {
    loading.value = false
  }
}
</script>