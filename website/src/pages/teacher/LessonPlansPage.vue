<template>
  <div>
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Prepare Lesson Plans</h2>
        <p class="text-gray-500 mt-1">Create and manage lesson plans with AI assistance</p>
      </div>
      <button @click="openCreateModal"
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
      <p class="text-gray-400">
        {{ searchQuery ? 'Try searching with different keywords' : 'Get started by creating your first lesson plan' }}
      </p>
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
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="closeCreateModal">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
          <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-200">
            <div>
              <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Step {{ createStep }} / 2</p>
              <h3 class="text-lg font-semibold text-gray-800 mt-1">Create New Lesson Plan</h3>
              <p class="text-sm text-gray-500 mt-1">
                {{ createStep === 1
                  ? 'Choose how you want to get started'
                  : 'Enter the basic information for your lesson' }}
              </p>
            </div>
            <button @click="closeCreateModal"
              class="w-9 h-9 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="p-6">
            <div v-if="createStep === 1" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <button v-for="option in startOptions" :key="option.id" @click="newPlan.startMode = option.id" :class="[
                'relative text-left border-2 rounded-xl p-5 transition-all',
                newPlan.startMode === option.id
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50',
              ]">
                <span v-if="option.recommended"
                  class="absolute top-3 right-3 text-[10px] font-semibold px-2 py-1 rounded-full bg-blue-600 text-white">
                  Recommended
                </span>
                <span
                  class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-blue-600 mb-4">
                  <i :class="option.icon"></i>
                </span>
                <strong class="block text-sm text-gray-800">{{ option.title }}</strong>
                <span class="block text-xs leading-5 text-gray-500 mt-2">{{ option.description }}</span>
              </button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên bài dạy <span
                    class="text-red-500">*</span></label>
                <input v-model="newPlan.title" type="text" placeholder="Ví dụ: Định luật bảo toàn năng lượng"
                  class="form-input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Môn học</label>
                <input v-model="newPlan.subject" type="text" placeholder="Ví dụ: Vật lý" class="form-input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Khối lớp</label>
                <input v-model="newPlan.grade_level" type="text" placeholder="Ví dụ: Lớp 10" class="form-input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chủ đề</label>
                <input v-model="newPlan.topic" type="text" placeholder="Ví dụ: Năng lượng" class="form-input" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Thời lượng</label>
                <input v-model="newPlan.duration" type="text" placeholder="Ví dụ: 2 tiết (90 phút)"
                  class="form-input" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu cần đạt</label>
                <textarea v-model="newPlan.learning_outcomes" rows="3"
                  placeholder="Mô tả ngắn gọn những gì học sinh cần đạt được sau bài học..."
                  class="form-input resize-y"></textarea>
              </div>
              <div v-if="newPlan.startMode === 'copy'" class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Source Lesson Plan <span
                    class="text-red-500">*</span></label>
                <select v-model="newPlan.sourcePlanId" class="form-input">
                  <option value="">Select a lesson plan to copy</option>
                  <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.title }}</option>
                </select>
                <p v-if="plans.length === 0" class="text-xs text-amber-600 mt-2">You don't have any lesson plans to
                  copy.</p>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button v-if="createStep === 2" @click="createStep = 1"
              class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
              <i class="fas fa-arrow-left mr-1"></i> Back
            </button>
            <span v-else></span>
            <button v-if="createStep === 1" @click="createStep = 2"
              :disabled="newPlan.startMode === 'copy' && plans.length === 0"
              class="px-5 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50">
              Continue <i class="fas fa-arrow-right ml-1"></i>
            </button>
            <button v-else @click="createPlan" :disabled="!canCreate || creating"
              class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <i v-if="creating" class="fas fa-spinner fa-spin mr-1"></i>
              {{ creating ? 'Creating...' : 'Create and Start Editing' }}
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
import { LESSON_PLAN_START_OPTIONS, buildStandardLessonPlan } from '@/constants/lessonPlanTemplates'

const api = useApi()
const router = useRouter()

const plans = ref([])
const loading = ref(false)
const searchQuery = ref('')
const showCreateModal = ref(false)
const createStep = ref(1)
const creating = ref(false)
const startOptions = LESSON_PLAN_START_OPTIONS
const emptyPlan = () => ({
  title: '',
  subject: '',
  grade_level: '',
  topic: '',
  duration: '',
  learning_outcomes: '',
  startMode: 'standard',
  sourcePlanId: '',
})
const newPlan = ref(emptyPlan())
const toast = ref({ show: false, message: '', type: 'success' })

const canCreate = computed(() =>
  newPlan.value.title.trim()
  && (newPlan.value.startMode !== 'copy' || newPlan.value.sourcePlanId)
)

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
    showToast('Không thể tải danh sách giáo án', 'error')
  } finally {
    loading.value = false
  }
}

async function createPlan() {
  if (!canCreate.value) return
  creating.value = true
  try {
    let content = ''
    if (newPlan.value.startMode === 'standard') {
      content = buildStandardLessonPlan(newPlan.value)
    } else if (newPlan.value.startMode === 'copy') {
      const source = await api.lessonPlan.getDetail(newPlan.value.sourcePlanId)
      content = source.data?.content || ''
    }

    const res = await api.lessonPlan.create({
      title: newPlan.value.title.trim(),
      subject: newPlan.value.subject.trim(),
      grade_level: newPlan.value.grade_level.trim(),
      content,
    })
    closeCreateModal()
    router.push({ name: 'TeacherLessonPlanEditor', params: { id: res.data.id } })
  } catch (err) {
    showToast(err?.response?.data?.message || 'Không thể tạo giáo án', 'error')
  } finally {
    creating.value = false
  }
}

async function confirmDelete(plan) {
  if (!confirm(`Xóa giáo án "${plan.title}"?`)) return
  try {
    await api.lessonPlan.delete(plan.id)
    plans.value = plans.value.filter(p => p.id !== plan.id)
    showToast('Đã xóa giáo án', 'success')
  } catch (err) {
    showToast('Không thể xóa giáo án', 'error')
  }
}

function openCreateModal() {
  createStep.value = 1
  newPlan.value = emptyPlan()
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
  createStep.value = 1
  newPlan.value = emptyPlan()
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

onMounted(fetchPlans)
</script>

<style scoped>
@reference "tailwindcss";

.form-input {
  @apply w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm outline-none transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}
</style>
