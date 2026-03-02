<template>
  <header class="sticky top-0 z-30 bg-white border-b border-gray-200 h-16 flex items-center justify-between px-6">
    <!-- Left: Page title / Breadcrumb -->
    <div class="flex items-center gap-3">
      <h1 class="text-lg font-semibold text-gray-800">
        {{ pageTitle }}
      </h1>
    </div>

    <!-- Right: User info -->
    <div class="flex items-center gap-4">
      <!-- Notification placeholder -->
      <button class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
      </button>

      <!-- User dropdown -->
      <div class="relative">
        <button @click="dropdownVisible = !dropdownVisible"
          class="flex items-center gap-3 p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none">
          <img :src="avatarUrl" class="w-8 h-8 rounded-full object-cover border border-gray-200"
            @error="onAvatarError" />
          <div v-if="currentUser" class="hidden sm:block text-left">
            <p class="text-sm font-semibold text-gray-700 leading-tight">{{ currentUser.name }}</p>
            <p class="text-xs text-gray-500">Giáo viên</p>
          </div>
          <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': dropdownVisible }" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Dropdown -->
        <Transition enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95">
          <div v-show="dropdownVisible"
            class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
              <p class="text-sm font-semibold text-gray-800">{{ currentUser?.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ currentUser?.email }}</p>
            </div>
            <router-link to="/teacher/settings" @click="dropdownVisible = false"
              class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
              <i class="fas fa-user"></i> Thông tin cá nhân
            </router-link>
            <button @click="handleLogout"
              class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors w-full text-left">
              <i class="fas fa-sign-out-alt"></i> Đăng xuất
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const route = useRoute()
const router = useRouter()
const api = useApi()

const dropdownVisible = ref(false)
const avatarFailed = ref(false)

const DEFAULT_AVATAR = 'https://ui-avatars.com/api/?background=2563EB&color=fff&bold=true&name='

const currentUser = computed(() => {
  const info = localStorage.getItem('user_info')
  return info ? JSON.parse(info) : null
})

const avatarUrl = computed(() => {
  if (avatarFailed.value || !currentUser.value?.avatar) {
    const name = encodeURIComponent(currentUser.value?.name || 'T')
    return DEFAULT_AVATAR + name
  }
  return currentUser.value.avatar
})

const onAvatarError = () => {
  avatarFailed.value = true
}

const pageTitles = {
  '/teacher/dashboard': 'Dashboard',
  '/teacher/classes': 'Classes',
  '/teacher/lessons': 'Lessons',
  '/teacher/quizzes': 'Quizzes',
  '/teacher/assignments': 'Assignments',
  '/teacher/students': 'Students',
  '/teacher/reports': 'Reports',
  '/teacher/settings': 'Settings',
}

const pageTitle = computed(() => {
  for (const [path, title] of Object.entries(pageTitles)) {
    if (route.path === path || route.path.startsWith(path + '/')) {
      return title
    }
  }
  return 'Dashboard'
})

// Close dropdown on outside click
const handleClickOutside = (e) => {
  if (dropdownVisible.value && !e.target.closest('.relative')) {
    dropdownVisible.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))

const handleLogout = async () => {
  dropdownVisible.value = false
  try {
    await api.auth.logout()
  } catch {
    // Bỏ qua lỗi
  } finally {
    localStorage.removeItem('access_token')
    localStorage.removeItem('user_info')
    localStorage.removeItem('token_expired_at')
    router.push('/login')
  }
}
</script>
