<template>
  <label class="space-y-1 text-sm font-bold text-slate-700">
    <span>{{ label }}</span>
    <input
      :type="type"
      :value="value"
      :readonly="readonly"
      :required="required"
      :class="align === 'right' ? 'text-right' : ''"
      class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20"
    />
  </label>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  type: { type: String, default: 'text' },
  value: { type: String, default: '' },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  align: { type: String, default: 'left' },
})
</script>
