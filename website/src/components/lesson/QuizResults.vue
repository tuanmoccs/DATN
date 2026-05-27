<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in">
      <!-- Header -->
      <div class="flex items-center justify-between p-5 border-b border-gray-200 sticky top-0 bg-white z-10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i class="fas fa-poll text-lg animate-pulse"></i>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-800">Quiz Results: {{ quiz.title }}</h3>
            <p class="text-sm text-gray-500">View and track student quiz submissions</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <button @click="fetchAttempts" :disabled="loading"
            class="p-2 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-gray-50 transition-colors"
            title="Refresh Results">
            <i :class="loading ? 'fas fa-spinner fa-spin text-indigo-600' : 'fas fa-sync-alt'"></i>
          </button>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>
      </div>

      <!-- Main Body Container -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6">
        <!-- Error State -->
        <div v-if="error" class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3">
          <i class="fas fa-exclamation-circle text-xl"></i>
          <span class="text-sm font-medium">{{ error }}</span>
        </div>

        <!-- Loading Skeleton -->
        <div v-if="loading && attempts.length === 0" class="space-y-6">
          <div class="grid grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="h-24 bg-gray-100 rounded-xl animate-pulse"></div>
          </div>
          <div class="h-10 bg-gray-100 rounded-lg animate-pulse"></div>
          <div class="space-y-3">
            <div v-for="i in 5" :key="i" class="h-16 bg-gray-50 rounded-xl animate-pulse"></div>
          </div>
        </div>

        <!-- Content loaded -->
        <template v-else>
          <!-- Stats Grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Total Attempts -->
            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 transition-all hover:shadow-md">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Total Submissions</span>
                <i class="fas fa-users text-indigo-400"></i>
              </div>
              <div class="text-2xl font-black text-indigo-900">{{ attempts.length }}</div>
              <div class="text-xs text-indigo-500 mt-1">student attempts</div>
            </div>

            <!-- Average Score -->
            <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4 transition-all hover:shadow-md">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Average Score</span>
                <i class="fas fa-chart-pie text-emerald-400"></i>
              </div>
              <div class="text-2xl font-black text-emerald-900">{{ averagePercentage }}%</div>
              <div class="text-xs text-emerald-500 mt-1">avg. grade</div>
            </div>

            <!-- Highest Score -->
            <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-4 transition-all hover:shadow-md">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Highest Score</span>
                <i class="fas fa-trophy text-amber-500 animate-bounce"></i>
              </div>
              <div class="text-2xl font-black text-amber-900">{{ highestScore }}</div>
              <div class="text-xs text-amber-500 mt-1">top performance</div>
            </div>

            <!-- Passing Rate -->
            <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 transition-all hover:shadow-md">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Pass Rate (>=50%)</span>
                <i class="fas fa-graduation-cap text-blue-400"></i>
              </div>
              <div class="text-2xl font-black text-blue-900">{{ passRate }}%</div>
              <div class="text-xs text-blue-500 mt-1">{{ passCount }} students passed</div>
            </div>
          </div>

          <!-- Controls Bar -->
          <div class="flex flex-col sm:flex-row gap-3 items-center justify-between bg-gray-50 p-4 rounded-xl border border-gray-100">
            <!-- Search -->
            <div class="relative w-full sm:max-w-xs">
              <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input v-model="searchQuery" type="text" placeholder="Search student name or email..."
                class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all" />
            </div>

            <!-- Sort & Filter -->
            <div class="flex items-center gap-3 w-full sm:w-auto">
              <div class="flex-1 sm:flex-none">
                <select v-model="sortBy"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all cursor-pointer">
                  <option value="submitted_newest">Newest Submission</option>
                  <option value="submitted_oldest">Oldest Submission</option>
                  <option value="score_highest">Highest Score First</option>
                  <option value="score_lowest">Lowest Score First</option>
                  <option value="attempt_desc">Highest Attempt Number</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredAttempts.length === 0" class="text-center py-16 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
            <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-inbox text-2xl"></i>
            </div>
            <h4 class="text-base font-semibold text-gray-700 mb-1">No quiz attempts found</h4>
            <p class="text-sm text-gray-400 max-w-sm mx-auto">
              {{ searchQuery ? 'Try adjusting your search terms to find student attempts.' : 'No students have completed this quiz yet. Make sure it is published!' }}
            </p>
          </div>

          <!-- Results Table -->
          <div v-else class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
            <table class="w-full text-left border-collapse bg-white">
              <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Attempt</th>
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Raw Score</th>
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Percentage</th>
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Status</th>
                  <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted At</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="attempt in filteredAttempts" :key="attempt.id" class="hover:bg-gray-50/80 transition-colors">
                  <!-- Student Info -->
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs shadow-sm flex-shrink-0">
                        {{ getInitials(attempt.student?.name) }}
                      </div>
                      <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-800 truncate">{{ attempt.student?.name || 'Student' }}</div>
                        <div class="text-xs text-gray-400 truncate">{{ attempt.student?.email || '-' }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Attempt Count -->
                  <td class="px-5 py-4 text-center">
                    <span class="px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium border border-gray-200">
                      Attempt #{{ attempt.attempt_number }}
                    </span>
                  </td>

                  <!-- Raw Score -->
                  <td class="px-5 py-4 text-center font-semibold text-sm text-gray-800">
                    {{ Number(attempt.score).toFixed(1) }} <span class="text-gray-400 font-normal">/ {{ totalPoints }}</span>
                  </td>

                  <!-- Percentage badge -->
                  <td class="px-5 py-4 text-center">
                    <span :class="scoreBadgeClass(attempt.percentage)" class="px-2.5 py-1 rounded-full text-xs font-bold border">
                      {{ Number(attempt.percentage).toFixed(0) }}%
                    </span>
                  </td>

                  <!-- Status -->
                  <td class="px-5 py-4 text-center">
                    <span :class="statusBadgeClass(attempt.status)" class="px-2 py-0.5 rounded-full text-[11px] font-semibold border uppercase tracking-wider">
                      {{ attempt.status }}
                    </span>
                  </td>

                  <!-- Date Submitted -->
                  <td class="px-5 py-4 text-sm text-gray-500">
                    <div class="flex items-center gap-1.5">
                      <i class="far fa-clock text-gray-400 text-xs"></i>
                      <span>{{ formatDate(attempt.submitted_at || attempt.created_at) }}</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>

      <!-- Footer -->
      <div class="p-4 bg-gray-50 border-t border-gray-150 flex justify-end">
        <button @click="$emit('close')"
          class="px-5 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium transition-colors shadow-sm">
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/plugins/api'

const props = defineProps({
  quiz: { type: Object, required: true },
})

defineEmits(['close'])

const api = useApi()
const loading = ref(true)
const error = ref(null)
const attempts = ref([])
const totalPoints = ref(10) // default fallback

// Search & Sort states
const searchQuery = ref('')
const sortBy = ref('submitted_newest')

const fetchAttempts = async () => {
  loading.value = true
  error.value = null
  try {
    const res = await api.lesson.getQuizAttempts(props.quiz.id)
    attempts.value = res.data?.data || []
    totalPoints.value = res.data?.total_points || 10
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load quiz student results.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchAttempts)

// Initial letters for student avatar
const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .filter(w => w.length > 0)
    .map(w => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

// Date formatter inside
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dateStr))
}

// Score Badges coloring
const scoreBadgeClass = (percentage) => {
  const p = Number(percentage)
  if (p >= 85) return 'bg-green-50 text-green-700 border-green-200'
  if (p >= 50) return 'bg-blue-50 text-blue-700 border-blue-200'
  return 'bg-red-50 text-red-700 border-red-200'
}

// Status Badges coloring
const statusBadgeClass = (status) => {
  const s = String(status).toLowerCase()
  if (s === 'graded') return 'bg-green-100 text-green-800 border-green-200'
  if (s === 'submitted') return 'bg-blue-100 text-blue-800 border-blue-200'
  return 'bg-yellow-100 text-yellow-800 border-yellow-200'
}

// Aggregate Statistics Computeds
const averagePercentage = computed(() => {
  if (attempts.value.length === 0) return 0
  const sum = attempts.value.reduce((acc, curr) => acc + Number(curr.percentage), 0)
  return (sum / attempts.value.length).toFixed(0)
})

const highestScore = computed(() => {
  if (attempts.value.length === 0) return 'N/A'
  const maxPerc = Math.max(...attempts.value.map(a => Number(a.percentage)))
  const maxAttempt = attempts.value.find(a => Number(a.percentage) === maxPerc)
  return `${Number(maxAttempt.score).toFixed(1)} / ${totalPoints.value} (${maxPerc.toFixed(0)}%)`
})

const passCount = computed(() => {
  return attempts.value.filter(a => Number(a.percentage) >= 50).length
})

const passRate = computed(() => {
  if (attempts.value.length === 0) return 0
  return ((passCount.value / attempts.value.length) * 100).toFixed(0)
})

// Search and Sorting logic combined
const filteredAttempts = computed(() => {
  let result = [...attempts.value]

  // Filter by Search Query
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    result = result.filter(
      a =>
        a.student?.name?.toLowerCase().includes(q) ||
        a.student?.email?.toLowerCase().includes(q)
    )
  }

  // Sorting
  result.sort((a, b) => {
    if (sortBy.value === 'submitted_newest') {
      return new Date(b.submitted_at || b.created_at) - new Date(a.submitted_at || a.created_at)
    }
    if (sortBy.value === 'submitted_oldest') {
      return new Date(a.submitted_at || a.created_at) - new Date(b.submitted_at || b.created_at)
    }
    if (sortBy.value === 'score_highest') {
      return Number(b.percentage) - Number(a.percentage)
    }
    if (sortBy.value === 'score_lowest') {
      return Number(a.percentage) - Number(b.percentage)
    }
    if (sortBy.value === 'attempt_desc') {
      return b.attempt_number - a.attempt_number
    }
    return 0
  })

  return result
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
