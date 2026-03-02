<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Quản lý lớp học</h2>
        <p class="text-gray-500 mt-1">Tạo và quản lý các lớp học của bạn</p>
      </div>
      <button @click="showCreateModal = true"
        class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
        <i class="fas fa-plus"></i>
        Tạo lớp học
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
    </div>

    <!-- Empty State -->
    <div v-else-if="classes.length === 0" class="text-center py-16">
      <i class="fas fa-book text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">Chưa có lớp học nào</h3>
      <p class="text-gray-400 mb-6">Tạo lớp học đầu tiên để bắt đầu giảng dạy</p>
      <button @click="showCreateModal = true"
        class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
        <i class="fas fa-plus mr-2"></i>Tạo lớp học
      </button>
    </div>

    <!-- Class List -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="cls in classes" :key="cls.id"
        class="bg-white rounded-xl border border-gray-200 hover:shadow-md transition-all cursor-pointer group"
        @click="goToDetail(cls.id)">
        <!-- Card Header -->
        <div class="p-5 border-b border-gray-100">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-semibold text-gray-800 truncate group-hover:text-blue-600 transition-colors">
                {{ cls.name }}
              </h3>
              <p class="text-sm text-gray-500 mt-0.5">{{ cls.semester || 'Chưa xác định' }}</p>
            </div>
            <span :class="statusClass(cls.status)"
              class="px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0 ml-3">
              {{ statusLabel(cls.status) }}
            </span>
          </div>
          <p class="text-sm text-gray-500 line-clamp-2">{{ cls.description || 'Không có mô tả' }}</p>
        </div>

        <!-- Card Body -->
        <div class="p-5">
          <!-- Class Code -->
          <div class="flex items-center gap-2 mb-4 p-3 bg-blue-50 rounded-lg">
            <i class="fas fa-key text-blue-500"></i>
            <span class="text-sm text-blue-700 font-medium">Mã lớp:</span>
            <code class="text-sm font-mono font-bold text-blue-800 tracking-wider">{{ cls.code }}</code>
            <button @click.stop="copyCode(cls.code)" class="ml-auto text-blue-500 hover:text-blue-700 transition-colors"
              title="Sao chép mã">
              <i class="fas fa-copy"></i>
            </button>
          </div>

          <!-- Stats -->
          <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-1.5 text-gray-600">
              <i class="fas fa-user-graduate text-green-500"></i>
              <span>{{ cls.student_count || 0 }} học sinh</span>
            </div>
            <div v-if="cls.pending_count > 0" class="flex items-center gap-1.5 text-orange-600">
              <i class="fas fa-clock"></i>
              <span>{{ cls.pending_count }} chờ duyệt</span>
            </div>
            <div v-if="cls.max_students" class="flex items-center gap-1.5 text-gray-400 ml-auto">
              <i class="fas fa-users"></i>
              <span>Tối đa {{ cls.max_students }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Class Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/50" @click="closeCreateModal"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-800">Tạo lớp học mới</h3>
            <button @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 transition-colors">
              <i class="fas fa-times text-lg"></i>
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleCreate" class="p-6 space-y-5">
            <!-- Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Tên lớp học <span class="text-red-500">*</span>
              </label>
              <input v-model="form.name" type="text" placeholder="VD: Toán cao cấp - Nhóm 01"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
                required />
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
              <textarea v-model="form.description" rows="3" placeholder="Mô tả ngắn về lớp học..."
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none"></textarea>
            </div>

            <!-- Semester & Max Students -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Học kỳ</label>
                <input v-model="form.semester" type="text" placeholder="VD: HK1 2025-2026"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sĩ số tối đa</label>
                <input v-model.number="form.max_students" type="number" min="1" max="500" placeholder="VD: 50"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái</label>
              <select v-model="form.status"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm">
                <option value="active">Đang hoạt động</option>
                <option value="draft">Bản nháp</option>
              </select>
            </div>

            <!-- Error -->
            <div v-if="createError" class="p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ createError }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
              <button type="button" @click="closeCreateModal"
                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm">
                Hủy
              </button>
              <button type="submit" :disabled="creating"
                class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <i v-if="creating" class="fas fa-spinner fa-spin mr-2"></i>
                {{ creating ? 'Đang tạo...' : 'Tạo lớp học' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Toast notification -->
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
import { useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const router = useRouter()
const api = useApi()

const classes = ref([])
const loading = ref(true)
const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')

const form = ref({
  name: '',
  description: '',
  semester: '',
  max_students: null,
  status: 'active',
})

const toast = ref({ show: false, message: '', type: 'success' })

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const fetchClasses = async () => {
  loading.value = true
  try {
    const res = await api.class.getClasses()
    classes.value = res.data || []
  } catch (err) {
    console.error('Lỗi lấy danh sách lớp:', err)
    showToast('Không thể tải danh sách lớp học', 'error')
  } finally {
    loading.value = false
  }
}

const handleCreate = async () => {
  creating.value = true
  createError.value = ''
  try {
    const payload = { ...form.value }
    if (!payload.max_students) delete payload.max_students
    if (!payload.semester) delete payload.semester
    if (!payload.description) delete payload.description

    await api.class.createClass(payload)
    showToast('Tạo lớp học thành công!')
    closeCreateModal()
    fetchClasses()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors
    if (typeof msg === 'object') {
      createError.value = Object.values(msg).flat().join(', ')
    } else {
      createError.value = msg || 'Có lỗi xảy ra khi tạo lớp học'
    }
  } finally {
    creating.value = false
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  form.value = { name: '', description: '', semester: '', max_students: null, status: 'active' }
  createError.value = ''
}

const goToDetail = (id) => {
  router.push({ name: 'TeacherClassDetail', params: { id } })
}

const copyCode = async (code) => {
  try {
    await navigator.clipboard.writeText(code)
    showToast('Đã sao chép mã lớp!')
  } catch {
    showToast('Không thể sao chép', 'error')
  }
}

const statusLabel = (status) => {
  const labels = { active: 'Hoạt động', draft: 'Bản nháp', archived: 'Lưu trữ' }
  return labels[status] || status
}

const statusClass = (status) => {
  const classes = {
    active: 'bg-green-100 text-green-700',
    draft: 'bg-yellow-100 text-yellow-700',
    archived: 'bg-gray-100 text-gray-600',
  }
  return classes[status] || 'bg-gray-100 text-gray-600'
}

onMounted(fetchClasses)
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
