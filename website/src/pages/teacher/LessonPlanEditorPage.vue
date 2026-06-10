<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
    </div>

    <template v-else-if="plan">
      <!-- Header -->
      <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-6">
        <div class="flex items-start gap-3 min-w-0">
          <button @click="$router.push({ name: 'TeacherLessonPlans' })"
            class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors text-gray-600">
            <i class="fas fa-arrow-left"></i>
          </button>
          <div class="min-w-0">
            <h2 class="text-xl font-bold text-gray-800 truncate">{{ plan.title }}</h2>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-0.5">
              <span v-if="plan.subject"><i class="fas fa-book-open mr-1"></i>{{ plan.subject }}</span>
              <span v-if="plan.grade_level"><i class="fas fa-graduation-cap mr-1"></i>{{ plan.grade_level }}</span>
              <span :class="plan.status === 'completed' ? 'text-green-600' : 'text-yellow-600'">
                <i class="fas fa-circle text-[6px] mr-1"></i>
                {{ plan.status === 'completed' ? 'Completed' : 'Draft' }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span v-if="saveStatus" :class="saveStatusClass" class="text-xs mr-1">
            <i :class="saveStatusIcon" class="mr-1"></i>
            {{ saveStatusLabel }}
          </span>
          <button @click="exportPDF"
            class="action-button bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
            <i class="fas fa-file-pdf"></i> Export PDF
          </button>
          <button @click="markCompleted" v-if="plan.status === 'draft'"
            class="action-button bg-green-600 text-white hover:bg-green-700">
            <i class="fas fa-check"></i> Mark as Completed
          </button>
          <button @click="savePlan" class="action-button bg-blue-600 text-white hover:bg-blue-700">
            <i class="fas fa-save"></i> Save
          </button>
        </div>
      </div>

      <!-- Main Layout: Editor + Sidebar -->
      <div class="flex flex-col lg:flex-row gap-6 items-start">
        <!-- Editor (main) -->
        <div class="w-full lg:flex-1 min-w-0 order-2 lg:order-1">
          <TiptapEditor v-model="editorContent" :aiSuggestFn="handleAiSuggest"
            placeholder="Start writing your lesson plan... AI will suggest content as you type." />
        </div>

        <!-- Sidebar -->
        <aside class="w-full lg:w-80 flex-shrink-0 space-y-4 order-1 lg:order-2 lg:sticky lg:top-4">
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
              <h3 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-list-alt mr-1"></i> Table of Contents
              </h3>
              <span class="text-xs text-gray-400">{{ outline.length }} items</span>
            </div>
            <div v-if="outline.length" class="max-h-64 overflow-y-auto space-y-1 pr-1">
              <button v-for="item in outline" :key="`${item.index}-${item.title}`" @click="scrollToSection(item.index)"
                :class="[
                  'w-full flex items-center gap-2 rounded-md py-2 pr-2 text-left text-xs transition-colors hover:bg-blue-50 hover:text-blue-700',
                  item.level === 3 ? 'pl-6 text-gray-500' : 'pl-2 font-medium text-gray-700',
                ]">
                <i class='fas fa-check-circle text-green-500'></i>
                <span class="line-clamp-2">{{ item.title }}</span>
              </button>
            </div>
            <p v-else class="text-xs text-gray-400 leading-5">
              Add H2 or H3 headings to create a table of contents and navigate quickly between sections.
            </p>
          </div>

          <!-- Plan Info -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
              <i class="fas fa-info-circle mr-1"></i> Lesson Plan Information
            </h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tên bài dạy</label>
                <input v-model="plan.title" type="text"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Môn học</label>
                <input v-model="plan.subject" type="text" placeholder="Ví dụ: Toán học"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Khối lớp</label>
                <input v-model="plan.grade_level" type="text" placeholder="Ví dụ: Lớp 10"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
            </div>
          </div>

          <!-- Reference Upload -->
          <ReferenceUpload :uploadFileFn="handleUploadFile" :uploadTextFn="handleUploadText" />

          <!-- Keyboard shortcuts -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">
              <i class="fas fa-keyboard mr-1"></i> Keyboard Shortcuts
            </h3>
            <div class="space-y-1.5 text-xs text-gray-500">
              <div class="flex justify-between">
                <span>Accept Suggestion</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Tab</kbd>
              </div>
              <div class="flex justify-between">
                <span>Ignore Suggestion</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Esc</kbd>
              </div>
              <div class="flex justify-between">
                <span>Save</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Ctrl+S</kbd>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </template>

    <!-- Not found -->
    <div v-else-if="!loading" class="text-center py-20">
      <i class="fas fa-exclamation-triangle text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600">Lesson plan not found</h3>
    </div>

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
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/plugins/api'
import TiptapEditor from '@/components/lessonPlan/TiptapEditor.vue'
import ReferenceUpload from '@/components/lessonPlan/ReferenceUpload.vue'

const api = useApi()
const route = useRoute()
const router = useRouter()

const plan = ref(null)
const loading = ref(true)
const editorContent = ref('')
const saveStatus = ref('')
const toast = ref({ show: false, message: '', type: 'success' })

let autoSaveTimer = null
let autoSaveEnabled = false
let saveStatusTimer = null
let saveInProgress = false
let saveQueued = false

const saveStatusLabel = computed(() => ({
  unsaved: 'There are unsaved changes',
  saving: 'Saving...',
  saved: 'Saved',
  error: 'Save failed',
}[saveStatus.value] || ''))

const saveStatusIcon = computed(() => ({
  unsaved: 'fas fa-circle text-[6px]',
  saving: 'fas fa-spinner fa-spin',
  saved: 'fas fa-check-circle',
  error: 'fas fa-exclamation-circle',
}[saveStatus.value] || ''))

const saveStatusClass = computed(() => ({
  unsaved: 'text-amber-600',
  saving: 'text-blue-500',
  saved: 'text-green-600',
  error: 'text-red-600',
}[saveStatus.value] || 'text-gray-400'))

const outline = computed(() => {
  if (!editorContent.value || typeof DOMParser === 'undefined') return []
  const doc = new DOMParser().parseFromString(editorContent.value, 'text/html')
  const headings = [...doc.body.querySelectorAll('h2, h3')]

  return headings.map((heading, index) => {
    let contentLength = 0
    let node = heading.nextElementSibling
    while (node && !['H2', 'H3'].includes(node.tagName)) {
      contentLength += (node.textContent || '')
        .trim()
        .length
      node = node.nextElementSibling
    }

    return {
      index,
      title: heading.textContent?.trim() || `Mục ${index + 1}`,
      level: Number(heading.tagName.slice(1)),
      completed: contentLength >= 30,
    }
  })
})

// Fetch plan detail
async function fetchPlan() {
  loading.value = true
  try {
    const res = await api.lessonPlan.getDetail(route.params.id)
    plan.value = res.data
    editorContent.value = plan.value.content || ''
    // Enable auto-save only after initial content is set
    setTimeout(() => { autoSaveEnabled = true }, 500)
  } catch (err) {
    plan.value = null
    showToast('Cannot load lesson plan', 'error')
  } finally {
    loading.value = false
  }
}

// Auto-save when content changes (skip initial load)
watch(
  () => [editorContent.value, plan.value?.title, plan.value?.subject, plan.value?.grade_level],
  () => {
    if (!autoSaveEnabled) return
    setSaveStatus('unsaved')
    if (autoSaveTimer) clearTimeout(autoSaveTimer)
    autoSaveTimer = setTimeout(() => autoSave(), 3000)
  },
)

async function autoSave() {
  if (!plan.value) return
  if (saveInProgress) {
    saveQueued = true
    return
  }

  saveInProgress = true
  setSaveStatus('saving')
  try {
    await api.lessonPlan.update(plan.value.id, {
      content: editorContent.value,
    })
    setSaveStatus('saved', true)
  } catch (err) {
    setSaveStatus('error')
    console.warn('Tự động lưu giáo án thất bại', {
      status: err?.response?.status,
      message: err?.response?.data?.message || err?.message,
      errors: err?.response?.data?.errors,
    })
  } finally {
    saveInProgress = false
    if (saveQueued) {
      saveQueued = false
      autoSaveTimer = setTimeout(() => autoSave(), 500)
    }
  }
}

async function savePlan() {
  if (!plan.value) return
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  setSaveStatus('saving')
  try {
    await persistPlan()
    setSaveStatus('saved', true)
    showToast('Lesson plan saved', 'success')
  } catch (err) {
    setSaveStatus('error')
    showToast('Cannot save lesson plan', 'error')
  }
}

function persistPlan(extraData = {}) {
  return api.lessonPlan.update(plan.value.id, {
    title: plan.value.title,
    subject: plan.value.subject,
    grade_level: plan.value.grade_level,
    content: editorContent.value,
    ...extraData,
  })
}

async function markCompleted() {
  if (!plan.value) return
  try {
    await persistPlan({ status: 'completed' })
    plan.value.status = 'completed'
    setSaveStatus('saved', true)
    showToast('Lesson plan marked as completed', 'success')
  } catch (err) {
    showToast('Cannot update lesson plan status', 'error')
  }
}

// AI autocomplete
async function handleAiSuggest(context, config = {}) {
  return await api.lessonPlan.aiSuggest(plan.value.id, context, config)
}

// Reference upload
async function handleUploadFile(file) {
  return await api.lessonPlan.uploadReference(plan.value.id, file)
}

async function handleUploadText(text) {
  return await api.lessonPlan.uploadReferenceText(plan.value.id, text)
}

// Export PDF (print-based)
function exportPDF() {
  const editorEl = document.querySelector('.editor-content .tiptap')
  if (!editorEl) return

  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>${escapeHtml(plan.value.title || 'Giáo án')}</title>
      <style>
        body { font-family: 'Times New Roman', serif; max-width: 800px; margin: 0 auto; padding: 40px; line-height: 1.8; color: #1a1a1a; }
        h1 { font-size: 24px; text-align: center; margin-bottom: 8px; }
        h2 { font-size: 20px; margin-top: 24px; }
        h3 { font-size: 17px; margin-top: 18px; }
        p { margin: 8px 0; text-align: justify; }
        ul, ol { padding-left: 24px; }
        li { margin: 4px 0; }
        blockquote { border-left: 3px solid #333; padding-left: 12px; font-style: italic; margin: 12px 0; }
        hr { border: none; border-top: 1px solid #ccc; margin: 20px 0; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .meta { text-align: center; font-size: 14px; color: #555; margin: 4px 0; }
        @media print { body { padding: 20px; } }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>${escapeHtml(plan.value.title || 'Giáo án')}</h1>
        ${plan.value.subject ? `<p class="meta">Môn học: ${escapeHtml(plan.value.subject)}</p>` : ''}
        ${plan.value.grade_level ? `<p class="meta">Khối lớp: ${escapeHtml(plan.value.grade_level)}</p>` : ''}
      </div>
      ${editorEl.innerHTML}
    </body>
    </html>
  `)
  printWindow.document.close()
  printWindow.print()
}

function scrollToSection(index) {
  const headings = document.querySelectorAll('.editor-content .tiptap h2, .editor-content .tiptap h3')
  const target = headings[index]
  if (!target) return
  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  target.classList.add('section-highlight')
  setTimeout(() => target.classList.remove('section-highlight'), 1400)
}

function setSaveStatus(status, autoClear = false) {
  if (saveStatusTimer) clearTimeout(saveStatusTimer)
  saveStatus.value = status
  if (autoClear) {
    saveStatusTimer = setTimeout(() => { saveStatus.value = '' }, 2500)
  }
}

function escapeHtml(value = '') {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

// Ctrl+S shortcut
function handleKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault()
    savePlan()
  }
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

onMounted(() => {
  fetchPlan()
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  if (saveStatusTimer) clearTimeout(saveStatusTimer)
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
@reference "tailwindcss";

.action-button {
  @apply flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg transition-colors text-sm;
}

:deep(.section-highlight) {
  @apply bg-blue-100 rounded transition-colors;
}
</style>
