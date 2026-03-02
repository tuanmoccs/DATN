<template>
  <div>
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Lesson Content</h3>

    <!-- Empty -->
    <div v-if="contents.length === 0" class="text-center py-12 bg-gray-50 rounded-xl">
      <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
      <h4 class="text-base font-semibold text-gray-600">No content uploaded</h4>
    </div>

    <!-- Content List -->
    <div v-else class="space-y-3">
      <div v-for="content in contents" :key="content.id"
        class="bg-white border border-gray-200 rounded-xl p-5 flex items-start gap-4">
        <!-- Icon -->
        <div :class="iconBg(content.content_type)"
          class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0">
          <i :class="iconClass(content.content_type)" class="text-lg"></i>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-sm font-semibold text-gray-800 uppercase">{{ content.content_type }}</span>
            <span v-if="content.is_primary"
              class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full">Primary</span>
          </div>

          <!-- Text content preview -->
          <div v-if="content.content_text" class="text-sm text-gray-600 line-clamp-3 mb-2 whitespace-pre-line">
            {{ content.content_text }}
          </div>

          <!-- File info -->
          <div v-if="content.file_path" class="flex items-center gap-3 text-xs text-gray-500">
            <span><i class="fas fa-file mr-1"></i>{{ content.file_path.split('/').pop() }}</span>
            <span v-if="content.file_size"><i class="fas fa-weight mr-1"></i>{{ formatSize(content.file_size) }}</span>
            <span v-if="content.mime_type"><i class="fas fa-tag mr-1"></i>{{ content.mime_type }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  contents: { type: Array, default: () => [] },
})

const iconClass = (type) => {
  const map = {
    text: 'fas fa-align-left text-blue-600',
    pdf: 'fas fa-file-pdf text-red-600',
    document: 'fas fa-file-word text-blue-700',
    presentation: 'fas fa-file-powerpoint text-orange-600',
    image: 'fas fa-image text-green-600',
  }
  return map[type] || 'fas fa-file text-gray-600'
}

const iconBg = (type) => {
  const map = {
    text: 'bg-blue-50',
    pdf: 'bg-red-50',
    document: 'bg-blue-50',
    presentation: 'bg-orange-50',
    image: 'bg-green-50',
  }
  return map[type] || 'bg-gray-50'
}

const formatSize = (bytes) => {
  if (!bytes) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>

<style scoped>
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
