<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-lg font-semibold text-gray-800">Presentation Slides</h3>
      <div class="flex items-center gap-2">
        <button v-if="slides.length > 0" @click="toggleEditing" :disabled="regenerating || saving || !!uploadingSlideKey" :class="[
          'flex items-center gap-2 px-4 py-2 rounded-lg transition-colors text-sm font-medium disabled:opacity-50',
          editing ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-blue-600 text-white hover:bg-blue-700'
        ]">
          <i :class="editing ? 'fas fa-times' : 'fas fa-pen'"></i>
          {{ editing ? 'Cancel' : 'Edit slides' }}
        </button>
        <button @click="handleRegenerate" :disabled="regenerating || saving || !!uploadingSlideKey"
          class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium disabled:opacity-50">
          <i :class="regenerating ? 'fas fa-spinner fa-spin' : 'fas fa-magic'"></i>
          {{ regenerating ? 'Generating...' : 'Regenerate with AI' }}
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="slides.length === 0 && !regenerating" class="text-center py-12 bg-gray-50 rounded-xl">
      <i class="fas fa-desktop text-4xl text-gray-300 mb-3"></i>
      <h4 class="text-base font-semibold text-gray-600 mb-1">No slides generated yet</h4>
      <p class="text-sm text-gray-400 mb-4">Click "Regenerate with AI" to create slides from lesson content</p>
    </div>

    <!-- Regenerating indicator -->
    <div v-if="regenerating" class="p-6 bg-purple-50 border border-purple-200 rounded-xl mb-6">
      <div class="flex items-center gap-3">
        <i class="fas fa-robot text-purple-600 text-xl animate-bounce"></i>
        <div>
          <p class="text-sm font-medium text-purple-700">AI is generating slides...</p>
          <p class="text-xs text-purple-500">This may take 30-60 seconds. Please wait.</p>
        </div>
      </div>
    </div>

    <!-- Slide Carousel -->
    <div v-if="editing" class="mb-6 border border-gray-200 rounded-xl bg-white overflow-hidden">
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gray-50">
        <p class="text-sm font-semibold text-gray-700">Slide editor</p>
        <div class="flex items-center gap-2">
          <button @click="addSlide"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">
            <i class="fas fa-plus"></i>Add slide
          </button>
          <button @click="saveSlides" :disabled="saving || !!uploadingSlideKey || editableSlides.length === 0"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50">
            <i :class="saving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
            {{ saving ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </div>

      <div class="divide-y divide-gray-100">
        <div v-for="(slide, idx) in editableSlides" :key="slide.localKey" class="p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono text-gray-400">Slide {{ idx + 1 }}</span>
              <select v-model="slide.layout"
                class="text-xs border border-gray-200 rounded-lg px-2 py-1 bg-white text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option v-for="layout in layoutOptions" :key="layout" :value="layout">{{ layout }}</option>
              </select>
            </div>
            <div class="flex items-center gap-1">
              <button @click="moveSlide(idx, -1)" :disabled="idx === 0"
                class="w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30">
                <i class="fas fa-arrow-up"></i>
              </button>
              <button @click="moveSlide(idx, 1)" :disabled="idx === editableSlides.length - 1"
                class="w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 disabled:opacity-30">
                <i class="fas fa-arrow-down"></i>
              </button>
              <button @click="removeSlide(idx)" class="w-8 h-8 rounded-lg text-red-500 hover:bg-red-50">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            <label class="block">
              <span class="text-xs font-medium text-gray-500">Title</span>
              <input v-model="slide.title" type="text"
                class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </label>
            <div>
              <span class="text-xs font-medium text-gray-500">Image</span>
              <div class="mt-1 flex items-center gap-3">
                <div class="w-24 h-14 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                  <img v-if="getSlideImageUrl(slide)" :src="getSlideImageUrl(slide)" :alt="slide.title"
                    class="w-full h-full object-cover" />
                  <i v-else class="fas fa-image text-gray-300"></i>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <label
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 cursor-pointer">
                    <i :class="uploadingSlideKey === slide.localKey ? 'fas fa-spinner fa-spin' : 'fas fa-upload'"></i>
                    {{ uploadingSlideKey === slide.localKey ? 'Uploading...' : 'Upload image' }}
                    <input type="file" accept="image/*" class="hidden" :disabled="uploadingSlideKey === slide.localKey"
                      @change="handleSlideImageChange($event, slide)" />
                  </label>
                  <button v-if="slide.image_url" @click="slide.image_url = ''"
                    class="px-3 py-2 text-sm font-medium text-red-600 bg-red-50 rounded-lg hover:bg-red-100">
                    <i class="fas fa-times mr-1"></i>Remove
                  </button>
                </div>
              </div>
            </div>
          </div>

          <label class="block mt-3">
            <span class="text-xs font-medium text-gray-500">Content</span>
            <textarea v-model="slide.content" rows="5"
              class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </label>

          <label class="block mt-3">
            <span class="text-xs font-medium text-gray-500">Speaker notes</span>
            <textarea v-model="slide.notes" rows="3"
              class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </label>
        </div>
      </div>
    </div>

    <div v-if="slides.length > 0" class="space-y-4">
      <!-- Main Slide Display -->
      <div class="relative group">
        <!-- Previous Button -->
        <button @click="goTo(currentIndex - 1)" :disabled="currentIndex === 0"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 bg-white border border-gray-200 rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:bg-gray-50 transition disabled:opacity-0 disabled:pointer-events-none">
          <i class="fas fa-chevron-left"></i>
        </button>

        <!-- Slide Card -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 shadow-sm bg-white"
          :class="editing ? 'cursor-default' : 'cursor-pointer'" @click="!editing && openFullscreen()">
          <!-- Slide Content Area -->
          <div
            class="aspect-video bg-gradient-to-br from-blue-600 to-blue-800 p-8 md:p-12 flex flex-col justify-center relative">
            <!-- Slide number badge -->
            <div class="absolute top-4 left-4 flex items-center gap-2">
              <span class="text-xs font-mono bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full">
                Slide {{ currentSlide.order }} / {{ slides.length }}
              </span>
              <span class="text-xs bg-white/15 backdrop-blur-sm text-blue-200 px-2 py-1 rounded-full">
                {{ currentSlide.layout }}
              </span>
            </div>
            <!-- Fullscreen hint -->
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
              <span class="text-xs bg-white/20 backdrop-blur-sm text-white px-3 py-1 rounded-full">
                <i class="fas fa-expand mr-1"></i>Click to expand
              </span>
            </div>
            <!-- Title & Content -->
            <h3 class="text-xl md:text-2xl font-bold text-white mb-4 mt-4">{{ currentSlide.title }}</h3>
            <div
              class="text-blue-100 text-sm md:text-base whitespace-pre-line leading-relaxed max-h-48 overflow-y-auto pr-2 custom-scrollbar">
              {{ currentSlide.content }}
            </div>
            <div v-if="getSlideImageUrl(currentSlide)" class="flex justify-center mt-4">
              <img :src="getSlideImageUrl(currentSlide)" :alt="currentSlide.title"
                class="rounded-xl shadow-xl object-cover max-h-[320px] max-w-full" />
            </div>
          </div>

          <!-- Speaker Notes -->
          <div v-if="currentSlide.notes" class="px-6 py-4 bg-amber-50 border-t border-amber-100">
            <p class="text-xs font-semibold text-amber-700 mb-1"><i class="fas fa-sticky-note mr-1"></i> Speaker Notes
            </p>
            <p class="text-sm text-amber-800 leading-relaxed">{{ currentSlide.notes }}</p>
          </div>
        </div>

        <!-- Next Button -->
        <button @click="goTo(currentIndex + 1)" :disabled="currentIndex === slides.length - 1"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 bg-white border border-gray-200 rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:bg-gray-50 transition disabled:opacity-0 disabled:pointer-events-none">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>

      <!-- Slide Thumbnails Strip -->
      <div class="relative">
        <div ref="thumbStrip" class="flex gap-2 overflow-x-auto pb-2 px-1 snap-x snap-mandatory custom-scrollbar-h">
          <button v-for="(slide, idx) in slides" :key="slide.id" @click="goTo(idx)" :class="[
            'flex-shrink-0 w-32 h-20 rounded-lg border-2 transition-all snap-start overflow-hidden',
            idx === currentIndex
              ? 'border-blue-500 shadow-md ring-2 ring-blue-200'
              : 'border-gray-200 hover:border-gray-300 opacity-70 hover:opacity-100'
          ]">
            <div class="w-full h-full bg-gradient-to-br from-blue-600 to-blue-800 p-2 flex flex-col justify-end relative">
              <img v-if="getSlideImageUrl(slide)" :src="getSlideImageUrl(slide)" :alt="slide.title"
                class="absolute inset-0 w-full h-full object-cover opacity-45" />
              <div class="absolute inset-0 bg-blue-900/35"></div>
              <div class="relative">
              <span class="text-[10px] text-blue-200 font-mono">{{ slide.order }}</span>
              <span class="text-[11px] text-white font-medium leading-tight line-clamp-2">{{ slide.title }}</span>
              </div>
            </div>
          </button>
        </div>
      </div>

      <!-- Keyboard hint -->
      <p class="text-xs text-gray-400 text-center">
        <i class="fas fa-keyboard mr-1"></i> Use <kbd
          class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">←</kbd>
        <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">→</kbd> arrow keys to navigate
      </p>
    </div>

    <!-- Fullscreen Modal -->
    <Teleport to="body">
      <div v-if="fullscreen" class="fixed inset-0 z-50 bg-black flex flex-col" @keydown="handleKey">
        <!-- Top bar -->
        <div class="flex items-center justify-between px-6 py-3 bg-black/80">
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-400 font-mono">Slide {{ currentSlide.order }} / {{ slides.length }}</span>
            <span class="text-xs px-2 py-0.5 bg-white/10 text-gray-400 rounded-full">{{ currentSlide.layout }}</span>
          </div>
          <button @click="fullscreen = false" class="text-gray-400 hover:text-white transition">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        <!-- Slide area -->
        <div class="flex-1 flex items-center justify-center px-16 relative">
          <!-- Prev -->
          <button @click="goTo(currentIndex - 1)" :disabled="currentIndex === 0"
            class="absolute left-4 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white transition disabled:opacity-0">
            <i class="fas fa-chevron-left text-xl"></i>
          </button>

          <div class="w-full max-w-5xl">
            <div
              class="aspect-video bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-10 md:p-16 flex flex-col justify-center">
              <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">{{ currentSlide.title }}</h2>
              <div class="text-blue-100 text-lg md:text-xl whitespace-pre-line leading-relaxed">{{ currentSlide.content
              }}</div>
              <div v-if="getSlideImageUrl(currentSlide)" class="flex justify-center mt-6">
                <img :src="getSlideImageUrl(currentSlide)" :alt="currentSlide.title"
                  class="rounded-xl shadow-xl object-cover max-h-[320px] max-w-full" />
              </div>
            </div>

            <div v-if="currentSlide.notes" class="mt-4 bg-amber-900/30 rounded-xl px-6 py-4">
              <p class="text-xs font-semibold text-amber-400 mb-1"><i class="fas fa-sticky-note mr-1"></i> Speaker Notes
              </p>
              <p class="text-sm text-amber-200 leading-relaxed">{{ currentSlide.notes }}</p>
            </div>
          </div>

          <!-- Next -->
          <button @click="goTo(currentIndex + 1)" :disabled="currentIndex === slides.length - 1"
            class="absolute right-4 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white transition disabled:opacity-0">
            <i class="fas fa-chevron-right text-xl"></i>
          </button>
        </div>

        <!-- Bottom dots -->
        <div class="flex items-center justify-center gap-1.5 py-4">
          <button v-for="(slide, idx) in slides" :key="slide.id" @click="goTo(idx)" :class="[
            'w-2 h-2 rounded-full transition-all',
            idx === currentIndex ? 'bg-white w-6' : 'bg-white/30 hover:bg-white/50'
          ]" />
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useApi } from '@/plugins/api'

const props = defineProps({
  slides: { type: Array, default: () => [] },
  lessonId: { type: Number, required: true },
})

const emit = defineEmits(['regenerate', 'updated', 'toast'])

const api = useApi()
const regenerating = ref(false)
const saving = ref(false)
const editing = ref(false)
const editableSlides = ref([])
const uploadingSlideKey = ref(null)
const currentIndex = ref(0)
const fullscreen = ref(false)
const thumbStrip = ref(null)
const storageBaseUrl = (import.meta.env.VITE_STORAGE_ENDPOINT || 'http://localhost:8000/storage').replace(/\/$/, '')
const layoutOptions = ['title', 'content', 'two_column', 'image', 'bullet_points']

const currentSlide = computed(() => props.slides[currentIndex.value] || {})

const cloneSlidesForEdit = () => props.slides.map((slide, index) => ({
  id: slide.id || null,
  localKey: slide.id ? `slide-${slide.id}` : `new-${Date.now()}-${index}`,
  title: slide.title || '',
  content: slide.content || '',
  notes: slide.notes || '',
  layout: layoutOptions.includes(slide.layout) ? slide.layout : 'content',
  image_url: slide.image_url || '',
}))

const toggleEditing = () => {
  editing.value = !editing.value
  editableSlides.value = editing.value ? cloneSlidesForEdit() : []
}

const addSlide = () => {
  editableSlides.value.push({
    id: null,
    localKey: `new-${Date.now()}-${editableSlides.value.length}`,
    title: 'New slide',
    content: '',
    notes: '',
    layout: 'content',
    image_url: '',
  })
}

const removeSlide = (idx) => {
  editableSlides.value.splice(idx, 1)
  if (currentIndex.value >= editableSlides.value.length) {
    currentIndex.value = Math.max(editableSlides.value.length - 1, 0)
  }
}

const moveSlide = (idx, direction) => {
  const nextIdx = idx + direction
  if (nextIdx < 0 || nextIdx >= editableSlides.value.length) return

  const nextSlides = [...editableSlides.value]
  const [slide] = nextSlides.splice(idx, 1)
  nextSlides.splice(nextIdx, 0, slide)
  editableSlides.value = nextSlides
}

const saveSlides = async () => {
  const invalidSlide = editableSlides.value.find(slide => !slide.content.trim())
  if (invalidSlide) {
    emit('toast', 'Slide content is required', 'error')
    return
  }

  saving.value = true
  try {
    const payload = editableSlides.value.map((slide) => ({
      id: slide.id,
      title: slide.title,
      content: slide.content,
      notes: slide.notes || null,
      layout: slide.layout,
      image_url: slide.image_url || null,
    }))
    await api.lesson.updateSlides(props.lessonId, payload)
    emit('toast', 'Slides saved successfully!')
    emit('updated')
    editing.value = false
  } catch (err) {
    emit('toast', err.response?.data?.message || 'Failed to save slides', 'error')
  } finally {
    saving.value = false
  }
}

const handleSlideImageChange = async (event, slide) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  uploadingSlideKey.value = slide.localKey
  try {
    const response = await api.lesson.uploadSlideImage(props.lessonId, file)
    slide.image_url = response.image_url || response.url || response.path || ''
    emit('toast', 'Slide image uploaded successfully!')
  } catch (err) {
    emit('toast', err.response?.data?.message || 'Failed to upload slide image', 'error')
  } finally {
    uploadingSlideKey.value = null
  }
}

const getSlideImageUrl = (slide) => {
  const imageUrl = slide?.image_url
  if (!imageUrl) return ''
  if (/^(https?:)?\/\//.test(imageUrl) || imageUrl.startsWith('data:image')) return imageUrl
  if (imageUrl.startsWith('/storage/')) return `${storageBaseUrl}${imageUrl.replace('/storage', '')}`
  if (imageUrl.startsWith('storage/')) return `${storageBaseUrl}/${imageUrl.replace(/^storage\//, '')}`
  if (imageUrl.startsWith('slides/')) return `${storageBaseUrl}/${imageUrl}`
  return imageUrl
}

const goTo = (idx) => {
  if (idx >= 0 && idx < props.slides.length) {
    currentIndex.value = idx
    scrollThumbIntoView(idx)
  }
}

const scrollThumbIntoView = async (idx) => {
  await nextTick()
  if (thumbStrip.value) {
    const btn = thumbStrip.value.children[idx]
    if (btn) btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
  }
}

const openFullscreen = () => {
  fullscreen.value = true
}

const handleKey = (e) => {
  if (e.key === 'ArrowLeft') goTo(currentIndex.value - 1)
  else if (e.key === 'ArrowRight') goTo(currentIndex.value + 1)
  else if (e.key === 'Escape') fullscreen.value = false
}

const onKeydown = (e) => {
  if (fullscreen.value) return // handled by modal
  if (props.slides.length === 0) return
  if (e.key === 'ArrowLeft') goTo(currentIndex.value - 1)
  else if (e.key === 'ArrowRight') goTo(currentIndex.value + 1)
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))

watch(() => props.slides.length, () => {
  if (currentIndex.value >= props.slides.length) currentIndex.value = 0
  if (editing.value) editableSlides.value = cloneSlidesForEdit()
})

const handleRegenerate = async () => {
  regenerating.value = true
  try {
    await api.lesson.regenerateSlides(props.lessonId)
    emit('toast', 'Slides regenerated successfully!')
    emit('regenerate')
    currentIndex.value = 0
  } catch (err) {
    emit('toast', err.response?.data?.message || 'Failed to regenerate slides', 'error')
  } finally {
    regenerating.value = false
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}

.custom-scrollbar-h::-webkit-scrollbar {
  height: 4px;
}

.custom-scrollbar-h::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 4px;
}

.custom-scrollbar-h::-webkit-scrollbar-track {
  background: transparent;
}
</style>
