<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">AI Competency Reports</h2>
        <p class="text-gray-500 mt-1">Review AI-generated student competency reports by class.</p>
      </div>
    </div>

    <div class="mb-6 flex flex-col md:flex-row md:items-end gap-4">
      <ClassReportFilter v-model="selectedClassId" :classes="classes" class="flex-1" />
      <div class="flex flex-col sm:flex-row gap-2 md:mb-5">
        <button @click="generateAllReports" :disabled="!selectedClassId || generatingAll"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
          <i :class="generatingAll ? 'fas fa-spinner fa-spin' : 'fas fa-wand-magic-sparkles'" class="mr-2"></i>
          Generate All Reports
        </button>
        <button @click="exportClassPdf" :disabled="!selectedClassId || exportingPdf"
          class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
          <i :class="exportingPdf ? 'fas fa-spinner fa-spin' : 'fas fa-file-pdf'" class="mr-2"></i>
          Export Class PDF
        </button>
      </div>
    </div>

    <div v-if="currentBatch" class="mb-6 bg-white rounded-xl border border-blue-100 p-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <p class="text-sm font-semibold text-gray-800">Generating class reports</p>
          <p class="text-xs text-gray-500 mt-1">
            {{ currentBatch.processed || 0 }}/{{ currentBatch.total_students || 0 }} processed
            · generated {{ currentBatch.generated || 0 }}
            · skipped {{ currentBatch.skipped || 0 }}
            · failed {{ currentBatch.failed || 0 }}
          </p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
          :class="currentBatch.status === 'failed' ? 'bg-red-100 text-red-700' : currentBatch.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
          <i v-if="['queued', 'processing'].includes(currentBatch.status)" class="fas fa-spinner fa-spin mr-2"></i>
          {{ currentBatch.status }}
        </span>
      </div>
      <div class="mt-3 h-2 bg-gray-100 rounded-full overflow-hidden">
        <div class="h-full bg-blue-600 transition-all" :style="{ width: `${batchProgress}%` }"></div>
      </div>
      <p v-if="currentBatch.error_message" class="text-sm text-red-600 mt-3">{{ currentBatch.error_message }}</p>
    </div>

    <div v-if="selectedClassId" class="mb-6 bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-gray-800">Risk Alerts</h3>
          <p class="text-sm text-gray-500 mt-1">Students flagged for low scores, missing work, or declining progress.</p>
        </div>
        <button @click="fetchRiskAlerts" :disabled="riskLoading"
          class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
          <i :class="riskLoading ? 'fas fa-spinner fa-spin' : 'fas fa-rotate-right'" class="mr-2"></i>
          Refresh
        </button>
      </div>

      <div v-if="riskLoading" class="flex justify-center py-8">
        <i class="fas fa-spinner fa-spin text-xl text-blue-600"></i>
      </div>

      <div v-else-if="!riskAlerts" class="p-5 text-sm text-gray-500">
        Risk alerts will appear after class data loads.
      </div>

      <div v-else class="p-5">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
          <div class="border border-gray-200 rounded-lg p-3">
            <p class="text-xs text-gray-500 font-medium">At Risk</p>
            <p class="text-xl font-bold text-gray-800">{{ riskAlerts.students_at_risk || 0 }}</p>
          </div>
          <div class="border border-red-100 bg-red-50 rounded-lg p-3">
            <p class="text-xs text-red-600 font-medium">High</p>
            <p class="text-xl font-bold text-red-700">{{ riskAlerts.high_risk || 0 }}</p>
          </div>
          <div class="border border-yellow-100 bg-yellow-50 rounded-lg p-3">
            <p class="text-xs text-yellow-700 font-medium">Medium</p>
            <p class="text-xl font-bold text-yellow-800">{{ riskAlerts.medium_risk || 0 }}</p>
          </div>
          <div class="border border-gray-200 rounded-lg p-3">
            <p class="text-xs text-gray-500 font-medium">Total Students</p>
            <p class="text-xl font-bold text-gray-800">{{ riskAlerts.total_students || 0 }}</p>
          </div>
        </div>

        <div v-if="(riskAlerts.alerts || []).length === 0" class="text-center py-8 border border-dashed border-gray-300 rounded-lg">
          <i class="fas fa-shield-halved text-3xl text-gray-300 mb-3"></i>
          <p class="text-sm text-gray-500">No risk alerts for this class right now.</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="studentRisk in riskAlerts.alerts" :key="studentRisk.student_id"
            class="border border-gray-200 rounded-lg p-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <p class="text-sm font-semibold text-gray-800">{{ studentRisk.student_name }}</p>
                  <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                    :class="riskLevelClass(studentRisk.risk_level)">
                    {{ studentRisk.risk_level }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ studentRisk.student_email }}</p>
              </div>
              <p class="text-sm font-semibold text-gray-700">
                {{ studentRisk.average_score != null ? `${studentRisk.average_score}%` : 'No report score' }}
              </p>
            </div>

            <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-2">
              <div v-for="alert in studentRisk.alerts" :key="alert.type"
                class="rounded-lg border px-3 py-2"
                :class="alertSeverityClass(alert.severity)">
                <p class="text-xs font-semibold">{{ alert.title }}</p>
                <p class="text-xs mt-1">{{ alert.message }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!selectedClassId" class="text-center py-16">
      <i class="fas fa-chart-bar text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600 mb-2">Select a class</h3>
      <p class="text-gray-400">Choose a class to view students and AI reports.</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-1">
        <StudentReportList :students="activeStudents" :selected-student-id="selectedStudent?.user?.id"
          @select="selectStudent" />
      </div>
      <div class="lg:col-span-2">
        <ReportEditor :student="selectedStudent" :report="selectedReport" :loading="reportLoading"
          :generating="reportGenerating" :saving="reportSaving" @generate="generateReport" @save="saveReport" />
      </div>
    </div>

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
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useApi } from '@/plugins/api'
import ClassReportFilter from '@/components/reports/ClassReportFilter.vue'
import StudentReportList from '@/components/reports/StudentReportList.vue'
import ReportEditor from '@/components/reports/ReportEditor.vue'

const api = useApi()

const classes = ref([])
const selectedClassId = ref('')
const classData = ref(null)
const selectedStudent = ref(null)
const selectedReport = ref(null)
const reportLoading = ref(false)
const reportGenerating = ref(false)
const reportSaving = ref(false)
const exportingPdf = ref(false)
const generatingAll = ref(false)
const currentBatch = ref(null)
const batchPollingTimer = ref(null)
const riskAlerts = ref(null)
const riskLoading = ref(false)

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const activeStudents = computed(() => {
  return (classData.value?.enrollment || []).filter(item => item.status === 'active')
})

const batchProgress = computed(() => {
  if (!currentBatch.value?.total_students) return 0
  return Math.min(100, Math.round((currentBatch.value.processed / currentBatch.value.total_students) * 100))
})

const fetchClasses = async () => {
  try {
    const res = await api.class.getClasses()
    classes.value = res.data || []
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to load classes', 'error')
  }
}

const fetchClassDetail = async () => {
  selectedStudent.value = null
  selectedReport.value = null
  classData.value = null
  riskAlerts.value = null
  currentBatch.value = null
  if (batchPollingTimer.value) {
    clearTimeout(batchPollingTimer.value)
    batchPollingTimer.value = null
  }

  if (!selectedClassId.value) return

  try {
    const res = await api.class.getClassDetail(selectedClassId.value)
    classData.value = res.data
    await fetchRiskAlerts()
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to load class details', 'error')
  }
}

const fetchRiskAlerts = async () => {
  if (!selectedClassId.value) return

  riskLoading.value = true
  try {
    const res = await api.competencyReport.getRiskAlerts(selectedClassId.value)
    riskAlerts.value = res.data
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to load risk alerts', 'error')
  } finally {
    riskLoading.value = false
  }
}

const selectStudent = async (studentEnrollment) => {
  selectedStudent.value = studentEnrollment
  selectedReport.value = null
  reportLoading.value = true

  try {
    const res = await api.competencyReport.getReports({
      class_id: selectedClassId.value,
      student_id: studentEnrollment.user?.id,
    })
    selectedReport.value = res.data?.[0] || null
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to load report', 'error')
  } finally {
    reportLoading.value = false
  }
}

const generateReport = async () => {
  if (!selectedStudent.value?.user?.id) return

  reportGenerating.value = true
  try {
    const res = await api.competencyReport.generateReport({
      class_id: Number(selectedClassId.value),
      student_id: selectedStudent.value.user.id,
      report_type: 'class',
    })
    selectedReport.value = res.data
    showToast('AI report generated')
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to generate report', 'error')
  } finally {
    reportGenerating.value = false
  }
}

const generateAllReports = async () => {
  if (!selectedClassId.value) return

  generatingAll.value = true
  try {
    const res = await api.competencyReport.generateClassReports(selectedClassId.value)
    currentBatch.value = res.data
    showToast('Class report generation queued')
    pollGenerateBatch(res.data.id)
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to generate class reports', 'error')
    generatingAll.value = false
  }
}

const pollGenerateBatch = async (batchId) => {
  if (batchPollingTimer.value) {
    clearTimeout(batchPollingTimer.value)
    batchPollingTimer.value = null
  }

  try {
    const res = await api.competencyReport.getGenerateBatchStatus(batchId)
    currentBatch.value = res.data

    if (['queued', 'processing'].includes(res.data.status)) {
      generatingAll.value = true
      batchPollingTimer.value = setTimeout(() => pollGenerateBatch(batchId), 2500)
      return
    }

    generatingAll.value = false
    if (res.data.status === 'completed') {
      showToast(`Generated ${res.data.generated}, skipped ${res.data.skipped}, failed ${res.data.failed}`)
      await fetchRiskAlerts()
      if (selectedStudent.value?.user?.id) {
        await selectStudent(selectedStudent.value)
      }
    } else {
      showToast(res.data.error_message || 'Class report generation failed', 'error')
    }
  } catch (err) {
    generatingAll.value = false
    showToast(err.response?.data?.message || 'Unable to check class report generation status', 'error')
  } finally {
  }
}

const saveReport = async (payload) => {
  if (!selectedReport.value?.id) return

  reportSaving.value = true
  try {
    const res = await api.competencyReport.updateReport(selectedReport.value.id, payload)
    selectedReport.value = res.data
    showToast('Report saved')
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to save report', 'error')
  } finally {
    reportSaving.value = false
  }
}

const exportClassPdf = async () => {
  if (!selectedClassId.value) return

  exportingPdf.value = true
  try {
    const blob = await api.competencyReport.exportClassPDF(selectedClassId.value)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    const className = classes.value.find(cls => String(cls.id) === String(selectedClassId.value))?.name || 'class'

    link.href = url
    link.download = `competency-report-${className}.pdf`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    showToast('Class PDF exported')
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to export class PDF', 'error')
  } finally {
    exportingPdf.value = false
  }
}

const riskLevelClass = (level) => {
  if (level === 'high') return 'bg-red-100 text-red-700'
  if (level === 'medium') return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-700'
}

const alertSeverityClass = (severity) => {
  if (severity === 'high') return 'border-red-100 bg-red-50 text-red-700'
  if (severity === 'medium') return 'border-yellow-100 bg-yellow-50 text-yellow-800'
  return 'border-gray-200 bg-gray-50 text-gray-700'
}

watch(selectedClassId, fetchClassDetail)
onMounted(fetchClasses)
onUnmounted(() => {
  if (batchPollingTimer.value) {
    clearTimeout(batchPollingTimer.value)
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
