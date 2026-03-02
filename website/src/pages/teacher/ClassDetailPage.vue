<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
    </div>

    <template v-else-if="classData">
      <!-- Back + Header -->
      <div class="mb-6">
        <button @click="$router.push({ name: 'TeacherClasses' })"
          class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-4">
          <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </button>

        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ classData.name }}</h2>
            <p class="text-gray-500 mt-1">{{ classData.semester || 'Chưa xác định học kỳ' }}</p>
          </div>
          <span :class="statusClass(classData.status)" class="px-3 py-1.5 rounded-full text-xs font-medium">
            {{ statusLabel(classData.status) }}
          </span>
        </div>
      </div>

      <!-- Info Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Class Code -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-blue-600 font-medium mb-1">Mã lớp học</p>
              <code class="text-2xl font-mono font-bold text-blue-800 tracking-wider">{{ classData.code }}</code>
            </div>
            <button @click="copyCode(classData.code)"
              class="w-9 h-9 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-200 transition-colors">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>

        <!-- Student Count -->
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
          <p class="text-xs text-green-600 font-medium mb-1">Học sinh</p>
          <p class="text-2xl font-bold text-green-800">{{ classData.student_count || 0 }}
            <span v-if="classData.max_students" class="text-sm font-normal text-green-600">/ {{
              classData.max_students }}</span>
          </p>
        </div>

        <!-- Pending -->
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
          <p class="text-xs text-orange-600 font-medium mb-1">Chờ duyệt</p>
          <p class="text-2xl font-bold text-orange-800">{{ classData.pending_count || 0 }}</p>
        </div>

        <!-- Description -->
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
          <p class="text-xs text-gray-500 font-medium mb-1">Mô tả</p>
          <p class="text-sm text-gray-700 line-clamp-3">{{ classData.description || 'Không có mô tả' }}</p>
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
            {{ tab.label }}
            <span v-if="tab.count > 0" class="ml-1.5 px-2 py-0.5 text-xs rounded-full"
              :class="tab.key === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Tab: Pending Requests -->
      <div v-if="activeTab === 'pending'">
        <div v-if="pendingEnrollments.length === 0" class="text-center py-12">
          <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
          <p class="text-gray-500">Không có yêu cầu nào đang chờ duyệt</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Học sinh
                </th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày gửi
                </th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hành động
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="enrollment in pendingEnrollments" :key="enrollment.id" class="hover:bg-gray-50">
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                      {{ enrollment.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <span class="text-sm font-medium text-gray-800">{{ enrollment.user?.name }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-500">{{ enrollment.user?.email }}</td>
                <td class="px-5 py-3.5 text-sm text-gray-500">{{ formatDate(enrollment.created_at) }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="handleApprove(enrollment.id)" :disabled="actionLoading === enrollment.id"
                      class="px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                      <i class="fas fa-check mr-1"></i>Duyệt
                    </button>
                    <button @click="handleReject(enrollment.id)" :disabled="actionLoading === enrollment.id"
                      class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                      <i class="fas fa-times mr-1"></i>Từ chối
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Active Students -->
      <div v-if="activeTab === 'students'">
        <div v-if="activeEnrollments.length === 0" class="text-center py-12">
          <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
          <p class="text-gray-500">Chưa có học sinh nào trong lớp</p>
        </div>

        <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Học sinh
                </th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ngày tham
                  gia</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Hành động
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(enrollment, index) in activeEnrollments" :key="enrollment.id" class="hover:bg-gray-50">
                <td class="px-5 py-3.5 text-sm text-gray-400">{{ index + 1 }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-semibold text-sm">
                      {{ enrollment.user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <span class="text-sm font-medium text-gray-800">{{ enrollment.user?.name }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-sm text-gray-500">{{ enrollment.user?.email }}</td>
                <td class="px-5 py-3.5 text-sm text-gray-500">{{ formatDate(enrollment.joined_at) }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center justify-end">
                    <button @click="confirmRemove(enrollment)"
                      class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                      <i class="fas fa-user-minus mr-1"></i>Xóa khỏi lớp
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Settings -->
      <div v-if="activeTab === 'settings'">
        <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-2xl">
          <h3 class="text-lg font-semibold text-gray-800 mb-6">Chỉnh sửa lớp học</h3>
          <form @submit.prevent="handleUpdate" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Tên lớp học</label>
              <input v-model="editForm.name" type="text"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Mô tả</label>
              <textarea v-model="editForm.description" rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Học kỳ</label>
                <input v-model="editForm.semester" type="text"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Sĩ số tối đa</label>
                <input v-model.number="editForm.max_students" type="number" min="1" max="500"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Trạng thái</label>
              <select v-model="editForm.status"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm">
                <option value="active">Đang hoạt động</option>
                <option value="draft">Bản nháp</option>
                <option value="archived">Lưu trữ</option>
              </select>
            </div>

            <div v-if="updateError" class="p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ updateError }}</p>
            </div>

            <div class="flex items-center gap-3 pt-2">
              <button type="submit" :disabled="updating"
                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm disabled:opacity-50">
                <i v-if="updating" class="fas fa-spinner fa-spin mr-2"></i>
                {{ updating ? 'Đang lưu...' : 'Lưu thay đổi' }}
              </button>
            </div>
          </form>

          <!-- Danger Zone -->
          <div class="mt-10 pt-6 border-t border-red-200">
            <h4 class="text-sm font-semibold text-red-600 mb-3">Vùng nguy hiểm</h4>
            <button @click="handleDelete"
              class="px-4 py-2 text-sm font-medium text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition-colors">
              <i class="fas fa-trash mr-2"></i>Xóa lớp học
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Confirm Remove Modal -->
    <Teleport to="body">
      <div v-if="removeTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="removeTarget = null"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-2">Xác nhận xóa</h3>
          <p class="text-sm text-gray-500 mb-6">
            Bạn có chắc muốn xóa <strong>{{ removeTarget.user?.name }}</strong> khỏi lớp học?
          </p>
          <div class="flex items-center gap-3">
            <button @click="removeTarget = null"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
              Hủy
            </button>
            <button @click="handleRemove(removeTarget.id)"
              class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
              Xóa
            </button>
          </div>
        </div>
      </div>
    </Teleport>

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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const props = defineProps({ id: [String, Number] })

const route = useRoute()
const router = useRouter()
const api = useApi()

const classData = ref(null)
const loading = ref(true)
const activeTab = ref('pending')
const actionLoading = ref(null)
const removeTarget = ref(null)
const updating = ref(false)
const updateError = ref('')

const editForm = ref({ name: '', description: '', semester: '', max_students: null, status: 'active' })

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const pendingEnrollments = computed(() =>
  (classData.value?.enrollment || []).filter(e => e.status === 'pending')
)
const activeEnrollments = computed(() =>
  (classData.value?.enrollment || []).filter(e => e.status === 'active')
)

const tabs = computed(() => [
  { key: 'pending', label: 'Yêu cầu tham gia', count: pendingEnrollments.value.length },
  { key: 'students', label: 'Danh sách học sinh', count: activeEnrollments.value.length },
  { key: 'settings', label: 'Cài đặt', count: 0 },
])

const fetchClassDetail = async () => {
  loading.value = true
  try {
    const classId = props.id || route.params.id
    const res = await api.class.getClassDetail(classId)
    classData.value = res.data

    // Populate edit form
    editForm.value = {
      name: classData.value.name,
      description: classData.value.description || '',
      semester: classData.value.semester || '',
      max_students: classData.value.max_students,
      status: classData.value.status,
    }
  } catch (err) {
    console.error(err)
    showToast('Không thể tải thông tin lớp học', 'error')
  } finally {
    loading.value = false
  }
}

const handleApprove = async (enrollmentId) => {
  actionLoading.value = enrollmentId
  try {
    await api.class.approveEnrollment(enrollmentId)
    showToast('Đã duyệt học sinh vào lớp')
    fetchClassDetail()
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi khi duyệt', 'error')
  } finally {
    actionLoading.value = null
  }
}

const handleReject = async (enrollmentId) => {
  actionLoading.value = enrollmentId
  try {
    await api.class.rejectEnrollment(enrollmentId)
    showToast('Đã từ chối yêu cầu')
    fetchClassDetail()
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi khi từ chối', 'error')
  } finally {
    actionLoading.value = null
  }
}

const confirmRemove = (enrollment) => {
  removeTarget.value = enrollment
}

const handleRemove = async (enrollmentId) => {
  try {
    await api.class.removeStudent(enrollmentId)
    showToast('Đã xóa học sinh khỏi lớp')
    removeTarget.value = null
    fetchClassDetail()
  } catch (err) {
    showToast(err.response?.data?.message || 'Lỗi khi xóa', 'error')
  }
}

const handleUpdate = async () => {
  updating.value = true
  updateError.value = ''
  try {
    const classId = props.id || route.params.id
    await api.class.updateClass(classId, editForm.value)
    showToast('Cập nhật thành công!')
    fetchClassDetail()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors
    updateError.value = typeof msg === 'object' ? Object.values(msg).flat().join(', ') : (msg || 'Có lỗi xảy ra')
  } finally {
    updating.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Bạn có chắc chắn muốn xóa lớp học này? Hành động này không thể hoàn tác.')) return
  try {
    const classId = props.id || route.params.id
    await api.class.deleteClass(classId)
    showToast('Đã xóa lớp học')
    router.push({ name: 'TeacherClasses' })
  } catch (err) {
    showToast(err.response?.data?.message || 'Không thể xóa lớp học', 'error')
  }
}

const copyCode = async (code) => {
  try {
    await navigator.clipboard.writeText(code)
    showToast('Đã sao chép mã lớp!')
  } catch {
    showToast('Không thể sao chép', 'error')
  }
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const statusLabel = (status) => {
  const labels = { active: 'Hoạt động', draft: 'Bản nháp', archived: 'Lưu trữ' }
  return labels[status] || status
}

const statusClass = (status) => {
  const cls = { active: 'bg-green-100 text-green-700', draft: 'bg-yellow-100 text-yellow-700', archived: 'bg-gray-100 text-gray-600' }
  return cls[status] || 'bg-gray-100 text-gray-600'
}

onMounted(fetchClassDetail)
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
