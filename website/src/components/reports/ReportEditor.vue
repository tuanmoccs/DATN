<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-base font-semibold text-gray-800">AI Competency Report</h3>
          <p class="text-sm text-gray-500 mt-1">
            {{ student?.user?.name || 'Select a student' }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button @click="$emit('generate')" :disabled="!student || generating"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50">
            <i v-if="generating" class="fas fa-spinner fa-spin mr-2"></i>
            {{ report?.id ? 'Generate New' : 'Generate Report' }}
          </button>
          <button v-if="report?.id" @click="emitSave" :disabled="saving"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50">
            <i v-if="saving" class="fas fa-spinner fa-spin mr-2"></i>
            Save Edits
          </button>
        </div>
      </div>
    </div>

    <div class="p-5">
      <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl text-sm text-yellow-800 mb-5">
        AI report is only a reference. Teachers should review and edit the content before using it for assessment.
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      </div>

      <div v-else-if="!student" class="text-center py-16">
        <i class="fas fa-user-graduate text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Select a student to view the report</p>
      </div>

      <div v-else-if="!report?.id" class="text-center py-16 border border-dashed border-gray-300 rounded-xl">
        <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">No report yet for this student</p>
      </div>

      <div v-else class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium mb-1">Average Score</p>
            <p class="text-2xl font-bold text-gray-800">
              {{ report.average_score != null ? `${report.average_score}%` : 'N/A' }}
            </p>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium mb-1">Quizzes</p>
            <p class="text-2xl font-bold text-gray-800">{{ report.total_quizzes_taken || 0 }}</p>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 font-medium mb-1">Assignments</p>
            <p class="text-2xl font-bold text-gray-800">{{ report.total_assignments_completed || 0 }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Overall Summary</label>
          <textarea v-model="form.overall_summary" rows="5"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm resize-y"></textarea>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Strengths</label>
            <textarea v-model="form.strengths" rows="8"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm resize-y"
              placeholder="One item per line"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Needs Support</label>
            <textarea v-model="form.weaknesses" rows="8"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm resize-y"
              placeholder="One item per line"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Recommendations</label>
            <textarea v-model="form.recommendations" rows="8"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm resize-y"
              placeholder="One item per line"></textarea>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'

const props = defineProps({
  student: {
    type: Object,
    default: null,
  },
  report: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  generating: {
    type: Boolean,
    default: false,
  },
  saving: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['generate', 'save'])

const form = reactive({
  overall_summary: '',
  strengths: '',
  weaknesses: '',
  recommendations: '',
})

const linesToArray = (value) => value.split('\n').map(item => item.trim()).filter(Boolean)

const syncForm = () => {
  form.overall_summary = props.report?.overall_summary || ''
  form.strengths = (props.report?.strengths || []).join('\n')
  form.weaknesses = (props.report?.weaknesses || []).join('\n')
  form.recommendations = (props.report?.recommendations || []).join('\n')
}

const emitSave = () => {
  emit('save', {
    overall_summary: form.overall_summary,
    strengths: linesToArray(form.strengths),
    weaknesses: linesToArray(form.weaknesses),
    recommendations: linesToArray(form.recommendations),
  })
}

watch(() => props.report, syncForm, { immediate: true })
</script>
