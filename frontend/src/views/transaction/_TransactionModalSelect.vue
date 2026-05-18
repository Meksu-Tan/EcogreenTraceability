<template>
  <label class="space-y-1 text-sm font-bold text-slate-700">
    <span>{{ label }}</span>
    <select
      :multiple="multiple"
      :required="required"
      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
      :class="multiple ? 'min-h-24' : ''"
    >
      <option value="">{{ placeholder }}</option>
      <option v-for="option in options" :key="option" :value="option">{{ option }}</option>
    </select>
    <span v-if="hint" class="text-xs font-semibold text-slate-500">{{ hint }}</span>
  </label>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  placeholder: { type: String, default: '- Select -' },
  options: { type: Array, default: () => [] },
  multiple: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  hint: { type: String, default: '' },
})
</script>
