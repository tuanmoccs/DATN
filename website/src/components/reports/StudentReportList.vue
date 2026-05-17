<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200">
      <h3 class="text-base font-semibold text-gray-800">Students</h3>
      <p class="text-sm text-gray-500 mt-1">Choose a student to view or generate an AI report.</p>
    </div>

    <div v-if="students.length === 0" class="text-center py-12">
      <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
      <p class="text-gray-500">No active students in this class</p>
    </div>

    <div v-else class="divide-y divide-gray-100">
      <button v-for="enrollment in students" :key="enrollment.id" @click="$emit('select', enrollment)"
        class="w-full px-5 py-4 text-left hover:bg-gray-50 transition-colors"
        :class="selectedStudentId === enrollment.user?.id ? 'bg-blue-50' : ''">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
            {{ enrollment.user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-800 truncate">{{ enrollment.user?.name }}</p>
            <p class="text-xs text-gray-500 truncate">{{ enrollment.user?.email }}</p>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  students: {
    type: Array,
    default: () => [],
  },
  selectedStudentId: {
    type: [Number, String],
    default: null,
  },
})

defineEmits(['select'])
</script>
