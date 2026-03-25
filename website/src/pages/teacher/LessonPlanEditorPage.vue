<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
    </div>

    <template v-else-if="plan">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <button @click="$router.push({ name: 'TeacherLessonPlans' })"
            class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors text-gray-600">
            <i class="fas fa-arrow-left"></i>
          </button>
          <div>
            <h2 class="text-xl font-bold text-gray-800">{{ plan.title }}</h2>
            <div class="flex items-center gap-3 text-sm text-gray-500 mt-0.5">
              <span v-if="plan.subject"><i class="fas fa-book-open mr-1"></i>{{ plan.subject }}</span>
              <span v-if="plan.grade_level"><i class="fas fa-graduation-cap mr-1"></i>{{ plan.grade_level }}</span>
              <span :class="plan.status === 'completed' ? 'text-green-600' : 'text-yellow-600'">
                <i class="fas fa-circle text-[6px] mr-1"></i>
                {{ plan.status === 'completed' ? 'Hoàn thành' : 'Nháp' }}
              </span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="autoSaveStatus" class="text-xs text-gray-400 mr-2">
            <i :class="autoSaveStatus === 'saving' ? 'fas fa-spinner fa-spin' : 'fas fa-check'"
              class="mr-1"></i>
            {{ autoSaveStatus === 'saving' ? 'Đang lưu...' : 'Đã lưu' }}
          </span>
          <button @click="exportPDF"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm">
            <i class="fas fa-file-pdf"></i> Xuất PDF
          </button>
          <button @click="markCompleted" v-if="plan.status === 'draft'"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
            <i class="fas fa-check"></i> Hoàn thành
          </button>
          <button @click="savePlan"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
            <i class="fas fa-save"></i> Lưu
          </button>
        </div>
      </div>

      <!-- Main Layout: Editor + Sidebar -->
      <div class="flex gap-6">
        <!-- Editor (main) -->
        <div class="flex-1 min-w-0">
          <TiptapEditor v-model="editorContent" :aiSuggestFn="handleAiSuggest"
            placeholder="Bắt đầu soạn giáo án... AI sẽ gợi ý nội dung khi bạn dừng gõ." />
        </div>

        <!-- Sidebar -->
        <div class="w-80 flex-shrink-0 space-y-4">
          <!-- Plan Info -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">
              <i class="fas fa-info-circle mr-1"></i> Thông tin giáo án
            </h3>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tên giáo án</label>
                <input v-model="plan.title" type="text"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Môn học</label>
                <input v-model="plan.subject" type="text" placeholder="VD: Toán"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Khối / Lớp</label>
                <input v-model="plan.grade_level" type="text" placeholder="VD: Lớp 10"
                  class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
            </div>
          </div>

          <!-- Reference Upload -->
          <ReferenceUpload :uploadFileFn="handleUploadFile" :uploadTextFn="handleUploadText" />

          <!-- Keyboard shortcuts -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">
              <i class="fas fa-keyboard mr-1"></i> Phím tắt
            </h3>
            <div class="space-y-1.5 text-xs text-gray-500">
              <div class="flex justify-between">
                <span>Chấp nhận gợi ý</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Tab</kbd>
              </div>
              <div class="flex justify-between">
                <span>Bỏ qua gợi ý</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Esc</kbd>
              </div>
              <div class="flex justify-between">
                <span>Lưu</span>
                <kbd class="px-1.5 py-0.5 bg-white border border-gray-300 rounded text-[10px] font-mono">Ctrl+S</kbd>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Not found -->
    <div v-else-if="!loading" class="text-center py-20">
      <i class="fas fa-exclamation-triangle text-5xl text-gray-300 mb-4"></i>
      <h3 class="text-lg font-semibold text-gray-600">Không tìm thấy giáo án</h3>
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
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
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
const autoSaveStatus = ref('')
const toast = ref({ show: false, message: '', type: 'success' })

let autoSaveTimer = null

// Fetch plan detail
async function fetchPlan() {
  loading.value = true
  try {
    const res = await api.lessonPlan.getDetail(route.params.id)
    plan.value = res.data
    editorContent.value = plan.value.content || ''
  } catch (err) {
    plan.value = null
    showToast('Lỗi khi tải giáo án', 'error')
  } finally {
    loading.value = false
  }
}

// Auto-save when content changes
watch(editorContent, () => {
  if (autoSaveTimer) clearTimeout(autoSaveTimer)
  autoSaveTimer = setTimeout(() => autoSave(), 3000)
})

async function autoSave() {
  if (!plan.value) return
  autoSaveStatus.value = 'saving'
  try {
    await api.lessonPlan.update(plan.value.id, {
      content: editorContent.value,
    })
    autoSaveStatus.value = 'saved'
    setTimeout(() => { autoSaveStatus.value = '' }, 2000)
  } catch {
    autoSaveStatus.value = ''
  }
}

async function savePlan() {
  if (!plan.value) return
  try {
    await api.lessonPlan.update(plan.value.id, {
      title: plan.value.title,
      subject: plan.value.subject,
      grade_level: plan.value.grade_level,
      content: editorContent.value,
    })
    showToast('Đã lưu giáo án', 'success')
  } catch (err) {
    showToast('Lỗi khi lưu giáo án', 'error')
  }
}

async function markCompleted() {
  if (!plan.value) return
  try {
    const res = await api.lessonPlan.update(plan.value.id, {
      content: editorContent.value,
      status: 'completed',
    })
    plan.value.status = 'completed'
    showToast('Giáo án đã được đánh dấu hoàn thành', 'success')
  } catch (err) {
    showToast('Lỗi khi cập nhật trạng thái', 'error')
  }
}

// AI autocomplete
async function handleAiSuggest(text) {
  return await api.lessonPlan.aiSuggest(plan.value.id, text)
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
      <title>${plan.value.title || 'Giáo án'}</title>
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
        <h1>${plan.value.title || 'GIÁO ÁN'}</h1>
        ${plan.value.subject ? `<p class="meta">Môn: ${plan.value.subject}</p>` : ''}
        ${plan.value.grade_level ? `<p class="meta">${plan.value.grade_level}</p>` : ''}
      </div>
      ${editorEl.innerHTML}
    </body>
    </html>
  `)
  printWindow.document.close()
  printWindow.print()
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
  document.removeEventListener('keydown', handleKeydown)
})
</script>
