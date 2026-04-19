<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5">
    <label class="block text-sm font-medium text-gray-700 mb-1.5">Select Class</label>
    <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)"
      class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm bg-white">
      <option value="">-- Select Class --</option>
      <option v-for="cls in classes" :key="cls.id" :value="cls.id">
        {{ cls.name }} ({{ cls.code }})
      </option>
    </select>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  classes: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['update:modelValue'])
</script>
