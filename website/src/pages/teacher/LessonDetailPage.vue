<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
    </div>

    <template v-else-if="lesson">
      <!-- Back -->
      <button @click="$router.push({ name: 'TeacherLessons' })"
        class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-4">
        <i class="fas fa-arrow-left"></i> Back to Lessons
      </button>

      <!-- Header -->
      <div class="flex items-start justify-between mb-6">
        <div>
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-400 font-mono">#{{ lesson.order }}</span>
            <h2 class="text-2xl font-bold text-gray-800">{{ lesson.title }}</h2>
          </div>
          <p class="text-gray-500 mt-1">{{ lesson.description || 'No description' }}</p>
        </div>
        <span :class="statusClass(lesson.status)" class="px-3 py-1.5 rounded-full text-xs font-medium">
          {{ statusLabel(lesson.status) }}
        </span>
      </div>

      <!-- Info Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
          <p class="text-xs text-blue-600 font-medium mb-1">Slides</p>
          <p class="text-2xl font-bold text-blue-800">{{ lesson.presentation?.slides?.length || 0 }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
          <p class="text-xs text-green-600 font-medium mb-1">Quizzes</p>
          <p class="text-2xl font-bold text-green-800">{{ lesson.quizzes?.length || 0 }}</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
          <p class="text-xs text-orange-600 font-medium mb-1">Content Items</p>
          <p class="text-2xl font-bold text-orange-800">{{ lesson.content?.length || 0 }}</p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-500 font-medium mb-1">Objectives</p>
          <p class="text-sm text-gray-700 line-clamp-3">{{ lesson.objectives || 'Not set' }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 mb-6">
        <nav class="flex gap-6">
          <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
            'pb-3 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.key
              ? 'border-blue-600 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700',
          ]">
            <i :class="tab.icon" class="mr-1.5"></i>
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab: Slides -->
      <div v-if="activeTab === 'slides'">
        <SlidePreview :slides="lesson.presentation?.slides || []" :lesson-id="lesson.id"
          @regenerate="handleRegenerateSlides" @toast="showToast" />
      </div>

      <!-- Tab: Quizzes -->
      <div v-if="activeTab === 'quizzes'">
        <QuizList :quizzes="lesson.quizzes || []" :lesson-id="lesson.id" @refresh="fetchLesson" @toast="showToast" />
      </div>

      <!-- Tab: Content -->
      <div v-if="activeTab === 'content'">
        <LessonContentTab :contents="lesson.content || []" />
      </div>

      <!-- Tab: Settings -->
      <div v-if="activeTab === 'settings'">
        <LessonSettings :lesson="lesson" @refresh="fetchLesson" @toast="showToast" />
      </div>
    </template>

    <!-- Not found -->
    <div v-else class="text-center py-16">
      <i class="fas fa-exclamation-circle text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600">Lesson not found</h3>
    </div>

    <!-- Toast -->
    <Teleport to="body">
      <Transition name="toast">
        <div v-if="toast.show"
          class="fixed top-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-lg shadow-lg text-white text-sm font-medium"
          :class="toast.type === 'success' ? 'bg-green-600' : 'bg-red-600'">
          <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
          {{ toast.message }}
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '@/plugins/api'
import SlidePreview from '@/components/lesson/SlidePreview.vue'
import QuizList from '@/components/lesson/QuizList.vue'
import LessonContentTab from '@/components/lesson/LessonContentTab.vue'
import LessonSettings from '@/components/lesson/LessonSettings.vue'

const route = useRoute()
const api = useApi()

const lesson = ref(null)
const loading = ref(true)
const activeTab = ref('slides')

const tabs = [
  { key: 'slides', label: 'Slides', icon: 'fas fa-desktop' },
  { key: 'quizzes', label: 'Quizzes', icon: 'fas fa-question-circle' },
  { key: 'content', label: 'Content', icon: 'fas fa-file-alt' },
  { key: 'settings', label: 'Settings', icon: 'fas fa-cog' },
]

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const fetchLesson = async () => {
  loading.value = true
  try {
    const res = await api.lesson.getLessonDetail(route.params.id)
    lesson.value = res.data
  } catch (err) {
    showToast('Failed to load lesson details', 'error')
  } finally {
    loading.value = false
  }
}

const handleRegenerateSlides = async () => {
  await fetchLesson()
}

const statusLabel = (status) => {
  const labels = { published: 'Published', draft: 'Draft' }
  return labels[status] || status
}

const statusClass = (status) => {
  const map = {
    published: 'bg-green-100 text-green-700',
    draft: 'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

onMounted(fetchLesson)
</script>

<style scoped>
.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
  animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }

  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }

  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
