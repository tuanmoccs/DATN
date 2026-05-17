<template>
  <div>
    <!-- Welcome -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-800">
        Welcome back, {{ userName }}! 👋
      </h2>
      <p class="text-gray-500 mt-1">Here's an overview of your teaching activity.</p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mr-3"></i>
      <span class="text-gray-500">Loading dashboard...</span>
    </div>

    <template v-else>
      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
        <div v-for="stat in statsCards" :key="stat.label"
          class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-center gap-4">
            <div :class="[stat.bgColor, 'w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0']">
              <i :class="[stat.icon, stat.iconColor, 'text-lg']"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500 font-medium">{{ stat.label }}</p>
              <p class="text-2xl font-bold text-gray-800">{{ stat.value }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Students Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Quiz Students -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fas fa-trophy text-yellow-500"></i>
            <h3 class="text-lg font-semibold text-gray-800">Top Quiz Performers</h3>
          </div>
          <div class="p-6">
            <div v-if="topQuizStudents.length === 0" class="text-center py-8">
              <i class="fas fa-clipboard-check text-gray-300 text-4xl mb-3"></i>
              <p class="text-gray-400 text-sm">No quiz data available yet.</p>
            </div>
            <div v-else class="space-y-4">
              <div v-for="(student, index) in topQuizStudents" :key="student.student?.id"
                class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                <!-- Rank badge -->
                <div class="flex-shrink-0">
                  <div v-if="index < 3"
                    :class="[rankColors[index], 'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm']">
                    {{ index + 1 }}
                  </div>
                  <div v-else
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-600 font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                </div>
                <!-- Avatar -->
                <div class="flex-shrink-0">
                  <img v-if="student.student?.avatar" :src="student.student.avatar"
                    class="w-10 h-10 rounded-full object-cover" :alt="student.student?.name" />
                  <div v-else
                    class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold text-sm">
                    {{ getInitials(student.student?.name) }}
                  </div>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-800 truncate">{{ student.student?.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ student.quiz_title || 'Multiple quizzes' }}</p>
                </div>
                <!-- Score -->
                <div class="flex-shrink-0 text-right">
                  <p class="text-sm font-bold" :class="getScoreColor(student.best_score)">
                    {{ student.best_score }}%
                  </p>
                  <p class="text-xs text-gray-400">avg {{ student.avg_score }}%</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Assignment Students -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fas fa-medal text-orange-500"></i>
            <h3 class="text-lg font-semibold text-gray-800">Top Assignment Performers</h3>
          </div>
          <div class="p-6">
            <div v-if="topAssignmentStudents.length === 0" class="text-center py-8">
              <i class="fas fa-file-alt text-gray-300 text-4xl mb-3"></i>
              <p class="text-gray-400 text-sm">No assignment data available yet.</p>
            </div>
            <div v-else class="space-y-4">
              <div v-for="(student, index) in topAssignmentStudents" :key="student.student?.id"
                class="flex items-center gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                <!-- Rank badge -->
                <div class="flex-shrink-0">
                  <div v-if="index < 3"
                    :class="[rankColors[index], 'w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm']">
                    {{ index + 1 }}
                  </div>
                  <div v-else
                    class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-200 text-gray-600 font-semibold text-sm">
                    {{ index + 1 }}
                  </div>
                </div>
                <!-- Avatar -->
                <div class="flex-shrink-0">
                  <img v-if="student.student?.avatar" :src="student.student.avatar"
                    class="w-10 h-10 rounded-full object-cover" :alt="student.student?.name" />
                  <div v-else
                    class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-semibold text-sm">
                    {{ getInitials(student.student?.name) }}
                  </div>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-gray-800 truncate">{{ student.student?.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ student.assignment_title || 'Multiple assignments' }}</p>
                </div>
                <!-- Score -->
                <div class="flex-shrink-0 text-right">
                  <p class="text-sm font-bold" :class="getScoreColor(student.best_score)">
                    {{ student.best_score }}%
                  </p>
                  <p class="text-xs text-gray-400">avg {{ student.avg_score }}%</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
          <i class="fas fa-clock text-blue-500"></i>
          <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
        </div>
        <div class="p-6">
          <div v-if="recentActivity.length === 0" class="text-center py-8">
            <i class="fas fa-history text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-400 text-sm">No recent activity.</p>
          </div>
          <div v-else class="space-y-3">
            <div v-for="(activity, index) in recentActivity" :key="index"
              class="flex items-start gap-4 p-3 rounded-lg hover:bg-gray-50 transition-colors">
              <!-- Activity icon -->
              <div class="flex-shrink-0 mt-0.5">
                <div v-if="activity.type === 'enrollment'"
                  class="w-9 h-9 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="fas fa-user-plus text-green-600 text-sm"></i>
                </div>
                <div v-else-if="activity.type === 'quiz_attempt'"
                  class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="fas fa-question-circle text-purple-600 text-sm"></i>
                </div>
                <div v-else class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="fas fa-file-upload text-blue-600 text-sm"></i>
                </div>
              </div>
              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700">{{ activity.message }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ formatTimeAgo(activity.date) }}</p>
              </div>
              <!-- Score badge for quizzes -->
              <div v-if="activity.type === 'quiz_attempt'" class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getScoreBadgeClass(activity.score)">
                  {{ activity.score }}%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/plugins/api'

const api = useApi()

const loading = ref(true)
const dashboardData = ref(null)

const userName = computed(() => {
  const info = localStorage.getItem('user_info')
  if (info) {
    try {
      return JSON.parse(info).name || 'Teacher'
    } catch {
      return 'Teacher'
    }
  }
  return 'Teacher'
})

const stats = computed(() => dashboardData.value?.stats || {})
const topQuizStudents = computed(() => dashboardData.value?.top_quiz_students || [])
const topAssignmentStudents = computed(() => dashboardData.value?.top_assignment_students || [])
const recentActivity = computed(() => dashboardData.value?.recent_activity || [])

const statsCards = computed(() => [
  {
    icon: 'fas fa-chalkboard',
    label: 'Classes',
    value: stats.value.total_classes || 0,
    bgColor: 'bg-blue-50',
    iconColor: 'text-blue-600',
  },
  {
    icon: 'fas fa-user-graduate',
    label: 'Students',
    value: stats.value.total_students || 0,
    bgColor: 'bg-green-50',
    iconColor: 'text-green-600',
  },
  {
    icon: 'fas fa-book-open',
    label: 'Lessons',
    value: stats.value.total_lessons || 0,
    bgColor: 'bg-purple-50',
    iconColor: 'text-purple-600',
  },
  {
    icon: 'fas fa-question-circle',
    label: 'Quizzes',
    value: stats.value.total_quizzes || 0,
    bgColor: 'bg-yellow-50',
    iconColor: 'text-yellow-600',
  },
  {
    icon: 'fas fa-tasks',
    label: 'Assignments',
    value: stats.value.total_assignments || 0,
    bgColor: 'bg-orange-50',
    iconColor: 'text-orange-600',
  },
])

const rankColors = ['bg-yellow-500', 'bg-gray-400', 'bg-amber-700']

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function getScoreColor(score) {
  if (score >= 90) return 'text-green-600'
  if (score >= 70) return 'text-blue-600'
  if (score >= 50) return 'text-yellow-600'
  return 'text-red-600'
}

function getScoreBadgeClass(score) {
  if (score >= 90) return 'bg-green-100 text-green-700'
  if (score >= 70) return 'bg-blue-100 text-blue-700'
  if (score >= 50) return 'bg-yellow-100 text-yellow-700'
  return 'bg-red-100 text-red-700'
}

function formatTimeAgo(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now - date
  const diffMin = Math.floor(diffMs / 60000)
  const diffHour = Math.floor(diffMs / 3600000)
  const diffDay = Math.floor(diffMs / 86400000)

  if (diffMin < 1) return 'Just now'
  if (diffMin < 60) return `${diffMin} min ago`
  if (diffHour < 24) return `${diffHour}h ago`
  if (diffDay < 7) return `${diffDay}d ago`
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

async function fetchDashboard() {
  loading.value = true
  try {
    const response = await api.dashboard.getTeacherDashboard()
    dashboardData.value = response
  } catch (error) {
    console.error('Failed to load dashboard:', error)
    dashboardData.value = null
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})
</script>