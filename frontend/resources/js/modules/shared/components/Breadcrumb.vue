<template>
  <nav class="d-flex align-center gap-1 mb-4">
    <RouterLink to="/" class="text-caption text-medium-emphasis text-decoration-none hover:text-primary">Home</RouterLink>
    <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
    <template v-for="(crumb, i) in crumbs" :key="i">
      <RouterLink v-if="crumb.to" :to="crumb.to" class="text-caption text-medium-emphasis text-decoration-none hover:text-primary">
        {{ crumb.label }}
      </RouterLink>
      <span v-else class="text-caption font-weight-semibold">{{ crumb.label }}</span>
      <VIcon v-if="i < crumbs.length - 1" icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
    </template>
  </nav>
</template>

<script setup>
defineProps({
  crumbs: { type: Array, required: true },
})
</script>
