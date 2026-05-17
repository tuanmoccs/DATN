<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Manage Students</h2>
        <p class="text-gray-500 mt-1">Manage all students across your classes</p>
      </div>
    </div>

    <!-- Class Selector -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Class</label>
      <select v-model="selectedClassId" @change="handleClassSelect"
        class="w-full max-w-md px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm bg-white">
        <option value="">-- Select Class --</option>
        <option v-for="cls in classes" :key="cls.id" :value="cls.id">
          {{ cls.name }} ({{ cls.code }})
        </option>
      </select>
    </div>

    <!-- No class selected -->
    <div v-if="!selectedClassId" class="text-center py-16">
      <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">Select a class to view students</h3>
      <p class="text-gray-400">Choose a class from the list above</p>
    </div>

    <!-- Content when class selected -->
    <template v-else>
      <!-- Search Bar -->
      <div class="mb-6 flex items-center gap-3">
        <div class="flex-1 max-w-md">
          <div class="relative">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
            <input v-model="searchQuery" type="text" placeholder="Search by student name..."
              class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
          </div>
        </div>
        <span v-if="searchQuery" class="text-sm text-gray-600">
          <strong>{{ allDisplayedStudents.length }}</strong> result(s)
        </span>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      </div>

      <template v-else>
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
                :class="tab.key === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'">
                {{ tab.count }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Pending Students -->
        <div v-if="activeTab === 'pending'">
          <div v-if="pendingStudents.length === 0"
            class="text-center py-12 bg-orange-50 rounded-xl border border-orange-200">
            <i class="fas fa-inbox text-4xl text-orange-300 mb-3"></i>
            <p class="text-gray-500">No pending requests</p>
          </div>

          <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Class
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Request
                    Date
                  </th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in pendingStudents" :key="item.enrollment.id" class="hover:bg-gray-50">
                  <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                        {{ item.enrollment.user?.name?.charAt(0)?.toUpperCase() }}
                      </div>
                      <span class="text-sm font-medium text-gray-800">{{ item.enrollment.user?.name }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3.5 text-sm text-gray-500">{{ item.enrollment.user?.email }}</td>
                  <td class="px-5 py-3.5 text-sm text-gray-700 font-medium">{{ item.class.name }}</td>
                  <td class="px-5 py-3.5 text-sm text-gray-500">{{ formatDate(item.enrollment.created_at) }}</td>
                  <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                      <button @click="handleApprove(item.enrollment.id)"
                        :disabled="actionLoading === item.enrollment.id"
                        class="px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                        <i class="fas fa-check mr-1"></i>Approve
                      </button>
                      <button @click="handleReject(item.enrollment.id)" :disabled="actionLoading === item.enrollment.id"
                        class="px-3 py-1.5 text-xs font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                        <i class="fas fa-times mr-1"></i>Reject
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Approved Students -->
        <template v-if="activeTab === 'approved'">
          <div v-if="approvedStudents.length === 0"
            class="text-center py-12 bg-green-50 rounded-xl border border-green-200">
            <i class="fas fa-users text-4xl text-green-300 mb-3"></i>
            <p class="text-gray-500">No approved students</p>
          </div>

          <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Email
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Class
                  </th>
                  <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined
                    Date
                  </th>
                  <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="(item, index) in approvedStudents" :key="item.enrollment.id" class="hover:bg-gray-50">
                  <td class="px-5 py-3.5 text-sm text-gray-400">{{ index + 1 }}</td>
                  <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 font-semibold text-sm">
                        {{ item.enrollment.user?.name?.charAt(0)?.toUpperCase() }}
                      </div>
                      <span class="text-sm font-medium text-gray-800">{{ item.enrollment.user?.name }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3.5 text-sm text-gray-500">{{ item.enrollment.user?.email }}</td>
                  <td class="px-5 py-3.5 text-sm text-gray-700 font-medium">{{ item.class.name }}</td>
                  <td class="px-5 py-3.5 text-sm text-gray-500">{{ formatDate(item.enrollment.joined_at) }}</td>
                  <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end">
                      <button @click="confirmRemove(item)"
                        class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <i class="fas fa-user-minus mr-1"></i>Remove
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </template>
    </template>

    <!-- Confirm Remove Modal -->
    <Teleport to="body">
      <div v-if="removeTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="removeTarget = null"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
          <h3 class="text-lg font-bold text-gray-800 mb-2">Confirm Delete</h3>
          <p class="text-sm text-gray-500 mb-6">
            Are you sure you want to remove <strong>{{ removeTarget.enrollment.user?.name }}</strong> from
            <strong>{{ removeTarget.class.name }}</strong>?
          </p>
          <div class="flex items-center gap-3">
            <button @click="removeTarget = null"
              class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-medium">
              Cancel
            </button>
            <button @click="handleRemove(removeTarget.enrollment.id)"
              :disabled="actionLoading === removeTarget.enrollment.id"
              class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium disabled:opacity-50">
              <i v-if="actionLoading === removeTarget.enrollment.id" class="fas fa-spinner fa-spin mr-1"></i>
              Remove
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
import { useApi } from '@/plugins/api'

const api = useApi()

const classes = ref([])
const selectedClassId = ref('')
const currentClass = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const activeTab = ref('pending')
const loading = ref(true)
const actionLoading = ref(null)
const removeTarget = ref(null)

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const allStudents = computed(() => {
  if (!currentClass.value) return []

  const enrollments = currentClass.value.enrollment || []
  return enrollments.map(enrollment => ({
    class: currentClass.value,
    enrollment
  }))
})

const allDisplayedStudents = computed(() => {
  // If search is active, use search results; otherwise use all students from current class
  return searchQuery.value.trim() ? searchResults.value : allStudents.value
})

const pendingStudents = computed(() => {
  return allDisplayedStudents.value.filter(item => item.enrollment.status === 'pending')
})

const approvedStudents = computed(() => {
  return allDisplayedStudents.value.filter(item => item.enrollment.status === 'active')
})

const tabs = computed(() => [
  { key: 'pending', label: 'Pending Requests', count: pendingStudents.value.length },
  { key: 'approved', label: 'Approved Students', count: approvedStudents.value.length },
])

// Watch searchQuery - fetch results from API
const searchStudents = async (query) => {
  if (!selectedClassId.value) return

  if (!query.trim()) {
    searchResults.value = []
    return
  }

  try {
    const res = await api.class.searchStudents(selectedClassId.value, query)
    if (res.data && Array.isArray(res.data)) {
      // Format API response to match our template structure
      searchResults.value = res.data.map(enrollment => ({
        class: currentClass.value,
        enrollment
      }))
    }
  } catch (err) {
    console.error('Search error:', err)
    searchResults.value = []
  }
}

watch(searchQuery, (newQuery) => {
  searchStudents(newQuery)
}, { debounce: 300 })

const fetchClasses = async () => {
  try {
    const res = await api.class.getClasses()
    classes.value = res.data
  } catch (err) {
    console.error(err)
    showToast('Unable to load classes', 'error')
  }
}

const handleClassSelect = async () => {
  if (!selectedClassId.value) {
    currentClass.value = null
    activeTab.value = 'pending'
    searchQuery.value = ''
    searchResults.value = []
    return
  }

  loading.value = true
  try {
    const res = await api.class.getClassDetail(selectedClassId.value)
    currentClass.value = res.data
    activeTab.value = 'pending'
    searchQuery.value = ''
    searchResults.value = []
  } catch (err) {
    console.error(err)
    showToast('Unable to load class details', 'error')
    selectedClassId.value = ''
    currentClass.value = null
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

const handleApprove = async (enrollmentId) => {
  actionLoading.value = enrollmentId
  try {
    await api.class.approveEnrollment(enrollmentId)
    showToast('Student approved')
    handleClassSelect()
  } catch (err) {
    showToast(err.response?.data?.message || 'Error approving student', 'error')
  } finally {
    actionLoading.value = null
  }
}

const handleReject = async (enrollmentId) => {
  actionLoading.value = enrollmentId
  try {
    await api.class.rejectEnrollment(enrollmentId)
    showToast('Request rejected')
    handleClassSelect()
  } catch (err) {
    showToast(err.response?.data?.message || 'Error rejecting request', 'error')
  } finally {
    actionLoading.value = null
  }
}

const confirmRemove = (item) => {
  removeTarget.value = item
}

const handleRemove = async (enrollmentId) => {
  try {
    await api.class.removeStudent(enrollmentId)
    showToast('Student removed')
    removeTarget.value = null
    handleClassSelect()
  } catch (err) {
    showToast(err.response?.data?.message || 'Error removing student', 'error')
  } finally {
    actionLoading.value = null
  }
}

const formatDate = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(() => {
  loading.value = true
  fetchClasses().finally(() => {
    loading.value = false
  })
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
