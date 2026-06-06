<template>
  <div class="space-y-6">
    <!-- Basic Info -->
    <div class="bg-white border border-gray-200 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Lesson Information</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
          <input v-model="form.title" type="text"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea v-model="form.description" rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Objectives</label>
          <textarea v-model="form.objectives" rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select v-model="form.status"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
            <option value="draft">Draft</option>
            <option value="published">Published</option>
            <option value="archived">Archived</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
          <input v-model.number="form.order" type="number" min="0"
            class="w-32 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Lesson Content (Text)</label>
          <textarea v-model="form.content_text" rows="5"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none font-mono"
            placeholder="Enter lesson content text..."></textarea>
          <p class="text-xs text-gray-500 mt-1">Lesson content displayed to students</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload Lesson Files</label>
          <input type="file" multiple @change="handleFileSelect"
            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none cursor-pointer" />
          <p class="text-xs text-gray-500 mt-1">You can upload PDF, images, documents, etc.</p>
          <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
            <p class="text-xs font-medium text-gray-600">Selected Files:</p>
            <div v-for="(file, idx) in selectedFiles" :key="idx"
              class="flex items-center justify-between bg-blue-50 p-2 rounded text-xs">
              <span class="text-gray-700 truncate">{{ file.name }}</span>
              <button type="button" @click="removeFile(idx)" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-6 flex justify-end">
        <button @click="saveLesson" :disabled="saving"
          class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
          <i v-if="saving" class="fas fa-spinner fa-spin mr-1"></i>
          Save Changes
        </button>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white border border-red-200 rounded-xl p-6">
      <h3 class="text-lg font-semibold text-red-600 mb-2">Danger Zone</h3>
      <p class="text-sm text-gray-600 mb-4">Once you delete this lesson, all associated slides, quizzes, and content
        will be permanently removed.</p>
      <button v-if="!confirmDelete" @click="confirmDelete = true"
        class="px-4 py-2 border border-red-500 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
        Delete Lesson
      </button>
      <div v-else class="flex items-center gap-3">
        <span class="text-sm text-red-600 font-medium">Are you sure?</span>
        <button @click="deleteLesson"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
          Yes, Delete
        </button>
        <button @click="confirmDelete = false"
          class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { useApi } from '@/plugins/api'
import { useRouter } from 'vue-router'

const props = defineProps({
  lesson: { type: Object, required: true },
})
const emit = defineEmits(['refresh', 'toast'])

const api = useApi()
const router = useRouter()

const saving = ref(false)
const confirmDelete = ref(false)

const form = reactive({
  title: '',
  description: '',
  objectives: '',
  status: 'draft',
  order: 0,
  content_text: '',
})

const selectedFiles = ref([])

watch(() => props.lesson, (val) => {
  if (val) {
    form.title = val.title || ''
    form.description = val.description || ''
    form.objectives = val.objectives || ''
    form.status = val.status || 'draft'
    form.order = val.order ?? 0
    form.content_text = val.lessonContents?.find(c => c.type === 'text')?.content_text || ''
  }
  selectedFiles.value = []
}, { immediate: true })

const handleFileSelect = (e) => {
  selectedFiles.value = Array.from(e.target.files || [])
}

const removeFile = (idx) => {
  selectedFiles.value.splice(idx, 1)
}

const saveLesson = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('title', form.title)
    formData.append('description', form.description)
    formData.append('objectives', form.objectives)
    formData.append('status', form.status)
    formData.append('order', form.order)
    formData.append('content_text', form.content_text)

    // Add selected files
    selectedFiles.value.forEach(file => {
      formData.append('files[]', file)
    })

    await api.lesson.updateLesson(props.lesson.id, formData)
    emit('toast','Lesson updated successfully.')
    emit('refresh')
  } catch (err) {
    emit('toast', { type: 'error', message: err.response?.data?.message || 'Failed to update lesson.' })
  } finally {
    saving.value = false
  }
}

const deleteLesson = async () => {
  try {
    await api.lesson.deleteLesson(props.lesson.id)
    emit('toast', { type: 'success', message: 'Lesson deleted.' })
    router.push({ name: 'TeacherLessons' })
  } catch {
    emit('toast', { type: 'error', message: 'Failed to delete lesson.' })
  }
}
</script>
