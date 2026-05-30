<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">RAG Sandbox</h2>
        <p class="text-gray-500 mt-1">Upload a document, tune RAG settings, inspect retrieval, and preview slide or quiz JSON without changing lessons.</p>
      </div>
      <span v-if="sandboxId" class="text-xs font-mono px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg">
        {{ sandboxId }}
      </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-sm font-semibold text-blue-800 mb-1">1. Process</p>
        <p class="text-xs text-blue-700">Tai lieu duoc extract text, cat thanh chunks theo chunk size/overlap, roi luu tam vao ChromaDB bang sandbox_id.</p>
      </div>
      <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
        <p class="text-sm font-semibold text-indigo-800 mb-1">2. Retrieve</p>
        <p class="text-xs text-indigo-700">Query se tim cac chunk gan nghia nhat, loc bang threshold, roi ghep thanh final context.</p>
      </div>
      <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
        <p class="text-sm font-semibold text-emerald-800 mb-1">3. Generate JSON</p>
        <p class="text-xs text-emerald-700">Slide/quiz preview chi dung context cua sandbox, khong ghi vao bai hoc that.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[360px_1fr] gap-6">
      <div class="space-y-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">
            <i class="fas fa-file-upload mr-1"></i> Document
          </h3>
          <label class="block border-2 border-dashed border-gray-300 hover:border-blue-400 rounded-lg p-4 text-center cursor-pointer transition-colors">
            <input type="file" class="hidden" accept=".pdf,.docx,.txt" @change="handleFileChange" />
            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
            <p class="text-sm text-gray-600">{{ selectedFile?.name || 'Choose PDF, DOCX, or TXT' }}</p>
            <p class="text-xs text-gray-400 mt-1">Max 20MB</p>
          </label>
          <button
            @click="processFile"
            :disabled="!selectedFile || processing"
            class="w-full mt-3 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium disabled:opacity-50"
          >
            <i :class="processing ? 'fas fa-spinner fa-spin' : 'fas fa-cubes'" class="mr-1"></i>
            Process Document
          </button>
        </div>

        <RagSandboxSettings v-model="settings" />
      </div>

      <div class="space-y-4">
        <div v-if="processResult" class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
            <Metric label="Extracted chars" :value="processResult.extracted_characters" />
            <Metric label="Chunks" :value="processResult.chunks_count" />
            <Metric label="File type" :value="processResult.content_type" />
          </div>
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Extracted preview</h3>
          <p class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg p-3 max-h-36 overflow-y-auto">
            {{ processResult.extracted_preview }}
          </p>
          <div class="mt-4 border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
              First chunks after splitting
            </div>
            <div class="divide-y divide-gray-100">
              <div v-for="chunk in processResult.chunks" :key="chunk.index" class="p-3">
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-xs font-mono text-gray-400">#{{ chunk.index }}</span>
                  <span class="text-xs text-gray-500">{{ chunk.characters }} chars</span>
                  <span v-if="chunk.overlap_characters"
                    class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">
                    {{ chunk.overlap_characters }} overlap chars
                  </span>
                </div>
                <div class="text-sm text-gray-700 leading-relaxed space-y-2">
                  <div v-if="chunk.overlap_preview" class="rounded-lg border border-amber-200 bg-amber-50 p-2">
                    <p class="text-[11px] font-semibold uppercase text-amber-700 mb-1">Overlap from previous chunk</p>
                    <mark class="font-semibold bg-amber-200 text-amber-950 px-1 rounded">
                      {{ chunk.overlap_preview }}
                    </mark>
                  </div>
                  <div class="rounded-lg border border-gray-200 bg-white p-2">
                    <p v-if="chunk.overlap_preview" class="text-[11px] font-semibold uppercase text-gray-500 mb-1">New content in this chunk</p>
                    <span>{{ chunk.body_preview || chunk.preview }}</span>
                  </div>
                </div>
                <div v-if="chunk.overlap_preview" class="mt-3 grid grid-cols-1 lg:grid-cols-2 gap-3">
                  <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <p class="text-[11px] font-semibold uppercase text-amber-700 mb-1">Exact tail of previous chunk</p>
                    <p class="text-xs text-amber-900 leading-relaxed">{{ chunk.previous_tail_preview }}</p>
                  </div>
                  <div class="rounded-lg border border-amber-300 bg-amber-100 p-3">
                    <p class="text-[11px] font-semibold uppercase text-amber-800 mb-1">Exact start of this chunk</p>
                    <p class="text-xs font-semibold text-amber-950 leading-relaxed">{{ chunk.overlap_preview }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <p class="mt-2 text-xs text-gray-500">
            Phan mau vang la dau chunk hien tai lap lai tu cuoi chunk truoc. Chunk preview phia tren chi hien phan dau moi chunk, nen overlap se khong nam o dau chunk truoc ma nam o cuoi chunk truoc.
          </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-end gap-3 mb-4">
            <label class="block flex-1">
              <span class="block text-xs font-medium text-gray-500 mb-1">Retrieval query</span>
              <input
                v-model="query"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                placeholder="VD: tom tat khai niem trong tam"
              />
            </label>
            <button
              @click="retrieve"
              :disabled="!sandboxId || retrieving"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium disabled:opacity-50"
            >
              <i :class="retrieving ? 'fas fa-spinner fa-spin' : 'fas fa-search'" class="mr-1"></i>
              Retrieve
            </button>
          </div>

          <div v-if="retrieveResult" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <Metric label="Returned chunks" :value="retrieveResult.chunks_returned" />
              <Metric label="Context chars" :value="retrieveResult.context_characters" />
              <Metric label="Threshold" :value="retrieveResult.settings?.score_threshold" />
            </div>
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 text-xs text-indigo-700">
              <strong>Score:</strong> diem gan nghia tu vector search. <strong>Lexical:</strong> muc trung tu khoa voi query.
              <strong>Combined:</strong> diem tong hop de sap xep chunk. <strong>Passed:</strong> chunk vuot threshold va duoc dua vao context.
            </div>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">Chunks</div>
              <div v-if="retrieveResult.chunks?.length" class="divide-y divide-gray-100">
                <div v-for="chunk in retrieveResult.chunks" :key="chunk.index" class="p-4">
                  <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="text-xs font-mono text-gray-400">#{{ chunk.index }}</span>
                    <span :class="chunk.passed_threshold ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                      class="px-2 py-0.5 rounded-full text-xs font-medium">
                      {{ chunk.passed_threshold ? 'passed' : 'fallback' }}
                    </span>
                    <span class="text-xs text-gray-500">score {{ chunk.relevance_score }}</span>
                    <span class="text-xs text-gray-500">lexical {{ chunk.lexical_score }}</span>
                    <span class="text-xs text-gray-500">combined {{ chunk.combined_score }}</span>
                    <span class="text-xs text-gray-400">{{ chunk.characters }} chars</span>
                  </div>
                  <p class="text-sm text-gray-700 whitespace-pre-line">{{ chunk.preview }}</p>
                </div>
              </div>
              <div v-else class="p-6 text-center text-sm text-gray-500">No chunks returned.</div>
            </div>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                Final context sent to generation
              </div>
              <pre class="p-4 text-sm text-gray-700 whitespace-pre-wrap leading-relaxed max-h-80 overflow-y-auto">{{ retrieveResult.context_preview || 'No context.' }}</pre>
            </div>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-sm font-semibold text-gray-700">Generation JSON Preview</h3>
              <p class="text-xs text-gray-500 mt-1">Uses only the sandbox document and current RAG settings.</p>
            </div>
            <div class="flex items-center gap-2">
              <input v-model.number="generationCount" type="number" min="1" max="30"
                class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm" />
              <button @click="generateSlides" :disabled="!sandboxId || generating"
                class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm font-medium disabled:opacity-50">
                Slides JSON
              </button>
              <button @click="generateQuiz" :disabled="!sandboxId || generating"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50">
                Quiz JSON
              </button>
            </div>
          </div>
          <pre class="bg-gray-950 text-gray-100 rounded-lg p-4 text-xs overflow-auto max-h-[520px]">{{ generationJson || 'No JSON generated yet.' }}</pre>
          <div class="mt-3 bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-600">
            <p class="font-semibold text-gray-700 mb-1">JSON shape</p>
            <p><strong>Slides:</strong> success, lesson_id, slides[], total_slides, message. Moi slide gom slide_number, title, bullet_points, speaker_notes, image_suggestion.</p>
            <p class="mt-1"><strong>Quiz:</strong> success, lesson_id, questions[], total_questions, message. Moi question gom question_number, content, question_type, options[], explanation, points.</p>
          </div>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="toast.show" :class="['fixed bottom-6 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm',
        toast.type === 'success' ? 'bg-green-600' : 'bg-red-600']">
        {{ toast.message }}
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref } from 'vue'
import { useApi } from '@/plugins/api'
import RagSandboxSettings from '@/components/ragSandbox/RagSandboxSettings.vue'

const Metric = defineComponent({
  props: { label: String, value: [String, Number] },
  setup(props) {
    return () => h('div', { class: 'bg-gray-50 border border-gray-200 rounded-lg p-3' }, [
      h('p', { class: 'text-xs text-gray-500' }, props.label),
      h('p', { class: 'text-xl font-semibold text-gray-800' }, String(props.value ?? '')),
    ])
  },
})

const api = useApi()
const selectedFile = ref(null)
const sandboxId = ref('')
const processResult = ref(null)
const retrieveResult = ref(null)
const generationJson = ref('')
const query = ref('')
const processing = ref(false)
const retrieving = ref(false)
const generating = ref(false)
const generationCount = ref(5)
const toast = ref({ show: false, message: '', type: 'success' })
const settings = reactive({
  chunk_size: 1000,
  chunk_overlap: 200,
  top_k: 5,
  score_threshold: 0.45,
  max_context_chars: 12000,
  low_confidence_fallback: true,
})

const requestPayload = computed(() => ({
  sandbox_id: sandboxId.value,
  query: query.value,
  settings: { ...settings },
}))

function handleFileChange(event) {
  selectedFile.value = event.target.files?.[0] || null
}

async function processFile() {
  if (!selectedFile.value) return
  processing.value = true
  retrieveResult.value = null
  generationJson.value = ''
  try {
    const res = await api.ragSandbox.process(selectedFile.value, settings)
    processResult.value = res.data
    sandboxId.value = res.data.sandbox_id
    showToast('Document processed')
  } catch (err) {
    showToast(err?.response?.data?.message || 'Failed to process document', 'error')
  } finally {
    processing.value = false
  }
}

async function retrieve() {
  retrieving.value = true
  try {
    const res = await api.ragSandbox.retrieve(requestPayload.value)
    retrieveResult.value = res.data
  } catch (err) {
    showToast(err?.response?.data?.message || 'Failed to retrieve chunks', 'error')
  } finally {
    retrieving.value = false
  }
}

async function generateSlides() {
  await generate('slides')
}

async function generateQuiz() {
  await generate('quiz')
}

async function generate(type) {
  generating.value = true
  try {
    const payload = {
      ...requestPayload.value,
      count: generationCount.value,
      language: 'Vietnamese',
      difficulty: 'medium',
    }
    const res = type === 'slides'
      ? await api.ragSandbox.slides(payload)
      : await api.ragSandbox.quiz(payload)
    generationJson.value = JSON.stringify(res.data, null, 2)
  } catch (err) {
    showToast(err?.response?.data?.message || `Failed to generate ${type}`, 'error')
  } finally {
    generating.value = false
  }
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}
</script>
