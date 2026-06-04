<template>
  <nav class="flex items-center gap-2 text-sm text-slate-600 mb-4">
    <a href="/" class="hover:text-primary transition-colors">Home</a>
    <Icon icon="ri:arrow-right-s-line" class="w-4 h-4 text-slate-400" />
    <template v-for="(crumb, i) in crumbs" :key="i">
      <a v-if="crumb.to" :href="crumb.to" class="hover:text-primary transition-colors">{{ crumb.label }}</a>
      <span v-else class="text-slate-800 font-medium">{{ crumb.label }}</span>
      <Icon v-if="i < crumbs.length - 1" icon="ri:arrow-right-s-line" class="w-4 h-4 text-slate-400" />
    </template>
  </nav>
</template>

<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  crumbs: {
    type: Array,
    required: true,
  },
})
</script>
