<template>
  <header class="sticky top-0 z-50 bg-white shadow">
    <div class="max-w-7xl mx-auto px-6">
      <nav class="flex items-center justify-between h-16">

        <!-- Logo -->
        <router-link to="/" class="flex items-center gap-2">
          <span class="text-2xl font-bold text-blue-600">ELearning</span>
        </router-link>

        <!-- Desktop Menu -->
        <ul class="hidden md:flex items-center gap-8 text-gray-700 font-medium">
          <li><router-link to="/" class="nav-link">Home</router-link></li>
          <li><router-link to="/listening-tests" class="nav-link">Listening</router-link></li>
          <li><router-link to="/tests" class="nav-link">Reading</router-link></li>
          <li><router-link to="/speaking-with-ai" class="nav-link">Speaking</router-link></li>
          <li><router-link to="/" class="nav-link">Writing</router-link></li>

          <!-- Auth -->
          <template v-if="!isLogged">
            <li><router-link to="/register" class="nav-link">Register</router-link></li>
            <li><router-link to="/login" class="nav-link">Login</router-link></li>
          </template>

          <!-- User Dropdown -->
          <li v-else class="relative">
            <button @click="toggleDropdown" class="flex items-center gap-2 focus:outline-none">
              <img :src="getAvatarUrl()" class="w-9 h-9 rounded-full object-cover border" />
              <span class="text-sm font-semibold">
                {{ currentUser?.name || 'User' }}
              </span>
              <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': dropdownVisible }" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div v-show="dropdownVisible"
              class="absolute right-0 mt-3 w-52 bg-white border rounded-lg shadow-lg overflow-hidden">
              <router-link to="/profile" @click="closeDropdown" class="dropdown-item">
                👤 Thông tin cá nhân
              </router-link>

              <button @click="handleLogout" class="dropdown-item text-red-600">
                🚪 Đăng xuất
              </button>
            </div>
          </li>
        </ul>

        <!-- Mobile Menu Button -->
        <button class="md:hidden" @click="mobileMenu = !mobileMenu">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </nav>

      <!-- Mobile Menu -->
      <div v-show="mobileMenu" class="md:hidden pb-4">
        <ul class="flex flex-col gap-4 text-gray-700 font-medium">
          <li><router-link to="/" class="nav-link">Home</router-link></li>
          <li><router-link to="/listening-tests" class="nav-link">Listening</router-link></li>
          <li><router-link to="/tests" class="nav-link">Reading</router-link></li>
          <li><router-link to="/speaking-with-ai" class="nav-link">Speaking</router-link></li>
          <li><router-link to="/" class="nav-link">Writing</router-link></li>

          <template v-if="!isLogged">
            <li><router-link to="/register" class="nav-link">Register</router-link></li>
            <li><router-link to="/login" class="nav-link">Login</router-link></li>
          </template>
        </ul>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Mock state (thay bằng Pinia/Auth Store của bạn)
const isLogged = ref(true)
const currentUser = ref({
  name: 'Tuấn Anh',
  avatar: null
})

const dropdownVisible = ref(false)
const mobileMenu = ref(false)

const toggleDropdown = () => {
  dropdownVisible.value = !dropdownVisible.value
}

const closeDropdown = () => {
  dropdownVisible.value = false
}

const getAvatarUrl = () => {
  return currentUser.value?.avatar || 'https://i.pravatar.cc/150'
}

const handleLogout = () => {
  closeDropdown()
  isLogged.value = false
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply hover:text-blue-600 transition-colors;
}

.dropdown-item {
  @apply flex items-center gap-2 px-4 py-3 hover:bg-gray-100 text-sm w-full text-left;
}
</style>
