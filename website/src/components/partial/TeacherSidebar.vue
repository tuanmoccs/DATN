<template>
  <aside :class="[
    'fixed top-0 left-0 z-40 h-screen bg-white border-r border-gray-200 transition-transform duration-300',
    collapsed ? 'w-20' : 'w-64',
  ]">
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
      <router-link v-if="!collapsed" to="/teacher/dashboard" class="flex items-center gap-2">
        <span class="text-xl font-bold text-blue-600">SP</span>
      </router-link>
      <span v-else class="text-xl font-bold text-blue-600 mx-auto"></span>

      <button @click="$emit('toggle')" class="p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="collapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13 5l7 7-7 7M5 5l7 7-7 7" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 19l-7-7 7-7M19 19l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <router-link v-for="item in menuItems" :key="item.path" :to="item.path" :class="[
        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
        isActive(item.path)
          ? 'bg-blue-50 text-blue-700'
          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
        collapsed ? 'justify-center' : '',
      ]" :title="collapsed ? item.label : ''">
        <i :class="[item.icon, 'text-lg flex-shrink-0']"></i>
        <span v-if="!collapsed">{{ item.label }}</span>
      </router-link>
    </nav>

    <!-- Bottom -->
    <div class="border-t border-gray-200 p-3">
      <button @click="handleLogout" :class="[
        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full text-red-600 hover:bg-red-50 transition-colors',
        collapsed ? 'justify-center' : '',
      ]" :title="collapsed ? 'Đăng xuất' : ''">
        <i class="fas fa-sign-out-alt text-lg flex-shrink-0"></i>
        <span v-if="!collapsed">Đăng xuất</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

defineProps({
  collapsed: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['toggle'])

const route = useRoute()
const router = useRouter()
const api = useApi()

const menuItems = [
  { icon: 'fas fa-chart-line', label: 'Dashboard', path: '/teacher/dashboard' },
  { icon: 'fas fa-book', label: 'Lớp học', path: '/teacher/classes' },
  { icon: 'fas fa-file-alt', label: 'Bài học', path: '/teacher/lessons' },
  { icon: 'fas fa-question-circle', label: 'Bài kiểm tra', path: '/teacher/quizzes' },
  { icon: 'fas fa-tasks', label: 'Bài tập', path: '/teacher/assignments' },
  { icon: 'fas fa-user-graduate', label: 'Học sinh', path: '/teacher/students' },
  { icon: 'fas fa-chart-bar', label: 'Báo cáo', path: '/teacher/reports' },
  { icon: 'fas fa-cog', label: 'Cài đặt', path: '/teacher/settings' },
]

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/')
}

const handleLogout = async () => {
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
