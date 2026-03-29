<template>
  <div class="max-w-4xl">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Profile</h1>
      <p class="text-gray-500 mt-1">Manage your account information and settings</p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mr-3"></i>
      <span class="text-gray-500">Loading...</span>
    </div>

    <template v-else>
      <!-- Tabs -->
      <div class="flex gap-4 mb-6 border-b border-gray-200">
        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
          'px-6 py-3 font-medium transition-colors border-b-2',
          activeTab === tab.id
            ? 'text-blue-600 border-blue-600'
            : 'text-gray-600 hover:text-gray-900 border-transparent'
        ]">
          <i :class="[tab.icon, 'mr-2']"></i>
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-xl border border-gray-200 p-8">
        <!-- Basic Info Tab -->
        <div v-if="activeTab === 'info'" class="space-y-8">
          <!-- Profile Photo -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Avatar</h3>
            <div class="flex items-center gap-6">
              <div class="relative">
                <img v-if="formData.avatarPreview" :src="formData.avatarPreview" alt="Avatar"
                  class="w-32 h-32 rounded-full object-cover border-4 border-gray-200" />
                <div v-else
                  class="w-32 h-32 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-4xl border-4 border-gray-200">
                  {{ getInitials(formData.name) }}
                </div>
                <label
                  class="absolute bottom-0 right-0 bg-blue-500 hover:bg-blue-600 text-white rounded-full p-3 cursor-pointer transition-colors shadow-lg">
                  <i class="fas fa-camera"></i>
                  <input type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
                </label>
              </div>

              <div>
                <p class="text-sm text-gray-600 mb-2">Update avatar</p>
                <p class="text-xs text-gray-400 mb-4">Format: JPEG, PNG, GIF (Max 5MB)</p>
                <button v-if="selectedFile" @click="uploadAvatar" :disabled="avatarUploading"
                  class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors">
                  <i v-if="!avatarUploading" class="fas fa-upload mr-2"></i>
                  <i v-else class="fas fa-spinner fa-spin mr-2"></i>
                  {{ avatarUploading ? 'Loading...' : 'Update Image' }}
                </button>
              </div>
            </div>
          </div>

          <hr class="border-gray-200" />

          <!-- Basic Information -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-user text-blue-500 mr-2"></i>Full Name
                </label>
                <input v-model="formData.name" type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter your full name" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                </label>
                <input v-model="formData.email" type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Nhập email" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-user-tie text-blue-500 mr-2"></i>Role
                </label>
                <div class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                  <span class="capitalize font-medium text-gray-700">{{ roleLabel }}</span>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-calendar text-blue-500 mr-2"></i>Participant in
                </label>
                <div class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                  <span class="font-medium text-gray-700">{{ formatDate(user.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <hr class="border-gray-200" />

          <div class="flex gap-4">
            <button @click="updateProfile" :disabled="updateLoading"
              class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
              <i v-if="!updateLoading" class="fas fa-save"></i>
              <i v-else class="fas fa-spinner fa-spin"></i>
              {{ updateLoading ? 'Saving...' : 'Save Changes' }}
            </button>
            <button @click="resetForm"
              class="px-6 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-lg font-medium transition-colors">
              Cancel
            </button>
          </div>
        </div>

        <!-- Security Tab -->
        <div v-if="activeTab === 'security'" class="space-y-8">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
            <div class="space-y-4 max-w-md">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-lock text-blue-500 mr-2"></i>Current Password
                </label>
                <div class="relative">
                  <input v-model="passwordForm.currentPassword" :type="showPasswords.current ? 'text' : 'password'"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                    placeholder="Nhập mật khẩu hiện tại" />
                  <button type="button" @click="togglePasswordVisibility('current')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <i :class="[showPasswords.current ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-key text-blue-500 mr-2"></i>New Password
                </label>
                <div class="relative">
                  <input v-model="passwordForm.newPassword" :type="showPasswords.new ? 'text' : 'password'"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                    placeholder="Nhập mật khẩu mới" />
                  <button type="button" @click="togglePasswordVisibility('new')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <i :class="[showPasswords.new ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                  </button>
                </div>
                <div class="mt-2">
                  <div class="flex gap-1 h-1 bg-gray-200 rounded-full overflow-hidden">
                    <div v-for="i in 4" :key="i"
                      :class="['flex-1 transition-colors', passwordStrength >= i ? 'bg-blue-500' : 'bg-gray-300']">
                    </div>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">{{ passwordStrengthText }}</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="fas fa-check-circle text-blue-500 mr-2"></i>Confirm Password
                </label>
                <div class="relative">
                  <input v-model="passwordForm.confirmPassword" :type="showPasswords.confirm ? 'text' : 'password'"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                    placeholder="Xác nhận mật khẩu mới" />
                  <button type="button" @click="togglePasswordVisibility('confirm')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <i :class="[showPasswords.confirm ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                  </button>
                </div>
              </div>

              <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm font-medium text-gray-800 mb-2">Password Requirements:</p>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li class="flex items-center gap-2">
                    <i
                      :class="passwordForm.newPassword.length >= 8 ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500'"></i>
                    At least 8 characters
                  </li>
                  <li class="flex items-center gap-2">
                    <i
                      :class="/[A-Z]/.test(passwordForm.newPassword) ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500'"></i>
                    Contains at least one uppercase letter
                  </li>
                  <li class="flex items-center gap-2">
                    <i
                      :class="/[0-9]/.test(passwordForm.newPassword) ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500'"></i>
                    Contains at least one number
                  </li>
                  <li class="flex items-center gap-2">
                    <i
                      :class="passwordForm.newPassword === passwordForm.confirmPassword && passwordForm.confirmPassword ? 'fas fa-check text-green-500' : 'fas fa-times text-red-500'"></i>
                    Confirm password matches
                  </li>
                </ul>
              </div>
            </div>

            <div class="flex gap-4 mt-6">
              <button @click="changePassword" :disabled="passwordChangeLoading || !isPasswordFormValid"
                class="px-6 py-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                <i v-if="!passwordChangeLoading" class="fas fa-lock"></i>
                <i v-else class="fas fa-spinner fa-spin"></i>
                {{ passwordChangeLoading ? 'Saving...' : 'Change Password' }}
              </button>
            </div>
          </div>

          <hr class="border-gray-200" />

          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Account Security</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center gap-3">
                  <i class="fas fa-check-circle text-green-500 text-xl"></i>
                  <div>
                    <p class="font-medium text-gray-800">Email Verification</p>
                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                  </div>
                </div>
                <span class="text-green-600 font-medium">✓ Verified</span>
              </div>

              <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center gap-3">
                  <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                  <div>
                    <p class="font-medium text-gray-800">Last Login</p>
                    <p class="text-sm text-gray-500">{{ formatDate(user.updated_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Alert -->
      <transition name="slide-down">
        <div v-if="showSuccess" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
          <i class="fas fa-check-circle text-green-500 text-xl"></i>
          <p class="font-medium text-green-800">{{ successMessage }}</p>
        </div>
      </transition>

      <!-- Error Alert -->
      <transition name="slide-down">
        <div v-if="showError" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
          <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
          <p class="font-medium text-red-800">{{ errorMessage }}</p>
        </div>
      </transition>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '@/plugins/axios'
import { useApi } from '@/plugins/api'
const api = useApi()

// ─── State ───────────────────────────────────────────────────────────────────
const loading = ref(false)
const activeTab = ref('info')
const selectedFile = ref(null)
const avatarUploading = ref(false)
const updateLoading = ref(false)
const passwordChangeLoading = ref(false)

const showSuccess = ref(false)
const showError = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const user = ref({})
const formData = ref({ name: '', email: '', avatarPreview: '' })
const passwordForm = ref({ currentPassword: '', newPassword: '', confirmPassword: '' })
const showPasswords = ref({ current: false, new: false, confirm: false })

// ─── Static data ──────────────────────────────────────────────────────────────
const tabs = [
  { id: 'info', label: 'Personal Information', icon: 'fas fa-user' },
  { id: 'security', label: 'Security', icon: 'fas fa-shield-alt' }
]

// ─── Computed ────────────────────────────────────────────────────────────────
const roleLabel = computed(() => {
  const roles = { teacher: 'Teacher' }
  return roles[user.value.role] || user.value.role
})

const passwordStrength = computed(() => {
  const pwd = passwordForm.value.newPassword
  let strength = 0
  if (pwd.length >= 8) strength++
  if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) strength++
  if (/[0-9]/.test(pwd)) strength++
  if (/[^A-Za-z0-9]/.test(pwd)) strength++
  return strength
})

const passwordStrengthText = computed(() => {
  return (['Weak', 'Fair', 'Good', 'Strong'])[passwordStrength.value - 1] || 'Weak'
})

const isPasswordFormValid = computed(() => {
  const { currentPassword, newPassword, confirmPassword } = passwordForm.value
  return (
    currentPassword &&
    newPassword &&
    confirmPassword &&
    newPassword === confirmPassword &&
    newPassword.length >= 8 &&
    passwordStrength.value >= 2
  )
})

// ─── Helpers ─────────────────────────────────────────────────────────────────
const getInitials = (name) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  }).format(new Date(date))
}

const showSuccessMessage = (message) => {
  successMessage.value = message
  showSuccess.value = true
  setTimeout(() => (showSuccess.value = false), 3000)
}

const showErrorMessage = (message) => {
  errorMessage.value = message
  showError.value = true
  setTimeout(() => (showError.value = false), 4000)
}

// ─── API actions ──────────────────────────────────────────────────────────────
const loadProfile = async () => {
  try {
    loading.value = true
    const response = await api.auth.getProfile()
    const userData = response?.user ?? response
    user.value = userData
    formData.value.name = userData.name
    formData.value.email = userData.email
    formData.value.avatarPreview = userData.avatar
      ? `${import.meta.env.VITE_STORAGE_ENDPOINT}/${userData.avatar}`
      : ''
  } catch (error) {
    console.log(error)
    showErrorMessage('Không thể tải thông tin hồ sơ')
  } finally {
    loading.value = false
  }
}


const handleAvatarChange = (event) => {
  const file = event.target.files[0]
  if (!file) return
  selectedFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => (formData.value.avatarPreview = e.target.result)
  reader.readAsDataURL(file)
}

const uploadAvatar = async () => {
  if (!selectedFile.value) return
  try {
    avatarUploading.value = true
    const fd = new FormData()
    fd.append('avatar', selectedFile.value)
    const response = await api.auth.uploadAvatar(fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (response) {
      const userData = response?.user ?? response
      user.value = userData
    }
    selectedFile.value = null
    showSuccessMessage('Profile picture updated successfully')
  } catch (err) {
    showErrorMessage(err.response?.data?.message || 'Failed to update profile picture')
  } finally {
    avatarUploading.value = false
  }
}

const updateProfile = async () => {
  try {
    updateLoading.value = true
    const response = await api.auth.updateProfile({
      name: formData.value.name,
      email: formData.value.email
    })
    if (response) {
      const userData = response?.user ?? response
      user.value = userData
    }
    showSuccessMessage('Profile information updated successfully')
  } catch (err) {
    console.log(err)
    console.log('err.response:', err.response)
    showErrorMessage(err.response?.data?.message || 'Failed to update profile information')
  } finally {
    updateLoading.value = false
  }
}

const changePassword = async () => {
  try {
    passwordChangeLoading.value = true
    await api.auth.changePassword({
      current_password: passwordForm.value.currentPassword,
      password: passwordForm.value.newPassword,
      password_confirmation: passwordForm.value.confirmPassword
    })
    passwordForm.value = { currentPassword: '', newPassword: '', confirmPassword: '' }
    showSuccessMessage('Password changed successfully')
  } catch (err) {
    console.log('changePassword error:', err)
    showErrorMessage(err.response?.data?.message || 'Failed to change password')
  } finally {
    passwordChangeLoading.value = false
  }
}

const resetForm = () => {
  formData.value.name = user.value.name
  formData.value.email = user.value.email
  selectedFile.value = null
}

const togglePasswordVisibility = (field) => {
  showPasswords.value[field] = !showPasswords.value[field]
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────
onMounted(loadProfile)
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>