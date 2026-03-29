<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Prepare Lesson Plans</h2>
        <p class="text-gray-500 mt-1">Create and manage lesson plans with AI assistance</p>
      </div>
      <button @click="showCreateModal = true"
        class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm">
        <i class="fas fa-plus"></i>
        Create New Lesson Plan
      </button>
    </div>

    <!-- Search -->
    <div class="mb-6">
      <div class="relative max-w-md">
        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
        <input v-model="searchQuery" type="text" placeholder="Search lesson plans..."
          class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredPlans.length === 0" class="text-center py-16">
      <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">
        {{ searchQuery ? 'No lesson plans found' : 'No lesson plans yet' }}
      </h3>
      <p class="text-gray-400">{{ searchQuery ? 'Try searching with different keywords' : `Get started by creating your
        first lesson plan` }}</p>
    </div>

    <!-- Plans Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="plan in filteredPlans" :key="plan.id"
        class="bg-white border border-gray-200 rounded-lg p-5 hover:shadow-md transition-shadow cursor-pointer group"
        @click="$router.push({ name: 'TeacherLessonPlanEditor', params: { id: plan.id } })">
        <div class="flex items-start justify-between mb-3">
          <h3 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors line-clamp-2">
            {{ plan.title }}
          </h3>
          <span :class="statusClass(plan.status)" class="text-xs px-2 py-1 rounded-full whitespace-nowrap ml-2">
            {{ plan.status === 'draft' ? 'Draft' : 'Completed' }}
          </span>
        </div>
        <div class="space-y-1 text-sm text-gray-500">
          <p v-if="plan.subject"><i class="fas fa-book-open mr-1.5 w-4 text-center"></i>{{ plan.subject }}</p>
          <p v-if="plan.grade_level"><i class="fas fa-graduation-cap mr-1.5 w-4 text-center"></i>{{ plan.grade_level }}
          </p>
          <p><i class="fas fa-clock mr-1.5 w-4 text-center"></i>{{ formatDate(plan.updated_at) }}</p>
        </div>
        <div class="mt-4 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <button @click.stop="$router.push({ name: 'TeacherLessonPlanEditor', params: { id: plan.id } })"
            class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors">
            <i class="fas fa-edit mr-1"></i> Edit
          </button>
          <button @click.stop="confirmDelete(plan)"
            class="text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded hover:bg-red-100 transition-colors">
            <i class="fas fa-trash mr-1"></i> Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="showCreateModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 mx-4">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Create New Lesson Plan</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Lesson Plan Name *</label>
              <input v-model="newPlan.title" type="text" placeholder="e.g., Math Lesson for Grade 10 - Topic 5"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
              <input v-model="newPlan.subject" type="text" placeholder="e.g., Math, Science, History..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level</label>
              <input v-model="newPlan.grade_level" type="text" placeholder="e.g., 10th Grade, 12th Grade..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
            </div>
          </div>
          <div class="flex items-center justify-end gap-3 mt-6">
            <button @click="showCreateModal = false"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
              Cancel
            </button>
            <button @click="createPlan" :disabled="!newPlan.title.trim() || creating"
              class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <i v-if="creating" class="fas fa-spinner fa-spin mr-1"></i>
              Create Lesson Plan
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <Teleport to="body">
      <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
        <div v-if="toast.show" :class="['fixed bottom-6 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm',
          toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
          {{ toast.message }}
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'

const api = useApi()
const router = useRouter()

const plans = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showCreateModal = ref(false)
const creating = ref(false)
const newPlan = ref({ title: '', subject: '', grade_level: '' })
const toast = ref({ show: false, message: '', type: 'success' })

const filteredPlans = computed(() => {
  if (!searchQuery.value.trim()) return plans.value
  const q = searchQuery.value.toLowerCase()
  return plans.value.filter(p =>
    p.title.toLowerCase().includes(q) ||
    (p.subject && p.subject.toLowerCase().includes(q))
  )
})

function statusClass(status) {
  return status === 'completed'
    ? 'bg-green-100 text-green-700'
    : 'bg-yellow-100 text-yellow-700'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchPlans() {
  loading.value = true
  try {
    const res = await api.lessonPlan.getAll()
    plans.value = res.data || []
  } catch (err) {
    showToast('Error occurred while loading lesson plans', 'error')
  } finally {
    loading.value = false
  }
}

async function createPlan() {
  if (!newPlan.value.title.trim()) return
  creating.value = true
  try {
    const res = await api.lessonPlan.create(newPlan.value)
    showCreateModal.value = false
    newPlan.value = { title: '', subject: '', grade_level: '' }
    // Navigate to editor
    router.push({ name: 'TeacherLessonPlanEditor', params: { id: res.data.id } })
  } catch (err) {
    showToast(err?.response?.data?.message || 'Error occurred while creating lesson plan', 'error')
  } finally {
    creating.value = false
  }
}

async function confirmDelete(plan) {
  if (!confirm(`Delete lesson plan "${plan.title}"?`)) return
  try {
    await api.lessonPlan.delete(plan.id)
    plans.value = plans.value.filter(p => p.id !== plan.id)
    showToast('Lesson plan deleted successfully', 'success')
  } catch (err) {
    showToast('Error occurred while deleting lesson plan', 'error')
  }
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

onMounted(fetchPlans)
</script>
