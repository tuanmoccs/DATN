<template>
  <div class="bg-white border border-gray-200 rounded-lg p-4">
    <div class="flex items-start justify-between gap-3 mb-3">
      <div>
        <h3 class="text-sm font-semibold text-gray-700">
          <i class="fas fa-sliders-h mr-1"></i> Sandbox RAG Settings
        </h3>
        <p class="text-xs text-gray-500 mt-1">Only affects this sandbox test.</p>
      </div>
      <button type="button" class="text-xs text-gray-500 hover:text-blue-600" @click="resetDefaults">
        Default
      </button>
    </div>

    <div class="space-y-3">
      <label class="block">
        <span class="flex items-center justify-between text-xs font-medium text-gray-500 mb-1">
          <span>Chunk size</span>
          <span>{{ modelValue.chunk_size }}</span>
        </span>
        <input v-model.number="modelValue.chunk_size" type="range" min="300" max="3000" step="100" class="w-full" />
        <p class="mt-1 text-[11px] text-gray-500">
          Do dai moi doan van ban sau khi cat tai lieu. Nho hon thi tim y chi tiet hon; lon hon thi giu duoc ngu canh dai hon.
        </p>
      </label>

      <label class="block">
        <span class="flex items-center justify-between text-xs font-medium text-gray-500 mb-1">
          <span>Overlap</span>
          <span>{{ modelValue.chunk_overlap }}</span>
        </span>
        <input v-model.number="modelValue.chunk_overlap" type="range" min="0" :max="maxOverlap" step="50" class="w-full" />
        <p class="mt-1 text-[11px] text-gray-500">
          So ky tu lap lai giua 2 chunk lien tiep. Cao hon giup khong mat y o ranh gioi, nhung tao nhieu noi dung trung lap.
        </p>
      </label>

      <div class="grid grid-cols-2 gap-3">
        <label class="block">
          <span class="block text-xs font-medium text-gray-500 mb-1">Top K</span>
          <input v-model.number="modelValue.top_k" type="number" min="1" max="12"
            class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm" />
          <p class="mt-1 text-[11px] text-gray-500">So chunk lay ra khi truy van.</p>
        </label>
        <label class="block">
          <span class="block text-xs font-medium text-gray-500 mb-1">Threshold</span>
          <input v-model.number="modelValue.score_threshold" type="number" min="0" max="1" step="0.05"
            class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm" />
          <p class="mt-1 text-[11px] text-gray-500">Nguong diem lien quan toi thieu.</p>
        </label>
      </div>

      <label class="block">
        <span class="block text-xs font-medium text-gray-500 mb-1">Max context chars</span>
        <input v-model.number="modelValue.max_context_chars" type="number" min="1000" max="30000" step="1000"
          class="w-full px-3 py-1.5 border border-gray-300 rounded text-sm" />
        <p class="mt-1 text-[11px] text-gray-500">
          Gioi han tong so ky tu context dua vao LLM. Cao hon doc duoc nhieu hon, nhung ton token hon.
        </p>
      </label>

      <label class="flex items-center gap-2 text-xs text-gray-600">
        <input v-model="modelValue.low_confidence_fallback" type="checkbox" class="rounded border-gray-300 text-blue-600" />
        Allow low-confidence fallback
      </label>
      <p class="text-[11px] text-gray-500">
        Khi bat, neu khong chunk nao dat threshold, he thong van lay chunk co diem gan nhat de ban quan sat.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'

const defaults = {
  chunk_size: 1000,
  chunk_overlap: 200,
  top_k: 5,
  score_threshold: 0.45,
  max_context_chars: 12000,
  low_confidence_fallback: true,
}

const modelValue = defineModel({ type: Object, required: true })
const maxOverlap = computed(() => Math.max(0, modelValue.value.chunk_size - 1))

watch(modelValue, () => {
  if (modelValue.value.chunk_overlap >= modelValue.value.chunk_size) {
    modelValue.value.chunk_overlap = modelValue.value.chunk_size - 1
  }
}, { deep: true })

function resetDefaults() {
  Object.assign(modelValue.value, defaults)
}
</script>
