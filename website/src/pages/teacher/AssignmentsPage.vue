<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Bài tập</h2>
        <p class="text-gray-500 mt-1">Quản lý bài tập cho các lớp học</p>
      </div>
    </div>

    <!-- Class Selector -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Chọn lớp học</label>
      <select v-model="selectedClassId" @change="fetchAssignments"
        class="w-full max-w-md px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm">
        <option value="">-- Chọn lớp --</option>
        <option v-for="cls in classes" :key="cls.id" :value="cls.id">
          {{ cls.name }} ({{ cls.code }})
        </option>
      </select>
    </div>

    <!-- No class selected -->
    <div v-if="!selectedClassId" class="text-center py-16">
      <i class="fas fa-tasks text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">Chọn lớp để xem bài tập</h3>
      <p class="text-gray-400">Chọn một lớp từ danh sách bên trên</p>
    </div>

    <template v-else>
      <!-- Action Bar -->
      <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">{{ assignments.length }} bài tập</p>
        <button @click="showCreateModal = true"
          class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
          <i class="fas fa-plus"></i>
          Tạo bài tập
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      </div>

      <!-- Empty State -->
      <div v-else-if="assignments.length === 0" class="text-center py-16">
        <i class="fas fa-tasks text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-600 mb-2">Chưa có bài tập</h3>
        <p class="text-gray-400 mb-6">Tạo bài tập đầu tiên cho lớp học</p>
        <button @click="showCreateModal = true"
          class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
          <i class="fas fa-plus mr-2"></i>Tạo bài tập
        </button>
      </div>

      <!-- Assignment List -->
      <div v-else class="space-y-4">
        <div v-for="assignment in assignments" :key="assignment.id"
          class="bg-white rounded-xl border border-gray-200 hover:shadow-md transition-all cursor-pointer group"
          @click="$router.push({ name: 'TeacherAssignmentDetail', params: { id: assignment.id } })">
          <div class="p-5">
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors">
                  {{ assignment.title }}
                </h3>
                <p class="text-sm text-gray-500 line-clamp-2 mt-1">{{ assignment.description || 'Không có mô tả' }}</p>
              </div>
              <span :class="statusClass(assignment.status)"
                class="px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0 ml-3">
                {{ statusLabel(assignment.status) }}
              </span>
            </div>

            <!-- Info -->
            <div class="flex items-center gap-5 text-sm text-gray-500 mt-3">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-clock text-orange-500"></i>
                <span>{{ formatDate(assignment.due_date) }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-star text-yellow-500"></i>
                <span>{{ assignment.max_score }} điểm</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-users text-blue-500"></i>
                <span>{{ assignment.submission_count || 0 }} nộp</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>{{ assignment.graded_count || 0 }} đã chấm</span>
              </div>
              <div class="flex items-center gap-1.5">
                <i class="fas fa-paperclip text-gray-400"></i>
                <span>{{ assignment.files?.length || 0 }} file</span>
              </div>
            </div>

            <!-- Overdue warning -->
            <div v-if="isOverdue(assignment)" class="mt-3 flex items-center gap-1.5 text-sm text-red-500">
              <i class="fas fa-exclamation-triangle"></i>
              <span>Đã quá hạn</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Create Assignment Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b px-6 py-4 rounded-t-2xl z-10">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-800">Tạo bài tập mới</h3>
              <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>
          </div>

          <form @submit.prevent="createAssignment" class="p-6 space-y-5">
            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề *</label>
              <input v-model="form.title" type="text" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                placeholder="Nhập tiêu đề bài tập" />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
              <textarea v-model="form.description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                placeholder="Mô tả ngắn gọn về bài tập"></textarea>
            </div>

            <!-- Instructions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Hướng dẫn làm bài</label>
              <textarea v-model="form.instructions" rows="4"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                placeholder="Hướng dẫn chi tiết cho học sinh"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Due Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hạn nộp</label>
                <input v-model="form.due_date" type="datetime-local"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
              </div>

              <!-- Max Score -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Điểm tối đa</label>
                <input v-model.number="form.max_score" type="number" min="1" max="1000"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <!-- Submission Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại nộp bài</label>
                <select v-model="form.submission_type"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                  <option value="file">File đính kèm</option>
                  <option value="text">Văn bản</option>
                  <option value="both">Cả hai</option>
                </select>
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                <select v-model="form.status"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm">
                  <option value="draft">Nháp</option>
                  <option value="published">Xuất bản</option>
                </select>
              </div>
            </div>

            <!-- Late Submission -->
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.allow_late_submission" type="checkbox"
                  class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500" />
                <span class="text-sm text-gray-700">Cho phép nộp trễ</span>
              </label>
              <div v-if="form.allow_late_submission" class="flex items-center gap-2">
                <input v-model.number="form.late_penalty" type="number" min="0" max="100"
                  class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm" />
                <span class="text-sm text-gray-500">% trừ điểm</span>
              </div>
            </div>

            <!-- Files -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">File đính kèm (tài liệu tham khảo)</label>
              <div
                class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors cursor-pointer"
                @click="$refs.fileInput.click()" @dragover.prevent @drop.prevent="handleDrop">
                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-500">Click hoặc kéo thả file vào đây</p>
                <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, TXT, JPG, PNG (tối đa 20MB/file)</p>
              </div>
              <input ref="fileInput" type="file" multiple class="hidden" @change="handleFileSelect"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif" />
              <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
                <div v-for="(file, index) in selectedFiles" :key="index"
                  class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded-lg">
                  <div class="flex items-center gap-2 text-sm text-gray-600">
                    <i :class="getFileIcon(file.name)" class="text-gray-400"></i>
                    <span class="truncate max-w-xs">{{ file.name }}</span>
                    <span class="text-gray-400">({{ formatFileSize(file.size) }})</span>
                  </div>
                  <button type="button" @click="removeFile(index)" class="text-red-400 hover:text-red-600">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <button type="button" @click="showCreateModal = false"
                class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium">
                Huỷ
              </button>
              <button type="submit" :disabled="creating"
                class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 text-sm font-medium">
                <i v-if="creating" class="fas fa-spinner fa-spin mr-2"></i>
                {{ creating ? 'Đang tạo...' : 'Tạo bài tập' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <div v-if="toast.show"
        class="fixed bottom-6 right-6 z-50 px-6 py-3 rounded-xl shadow-lg text-white text-sm font-medium transition-all"
        :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
        {{ toast.message }}
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useApi } from '@/plugins/api'

const api = useApi()

// State
const classes = ref([])
const selectedClassId = ref('')
const assignments = ref([])
const loading = ref(false)
const creating = ref(false)
const showCreateModal = ref(false)
const selectedFiles = ref([])
const toast = reactive({ show: false, message: '', type: 'success' })

// Form
const form = reactive({
  title: '',
  description: '',
  instructions: '',
  due_date: '',
  max_score: 100,
  submission_type: 'file',
  status: 'draft',
  allow_late_submission: false,
  late_penalty: 0,
})

// Fetch classes on mount
onMounted(async () => {
  try {
    const res = await api.class.getClasses()
    classes.value = res.data || []
  } catch (e) {
    showToast('Lỗi khi tải danh sách lớp', 'error')
  }
})

// Fetch assignments
const fetchAssignments = async () => {
  if (!selectedClassId.value) {
    assignments.value = []
    return
  }

  loading.value = true
  try {
    const res = await api.assignment.getAssignmentsByClass(selectedClassId.value)
    assignments.value = res.data || []
  } catch (e) {
    showToast('Lỗi khi tải bài tập', 'error')
  } finally {
    loading.value = false
  }
}

// Create assignment
const createAssignment = async () => {
  creating.value = true
  try {
    const formData = new FormData()
    formData.append('class_id', selectedClassId.value)
    formData.append('title', form.title)
    if (form.description) formData.append('description', form.description)
    if (form.instructions) formData.append('instructions', form.instructions)
    if (form.due_date) formData.append('due_date', form.due_date)
    formData.append('max_score', form.max_score)
    formData.append('submission_type', form.submission_type)
    formData.append('status', form.status)
    formData.append('allow_late_submission', form.allow_late_submission ? '1' : '0')
    if (form.allow_late_submission) formData.append('late_penalty', form.late_penalty)

    selectedFiles.value.forEach((file) => {
      formData.append('files[]', file)
    })

    await api.assignment.createAssignment(formData)
    showToast('Tạo bài tập thành công')
    showCreateModal.value = false
    resetForm()
    fetchAssignments()
  } catch (e) {
    showToast(e.response?.data?.message || 'Lỗi khi tạo bài tập', 'error')
  } finally {
    creating.value = false
  }
}

// File handling
const handleFileSelect = (e) => {
  const files = Array.from(e.target.files)
  selectedFiles.value.push(...files)
}

const handleDrop = (e) => {
  const files = Array.from(e.dataTransfer.files)
  selectedFiles.value.push(...files)
}

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1)
}

// Reset form
const resetForm = () => {
  form.title = ''
  form.description = ''
  form.instructions = ''
  form.due_date = ''
  form.max_score = 100
  form.submission_type = 'file'
  form.status = 'draft'
  form.allow_late_submission = false
  form.late_penalty = 0
  selectedFiles.value = []
}

// Helpers
const statusClass = (status) => {
  const map = {
    draft: 'bg-gray-100 text-gray-600',
    published: 'bg-green-100 text-green-600',
    closed: 'bg-red-100 text-red-600',
    archived: 'bg-yellow-100 text-yellow-700',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

const statusLabel = (status) => {
  const map = { draft: 'Nháp', published: 'Đã xuất bản', closed: 'Đã đóng', archived: 'Lưu trữ' }
  return map[status] || status
}

const formatDate = (date) => {
  if (!date) return 'Không có hạn'
  return new Date(date).toLocaleString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const isOverdue = (assignment) => {
  if (!assignment.due_date) return false
  return new Date(assignment.due_date) < new Date() && assignment.status !== 'closed'
}

const getFileIcon = (name) => {
  if (!name) return 'fas fa-file'
  const ext = name.split('.').pop().toLowerCase()
  const map = { pdf: 'fas fa-file-pdf text-red-500', doc: 'fas fa-file-word text-blue-500', docx: 'fas fa-file-word text-blue-500', txt: 'fas fa-file-alt', jpg: 'fas fa-file-image text-purple-500', jpeg: 'fas fa-file-image text-purple-500', png: 'fas fa-file-image text-purple-500' }
  return map[ext] || 'fas fa-file'
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

const showToast = (message, type = 'success') => {
  toast.show = true
  toast.message = message
  toast.type = type
  setTimeout(() => { toast.show = false }, 3000)
}
</script>
