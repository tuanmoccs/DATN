<template>
  <div class="bg-white border border-gray-200 rounded-lg p-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">
      <i class="fas fa-file-upload mr-1"></i> Tai lieu tham khao
    </h3>
    <p class="text-xs text-gray-500 mb-3">
      Upload tai lieu de AI co ngu canh goi y noi dung chinh xac hon.
    </p>

    <div
      :class="[
        'border-2 border-dashed rounded-lg p-4 text-center transition-colors cursor-pointer',
        isDragging ? 'border-blue-400 bg-blue-50' : 'border-gray-300 hover:border-gray-400',
      ]"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
      @click="$refs.fileInput.click()"
    >
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        accept=".pdf,.docx,.txt"
        @change="handleFileSelect"
      />
      <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
      <p class="text-sm text-gray-500">Keo tha file hoac click de chon</p>
      <p class="text-xs text-gray-400 mt-1">PDF, DOCX, TXT (toi da 20MB)</p>
    </div>

    <div v-if="isUploading" class="mt-3 flex items-center gap-2 text-sm text-blue-600">
      <i class="fas fa-spinner fa-spin"></i>
      <span>Dang xu ly tai lieu...</span>
    </div>

    <div v-if="uploadResult" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
      <div class="flex items-center gap-2 text-sm text-green-700">
        <i class="fas fa-check-circle"></i>
        <span>{{ uploadResult.message }}</span>
      </div>
      <p v-if="uploadResult.chunks_count" class="text-xs text-green-600 mt-1">
        Da phan tich thanh {{ uploadResult.chunks_count }} doan van ban
      </p>
    </div>

    <div v-if="uploadError" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex items-center gap-2 text-sm text-red-700">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ uploadError }}</span>
      </div>
    </div>

    <div class="mt-4 border-t border-gray-100 pt-3">
      <button
        class="text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1"
        @click="showTextInput = !showTextInput"
      >
        <i :class="showTextInput ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-xs"></i>
        Hoac nhap noi dung tham khao truc tiep
      </button>
      <div v-if="showTextInput" class="mt-2">
        <textarea
          v-model="referenceText"
          rows="5"
          placeholder="Dan noi dung tai lieu tham khao vao day..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-y"
        ></textarea>
        <button
          class="mt-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!referenceText.trim() || isUploading"
          @click="submitText"
        >
          <i class="fas fa-paper-plane mr-1"></i> Gui phan tich
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  uploadFileFn: { type: Function, required: true },
  uploadTextFn: { type: Function, required: true },
})

const isDragging = ref(false)
const isUploading = ref(false)
const uploadResult = ref(null)
const uploadError = ref(null)
const showTextInput = ref(false)
const referenceText = ref('')

function handleDrop(event) {
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) uploadFile(file)
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) uploadFile(file)
  event.target.value = ''
}

async function uploadFile(file) {
  const maxSize = 20 * 1024 * 1024
  if (file.size > maxSize) {
    uploadError.value = 'File qua lon. Toi da 20MB.'
    return
  }

  const allowedTypes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
  ]

  if (!allowedTypes.includes(file.type)) {
    uploadError.value = 'Dinh dang khong ho tro. Chi chap nhan PDF, DOCX, TXT.'
    return
  }

  isUploading.value = true
  uploadError.value = null
  uploadResult.value = null

  try {
    const result = await props.uploadFileFn(file)
    uploadResult.value = result
  } catch (err) {
    uploadError.value =
      err?.code === 'ECONNABORTED'
        ? 'Xu ly tai lieu qua lau. Hay thu lai voi file nhe hon hoac doi lau hon.'
        : err?.response?.data?.message || 'Loi khi upload tai lieu'
  } finally {
    isUploading.value = false
  }
}

async function submitText() {
  if (!referenceText.value.trim()) return

  isUploading.value = true
  uploadError.value = null
  uploadResult.value = null

  try {
    const result = await props.uploadTextFn(referenceText.value)
    uploadResult.value = result
    referenceText.value = ''
    showTextInput.value = false
  } catch (err) {
    uploadError.value = err?.response?.data?.message || 'Loi khi xu ly noi dung'
  } finally {
    isUploading.value = false
  }
}
</script>
