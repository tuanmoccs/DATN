<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo/Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-500 rounded-full mb-4">
          <i class="fas fa-lock text-white text-2xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800">Quên Mật Khẩu</h1>
        <p class="text-gray-600 mt-2">Hãy cung cấp email để đặt lại mật khẩu</p>
      </div>

      <!-- Main Content -->
      <div v-if="!resetSuccess" class="space-y-6">
        <!-- Step 1: Email & Role -->
        <div v-if="currentStep === 1" class="bg-white rounded-xl shadow-lg p-8 space-y-6">
          <!-- Email Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
            </label>
            <input v-model="email" type="email"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
              placeholder="Nhập email của bạn" @keyup.enter="sendOtp" />
          </div>

          <!-- Role Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              <i class="fas fa-user-tie text-blue-500 mr-2"></i>Vai Trò
            </label>
            <div class="grid grid-cols-2 gap-3">
              <button v-for="r in ['teacher', 'student']" :key="r" @click="role = r" :class="[
                'py-3 px-4 rounded-lg border-2 font-medium transition-all',
                role === r
                  ? 'border-blue-500 bg-blue-50 text-blue-600'
                  : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'
              ]">
                {{ r === 'teacher' ? '👨‍🏫 Giáo Viên' : '👨‍🎓 Học Sinh' }}
              </button>
            </div>
          </div>

          <!-- Send OTP Button -->
          <button @click="sendOtp" :disabled="!email || !role || otpLoading"
            class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
            <i v-if="!otpLoading" class="fas fa-paper-plane"></i>
            <i v-else class="fas fa-spinner fa-spin"></i>
            {{ otpLoading ? 'Đang gửi...' : 'Gửi Mã OTP' }}
          </button>

          <!-- Back to Login -->
          <div class="text-center">
            <router-link to="/login"
              class="text-blue-500 hover:text-blue-600 font-medium flex items-center justify-center gap-2">
              <i class="fas fa-arrow-left"></i>
              Quay lại đăng nhập
            </router-link>
          </div>
        </div>

        <!-- Step 2: OTP Verification -->
        <div v-if="currentStep === 2" class="bg-white rounded-xl shadow-lg p-8 space-y-6">
          <!-- OTP Info -->
          <div class="text-center bg-blue-50 rounded-lg p-4 mb-4">
            <p class="text-sm text-gray-600">
              Mã OTP đã được gửi tới
            </p>
            <p class="font-semibold text-gray-800 truncate">{{ email }}</p>
            <p class="text-xs text-gray-500 mt-2">Mã sẽ hết hạn sau 10 phút</p>
          </div>

          <!-- OTP Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-key text-blue-500 mr-2"></i>Mã OTP (6 chữ số)
            </label>
            <input v-model="otp" type="text" maxlength="6" inputmode="numeric"
              class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-center text-2xl font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
              placeholder="000000" @keyup.enter="verifyOtp" />
            <p class="text-xs text-gray-500 mt-2 text-center">{{ otpTimer }}s</p>
          </div>

          <!-- Verify Button -->
          <button @click="verifyOtp" :disabled="otp.length !== 6 || verifyLoading"
            class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
            <i v-if="!verifyLoading" class="fas fa-check-circle"></i>
            <i v-else class="fas fa-spinner fa-spin"></i>
            {{ verifyLoading ? 'Đang xác minh...' : 'Xác Minh OTP' }}
          </button>

          <!-- Resend OTP -->
          <div class="text-center">
            <button @click="sendOtp" :disabled="otpTimer > 0 || resendLoading"
              class="text-blue-500 hover:text-blue-600 disabled:text-gray-400 font-medium transition-colors flex items-center justify-center gap-2 w-full">
              <i v-if="!resendLoading" class="fas fa-redo"></i>
              <i v-else class="fas fa-spinner fa-spin"></i>
              {{ otpTimer > 0 ? `Gửi lại sau ${otpTimer}s` : 'Gửi mã OTP lại' }}
            </button>
          </div>

          <!-- Back Button -->
          <button @click="currentStep = 1; otp = ''"
            class="w-full text-gray-600 hover:text-gray-800 font-medium flex items-center justify-center gap-2">
            <i class="fas fa-arrow-left"></i>
            Quay lại
          </button>
        </div>

        <!-- Step 3: Reset Password -->
        <div v-if="currentStep === 3" class="bg-white rounded-xl shadow-lg p-8 space-y-6">
          <!-- Progress -->
          <div class="flex items-center justify-center gap-4 text-sm">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white font-semibold">
              <i class="fas fa-check text-sm"></i>
            </div>
            <div class="h-1 w-8 bg-blue-500"></div>
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white font-semibold">
              <i class="fas fa-check text-sm"></i>
            </div>
            <div class="h-1 w-8 bg-blue-500"></div>
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white font-semibold">
              3
            </div>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-key text-blue-500 mr-2"></i>Mật Khẩu Mới
            </label>
            <div class="relative">
              <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10 transition-all"
                placeholder="Nhập mật khẩu mới" />
              <button type="button" @click="showNewPassword = !showNewPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                <i :class="[showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
              </button>
            </div>

            <!-- Password Strength -->
            <div class="mt-3">
              <div class="flex gap-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                <div v-for="i in 4" :key="i" :class="[
                  'flex-1 transition-colors',
                  passwordStrength >= i ? getStrengthColor(passwordStrength) : 'bg-gray-200'
                ]"></div>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ passwordStrengthText }}</p>
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-check-circle text-blue-500 mr-2"></i>Xác Nhận Mật Khẩu
            </label>
            <div class="relative">
              <input v-model="confirmPassword" :type="showConfirmPassword ? 'text' : 'password'"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10 transition-all"
                placeholder="Xác nhận mật khẩu" @keyup.enter="resetPassword" />
              <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                <i :class="[showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
              </button>
            </div>
          </div>

          <!-- Password Requirements -->
          <div class="p-4 bg-blue-50 rounded-lg text-sm">
            <p class="font-medium text-gray-800 mb-2">Yêu cầu mật khẩu:</p>
            <ul class="space-y-1 text-gray-700">
              <li class="flex items-center gap-2">
                <i :class="[newPassword.length >= 8 ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500']"></i>
                Ít nhất 8 ký tự
              </li>
              <li class="flex items-center gap-2">
                <i
                  :class="[/[A-Z]/.test(newPassword) ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500']"></i>
                Chứa 1 chữ cái viết hoa
              </li>
              <li class="flex items-center gap-2">
                <i
                  :class="[/[0-9]/.test(newPassword) ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500']"></i>
                Chứa 1 chữ số
              </li>
              <li class="flex items-center gap-2">
                <i
                  :class="[newPassword === confirmPassword && confirmPassword ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500']"></i>
                Xác nhận khớp
              </li>
            </ul>
          </div>

          <!-- Reset Button -->
          <button @click="resetPassword" :disabled="!isPasswordValid || resetLoading"
            class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-semibold transition-all flex items-center justify-center gap-2">
            <i v-if="!resetLoading" class="fas fa-save"></i>
            <i v-else class="fas fa-spinner fa-spin"></i>
            {{ resetLoading ? 'Đang xử lý...' : 'Đặt Lại Mật Khẩu' }}
          </button>
        </div>
      </div>

      <!-- Success State -->
      <div v-else class="bg-white rounded-xl shadow-lg p-8 text-center space-y-6">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mx-auto">
          <i class="fas fa-check text-green-500 text-4xl"></i>
        </div>
        <div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Mật Khẩu Được Đặt Lại</h2>
          <p class="text-gray-600">Mật khẩu của bạn đã được thay đổi thành công. Vui lòng đăng nhập bằng mật khẩu mới.
          </p>
        </div>
        <router-link to="/login"
          class="inline-block py-3 px-8 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-semibold transition-colors">
          <i class="fas fa-sign-in-alt mr-2"></i>
          Đi Đến Đăng Nhập
        </router-link>
      </div>

      <!-- Error Alert -->
      <transition name="slide-down">
        <div v-if="showError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <i class="fas fa-exclamation-circle text-red-500 text-xl flex-shrink-0"></i>
          <div>
            <p class="font-medium text-red-800 text-sm">{{ errorMessage }}</p>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '@/plugins/axios'

const router = useRouter()

const currentStep = ref(1)
const email = ref('')
const role = ref('teacher')
const otp = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const otpLoading = ref(false)
const resendLoading = ref(false)
const verifyLoading = ref(false)
const resetLoading = ref(false)

const otpTimer = ref(0)
const showError = ref(false)
const errorMessage = ref('')
const resetSuccess = ref(false)

let otpTimerInterval = null

const passwordStrength = computed(() => {
  let strength = 0
  const pwd = newPassword.value

  if (pwd.length >= 8) strength++
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++
  if (/[0-9]/.test(pwd)) strength++
  if (/[^A-Za-z0-9]/.test(pwd)) strength++

  return strength
})

const passwordStrengthText = computed(() => {
  const texts = ['Yếu', 'Trung Bình', 'Tốt', 'Rất Tốt']
  return texts[passwordStrength.value - 1] || 'Yếu'
})

const isPasswordValid = computed(() => {
  return (
    newPassword.value.length >= 8 &&
    /[A-Z]/.test(newPassword.value) &&
    /[0-9]/.test(newPassword.value) &&
    newPassword.value === confirmPassword.value &&
    confirmPassword.value
  )
})

const getStrengthColor = (strength) => {
  const colors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500']
  return colors[strength - 1] || 'bg-gray-300'
}

const startOtpTimer = () => {
  otpTimer.value = 120
  if (otpTimerInterval) clearInterval(otpTimerInterval)

  otpTimerInterval = setInterval(() => {
    otpTimer.value--
    if (otpTimer.value <= 0) {
      clearInterval(otpTimerInterval)
    }
  }, 1000)
}

const sendOtp = async () => {
  if (!email.value || !role.value) return

  try {
    otpLoading.value = true
    resendLoading.value = true
    await apiClient.post('/auth/forgot-password/send-otp', {
      email: email.value,
      role: role.value
    })

    currentStep.value = 2
    otp.value = ''
    startOtpTimer()
    showError.value = false
  } catch (error) {
    showErrorMessage(error.response?.data?.message || 'Không thể gửi OTP')
  } finally {
    otpLoading.value = false
    resendLoading.value = false
  }
}

const verifyOtp = async () => {
  if (otp.value.length !== 6) return

  try {
    verifyLoading.value = true
    await apiClient.post('/auth/forgot-password/reset-temp', {
      email: email.value,
      otp: otp.value
    })

    currentStep.value = 3
    newPassword.value = ''
    confirmPassword.value = ''
    showError.value = false
    clearInterval(otpTimerInterval)
  } catch (error) {
    showErrorMessage(error.response?.data?.message || 'Mã OTP không hợp lệ')
  } finally {
    verifyLoading.value = false
  }
}

const resetPassword = async () => {
  if (!isPasswordValid.value) return

  try {
    resetLoading.value = true
    await apiClient.post('/auth/forgot-password/reset', {
      email: email.value,
      otp: otp.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value
    })

    resetSuccess.value = true
    showError.value = false

    setTimeout(() => {
      router.push('/login')
    }, 3000)
  } catch (error) {
    showErrorMessage(error.response?.data?.message || 'Đặt lại mật khẩu thất bại')
  } finally {
    resetLoading.value = false
  }
}

const showErrorMessage = (message) => {
  errorMessage.value = message
  showError.value = true
  setTimeout(() => {
    showError.value = false
  }, 4000)
}

onMounted(() => {
  clearInterval(otpTimerInterval)
})
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  -moz-appearance: textfield;
}
</style>
