<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Lessons</h2>
        <p class="text-gray-500 mt-1">Manage lessons for your classes</p>
      </div>
    </div>

    <!-- Class Selector -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Class</label>
      <select v-model="selectedClassId" @change="fetchLessons"
        class="w-full max-w-md px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm">
        <option value="">-- Choose a class --</option>
        <option v-for="cls in classes" :key="cls.id" :value="cls.id">
          {{ cls.name }} ({{ cls.code }})
        </option>
      </select>
    </div>

    <!-- No class selected -->
    <div v-if="!selectedClassId" class="text-center py-16">
      <i class="fas fa-book-open text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">Select a class to view lessons</h3>
      <p class="text-gray-400">Choose a class from the dropdown above</p>
    </div>

    <template v-else>
      <!-- Action Bar -->
      <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ lessons.length }} lesson(s) found</p>
        <button @click="$router.push({ name: 'TeacherLessonCreate', query: { class_id: selectedClassId } })"
          class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
          <i class="fas fa-plus"></i>
          Create Lesson
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      </div>

      <!-- Empty State -->
      <div v-else-if="lessons.length === 0" class="text-center py-16">
        <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">No lessons yet</h3>
        <p class="text-gray-400 mb-6">Create your first lesson with AI-generated slides and quizzes</p>
        <button @click="$router.push({ name: 'TeacherLessonCreate', query: { class_id: selectedClassId } })"
          class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
          <i class="fas fa-plus mr-2"></i>Create Lesson
        </button>
      </div>

      <!-- Lesson List -->
      <div v-else class="space-y-4">
        <div v-for="lesson in lessons" :key="lesson.id"
          class="bg-white rounded-xl border border-gray-200 hover:shadow-md transition-all cursor-pointer group"
          @click="$router.push({ name: 'TeacherLessonDetail', params: { id: lesson.id } })">
          <div class="p-5">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-1">
                  <span class="text-sm text-gray-400 font-mono">#{{ lesson.order }}</span>
                  <h3 class="text-lg font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors">
                    {{ lesson.title }}
                  </h3>
                </div>
                <p class="text-sm text-gray-500 line-clamp-2">{{ lesson.description || 'No description' }}</p>
              </div>
              <span :class="statusClass(lesson.status)"
                class="px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0 ml-3">
                {{ statusLabel(lesson.status) }}
              </span>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-5 text-sm text-gray-500 mt-3">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-desktop text-blue-500"></i>
                <span>{{ lesson.presentation?.slides?.length || 0 }} slides</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-question-circle text-green-500"></i>
                <span>{{ lesson.quizzes?.length || 0 }} quiz(zes)</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-file text-orange-500"></i>
                <span>{{ lesson.content?.length || 0 }} content(s)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

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
import { useApi } from '@/plugins/api'

const api = useApi()

const classes = ref([])
const lessons = ref([])
const selectedClassId = ref('')
const loading = ref(false)

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const fetchClasses = async () => {
  try {
    const res = await api.class.getClasses()
    classes.value = res.data || []
  } catch (err) {
    showToast('Failed to load classes', 'error')
  }
}

const fetchLessons = async () => {
  if (!selectedClassId.value) {
    lessons.value = []
    return
  }
  loading.value = true
  try {
    const res = await api.lesson.getLessonsByClass(selectedClassId.value)
    lessons.value = res.data || []
  } catch (err) {
    showToast('Failed to load lessons', 'error')
  } finally {
    loading.value = false
  }
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

onMounted(() => {
  fetchClasses()
})
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
