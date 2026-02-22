<template>
  <div class="w-full max-w-md mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <!-- Header -->
      <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Đăng ký giáo viên</h2>
        <p class="text-gray-600 mt-2">Tạo tài khoản giáo viên</p>
      </div>

      <!-- Step 1: Nhập thông tin (Teacher) hoặc Form đầy đủ (Student) -->
      <form v-if="step === 1" @submit.prevent="handleSubmitStep1" class="space-y-6">

        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
            Họ và tên
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="text-gray-400">👤</i>
            </div>
            <input id="name" v-model="formData.name" type="text" required
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="Nguyễn Văn A" :class="{ 'border-red-500': errors.name }" />
          </div>
          <p v-if="errors.name" class="mt-1 text-sm text-red-600">
            {{ errors.name[0] }}
          </p>
        </div>

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
              placeholder="Tối thiểu 8 ký tự" :class="{ 'border-red-500': errors.password }" />
            <button type="button" @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center">
              <i class="text-gray-400">{{ showPassword ? '👁' : '👁‍🗨' }}</i>
            </button>
          </div>
          <p v-if="errors.password" class="mt-1 text-sm text-red-600">
            {{ errors.password[0] }}
          </p>
        </div>

        <!-- Password Confirmation -->
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
            Xác nhận mật khẩu
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="text-gray-400">🔒</i>
            </div>
            <input id="password_confirmation" v-model="formData.password_confirmation"
              :type="showPasswordConfirm ? 'text' : 'password'" required
              class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
              placeholder="Nhập lại mật khẩu" />
            <button type="button" @click="showPasswordConfirm = !showPasswordConfirm"
              class="absolute inset-y-0 right-0 pr-3 flex items-center">
              <i class="text-gray-400">{{ showPasswordConfirm ? '👁' : '👁‍🗨' }}</i>
            </button>
          </div>
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
          <span v-else>
            Tiếp tục
          </span>
        </button>
      </form>

      <!-- Step 2: Nhập OTP (Chỉ cho Teacher) -->
      <form v-if="step === 2" @submit.prevent="handleVerifyOtp" class="space-y-6">
        <!-- Info Message -->
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-sm text-blue-800">
            Mã OTP đã được gửi đến email <strong>{{ formData.email }}</strong>
          </p>
        </div>

        <!-- OTP Input -->
        <div>
          <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
            Nhập mã OTP (6 số)
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="text-gray-400">🔑</i>
            </div>
            <input id="otp" v-model="otpCode" type="text" maxlength="6" required
              class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-center text-2xl tracking-widest font-mono"
              placeholder="000000" :class="{ 'border-red-500': errors.otp }" />
          </div>
          <p v-if="errors.otp" class="mt-1 text-sm text-red-600">
            {{ errors.otp[0] }}
          </p>
        </div>

        <!-- Resend OTP -->
        <div class="text-center">
          <button type="button" @click="handleResendOtp" :disabled="resendLoading || countdown > 0"
            class="text-sm text-blue-600 hover:text-blue-700 disabled:text-gray-400 disabled:cursor-not-allowed">
            {{ countdown > 0 ? `Gửi lại sau ${countdown}s` : 'Gửi lại mã OTP' }}
          </button>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ errorMessage }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
          <button type="submit" :disabled="loading"
            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
            <span v-if="loading" class="flex items-center justify-center">
              <i class="animate-spin mr-2">⟳</i>
              Đang xác thực...
            </span>
            <span v-else>Xác thực</span>
          </button>

          <button type="button" @click="step = 1"
            class="w-full py-3 px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
            Quay lại
          </button>
        </div>
      </form>

      <!-- Login Link -->
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Đã có tài khoản?
          <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">
            Đăng nhập ngay
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useApi } from '@/plugins/api'

const router = useRouter()
const route = useRoute()
const api = useApi()

const currentRole = ref('teacher')
const step = ref(1)

const formData = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const otpCode = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const loading = ref(false)
const resendLoading = ref(false)
const errors = ref({})
const errorMessage = ref('')
const countdown = ref(0)
let countdownInterval = null

// Watch role change để reset form
// watch(currentRole, () => {
//   step.value = 1
//   errors.value = {}
//   errorMessage.value = ''
// })

const handleSubmitStep1 = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''

  try {
    if (currentRole.value === 'teacher') {
      // Teacher: Gửi OTP
      const response = await api.auth.registerTeacherSendOtp({
        name: formData.name,
        email: formData.email,
        password: formData.password,
        password_confirmation: formData.password_confirmation,
      })

      if (response.success) {
        step.value = 2
        startCountdown()
      }
    }
    //else {
    //   // Student: Đăng ký trực tiếp
    //   const response = await api.auth.registerStudent({
    //     name: formData.name,
    //     email: formData.email,
    //     password: formData.password,
    //     password_confirmation: formData.password_confirmation,
    //   })

    //   if (response.success) {
    //     // Lưu token và thông tin user
    //     localStorage.setItem('access_token', response.access_token)
    //     localStorage.setItem('user_info', JSON.stringify(response.user))

    //     const expiresAt = Date.now() + response.expires_in * 1000
    //     localStorage.setItem('token_expired_at', expiresAt.toString())

    //     // Redirect
    //     router.push('/student/dashboard')
    //   }
    // }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      errorMessage.value = error.response?.data?.message || 'Đăng ký thất bại. Vui lòng thử lại.'
    }
  } finally {
    loading.value = false
  }
}

const handleVerifyOtp = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''

  try {
    const response = await api.auth.registerTeacherVerifyOtp({
      email: formData.email,
      otp: otpCode.value,
    })

    if (response.success) {
      // Lưu token và thông tin user
      localStorage.setItem('access_token', response.access_token)
      localStorage.setItem('user_info', JSON.stringify(response.user))

      const expiresAt = Date.now() + response.expires_in * 1000
      localStorage.setItem('token_expired_at', expiresAt.toString())

      // Redirect
      router.push('/teacher/dashboard')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      errorMessage.value = error.response?.data?.message || 'Xác thực OTP thất bại.'
    }
  } finally {
    loading.value = false
  }
}

const handleResendOtp = async () => {
  resendLoading.value = true
  errorMessage.value = ''

  try {
    const response = await api.auth.registerTeacherSendOtp({
      name: formData.name,
      email: formData.email,
      password: formData.password,
      password_confirmation: formData.password_confirmation,
    })

    if (response.success) {
      otpCode.value = ''
      startCountdown()
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Gửi lại OTP thất bại.'
  } finally {
    resendLoading.value = false
  }
}

const startCountdown = () => {
  countdown.value = 60
  countdownInterval = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownInterval)
    }
  }, 1000)
}

onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>