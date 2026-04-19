<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">AI Competency Reports</h2>
        <p class="text-gray-500 mt-1">Review AI-generated student competency reports by class.</p>
      </div>
    </div>

    <ClassReportFilter v-model="selectedClassId" :classes="classes" class="mb-6" />

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
import { computed, onMounted, ref, watch } from 'vue'
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

const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

const activeStudents = computed(() => {
  return (classData.value?.enrollment || []).filter(item => item.status === 'active')
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

  if (!selectedClassId.value) return

  try {
    const res = await api.class.getClassDetail(selectedClassId.value)
    classData.value = res.data
  } catch (err) {
    showToast(err.response?.data?.message || 'Unable to load class details', 'error')
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

watch(selectedClassId, fetchClassDetail)
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
</style>
