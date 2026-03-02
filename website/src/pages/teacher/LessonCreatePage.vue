<template>
  <div>
    <!-- Back -->
    <button @click="$router.push({ name: 'TeacherLessons' })"
      class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-6">
      <i class="fas fa-arrow-left"></i> Back to Lessons
    </button>

    <h2 class="text-2xl font-bold text-gray-800 mb-6">Create New Lesson</h2>

    <form @submit.prevent="handleSubmit" class="max-w-3xl space-y-6">
      <!-- Basic Info -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>

        <!-- Class -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Class <span class="text-red-500">*</span>
          </label>
          <select v-model="form.class_id"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
            required>
            <option value="">-- Select a class --</option>
            <option v-for="cls in classes" :key="cls.id" :value="cls.id">
              {{ cls.name }} ({{ cls.code }})
            </option>
          </select>
        </div>

        <!-- Title -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Lesson Title <span class="text-red-500">*</span>
          </label>
          <input v-model="form.title" type="text" placeholder="e.g. Introduction to Machine Learning"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
            required />
        </div>

        <!-- Description -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
          <textarea v-model="form.description" rows="3" placeholder="Brief description of the lesson..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none"></textarea>
        </div>

        <!-- Objectives -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Learning Objectives</label>
          <textarea v-model="form.objectives" rows="3" placeholder="What students will learn from this lesson..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none"></textarea>
        </div>
      </div>

      <!-- Content Input -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Lesson Content</h3>
        <p class="text-sm text-gray-500 mb-4">
          Provide content in any language (Vietnamese, Chinese, English, etc.). AI will generate slides and quiz in
          English.
        </p>

        <!-- Text Content -->
        <div class="mb-5">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Content Text</label>
          <textarea v-model="form.content_text" rows="8"
            placeholder="Paste or type lesson content here in any language..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none font-mono"></textarea>
        </div>

        <!-- File Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Upload Document</label>
          <div
            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors"
            @dragover.prevent @drop.prevent="handleDrop">
            <input ref="fileInput" type="file" class="hidden" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
              @change="handleFileChange" />

            <div v-if="!selectedFile" class="cursor-pointer" @click="$refs.fileInput.click()">
              <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
              <p class="text-sm text-gray-600 font-medium">Click to upload or drag and drop</p>
              <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, PPT, PPTX, TXT (max 20MB)</p>
            </div>

            <div v-else class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <i class="fas fa-file text-2xl text-blue-500"></i>
                <div class="text-left">
                  <p class="text-sm font-medium text-gray-700">{{ selectedFile.name }}</p>
                  <p class="text-xs text-gray-400">{{ formatFileSize(selectedFile.size) }}</p>
                </div>
              </div>
              <button type="button" @click="removeFile" class="text-red-500 hover:text-red-700 transition-colors">
                <i class="fas fa-times-circle text-lg"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- AI Settings -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">AI Generation Settings</h3>
        <p class="text-sm text-gray-500 mb-4">Configure how AI generates slides and quiz questions</p>

        <div class="space-y-4">
          <!-- Generate Slides Toggle -->
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-gray-700">Generate Presentation Slides</p>
              <p class="text-xs text-gray-500">AI will create slides from lesson content in English</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.generate_slides" class="sr-only peer" />
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
              </div>
            </label>
          </div>

          <!-- Slide Count -->
          <div v-if="form.generate_slides" class="pl-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Number of Slides</label>
            <input v-model.number="form.slide_count" type="number" min="3" max="30"
              class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
          </div>

          <!-- Generate Quiz Toggle -->
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div>
              <p class="text-sm font-medium text-gray-700">Generate Quiz Questions</p>
              <p class="text-xs text-gray-500">AI will create multiple-choice questions in English</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.generate_quiz" class="sr-only peer" />
              <div
                class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
              </div>
            </label>
          </div>

          <!-- Question Count -->
          <div v-if="form.generate_quiz" class="pl-4">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Number of Questions</label>
            <input v-model.number="form.question_count" type="number" min="1" max="20"
              class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-4">
        <button type="button" @click="$router.push({ name: 'TeacherLessons' })"
          class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
          Cancel
        </button>
        <button type="submit" :disabled="submitting"
          class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
          <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
          {{ submitting ? 'Creating & Generating AI Content...' : 'Create Lesson' }}
        </button>
      </div>

      <!-- AI Progress Note -->
      <div v-if="submitting" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center gap-3">
          <i class="fas fa-robot text-blue-600 text-lg"></i>
          <div>
            <p class="text-sm font-medium text-blue-700">AI is generating content...</p>
            <p class="text-xs text-blue-500">This may take up to 30-60 seconds. Please don't close the page.</p>
          </div>
        </div>
      </div>
    </form>

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
import { useRouter, useRoute } from 'vue-router'
import { useApi } from '@/plugins/api'

const router = useRouter()
const route = useRoute()
const api = useApi()

const classes = ref([])
const selectedFile = ref(null)
const submitting = ref(false)
const error = ref('')

const form = ref({
  class_id: '',
  title: '',
  description: '',
  objectives: '',
  content_text: '',
  generate_slides: true,
  generate_quiz: true,
  slide_count: 10,
  question_count: 5,
})

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

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    if (file.size > 20 * 1024 * 1024) {
      showToast('File size must not exceed 20MB', 'error')
      return
    }
    selectedFile.value = file
  }
}

const handleDrop = (e) => {
  const file = e.dataTransfer.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const removeFile = () => {
  selectedFile.value = null
  if (document.querySelector('input[type="file"]')) {
    document.querySelector('input[type="file"]').value = ''
  }
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const handleSubmit = async () => {
  if (!form.value.class_id) {
    error.value = 'Please select a class'
    return
  }
  if (!form.value.title.trim()) {
    error.value = 'Please enter a lesson title'
    return
  }
  if (!form.value.content_text.trim() && !selectedFile.value) {
    error.value = 'Please provide lesson content (text or file)'
    return
  }

  submitting.value = true
  error.value = ''

  try {
    const formData = new FormData()
    formData.append('class_id', form.value.class_id)
    formData.append('title', form.value.title)
    if (form.value.description) formData.append('description', form.value.description)
    if (form.value.objectives) formData.append('objectives', form.value.objectives)
    if (form.value.content_text) formData.append('content_text', form.value.content_text)
    formData.append('generate_slides', form.value.generate_slides ? '1' : '0')
    formData.append('generate_quiz', form.value.generate_quiz ? '1' : '0')
    formData.append('slide_count', form.value.slide_count)
    formData.append('question_count', form.value.question_count)
    formData.append('status', 'draft')

    if (selectedFile.value) {
      formData.append('file', selectedFile.value)
    }

    const res = await api.lesson.createLesson(formData)

    if (res.success) {
      showToast('Lesson created successfully!')
      setTimeout(() => {
        router.push({ name: 'TeacherLessonDetail', params: { id: res.data.id } })
      }, 1000)
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors
    if (typeof msg === 'object') {
      error.value = Object.values(msg).flat().join(', ')
    } else {
      error.value = msg || 'Failed to create lesson'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  fetchClasses()
  // Pre-select class from query params
  if (route.query.class_id) {
    form.value.class_id = parseInt(route.query.class_id)
  }
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
</style>
