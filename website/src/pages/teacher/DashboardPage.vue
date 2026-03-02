<template>
  <div>
    <!-- Welcome -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-800">
        Xin chào, {{ userName }}! 👋
      </h2>
      <p class="text-gray-500 mt-1">Chào mừng bạn quay trở lại hệ thống.</p>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="stat in stats" :key="stat.label"
        class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500 font-medium">{{ stat.label }}</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ stat.value }}</p>
          </div>
          <i :class="[stat.icon, 'text-lg flex-shrink-0']"></i>
        </div>
      </div>
    </div>

    <!-- Placeholder content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Hoạt động gần đây</h3>
        <p class="text-gray-400 text-sm">Chưa có hoạt động nào.</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Lớp học của bạn</h3>
        <p class="text-gray-400 text-sm">Chưa có lớp học nào.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const userName = computed(() => {
  const info = localStorage.getItem('user_info')
  if (info) {
    return JSON.parse(info).name || 'Giáo viên'
  }
  return 'Giáo viên'
})

const stats = [
  { icon: 'fas fa-book', label: 'Lớp học', value: 0 },
  { icon: 'fas fa-user-graduate', label: 'Học sinh', value: 0 },
  { icon: 'fas fa-book-open', label: 'Bài học', value: 0 },
  { icon: 'fas fa-question-circle', label: 'Bài kiểm tra', value: 0 },
]
</script>