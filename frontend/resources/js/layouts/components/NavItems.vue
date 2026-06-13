<template>
  <VList density="compact" nav class="py-2">
    <template v-for="group in sidebarMenu" :key="group.id">
      <!-- Group header -->
      <VListSubheader v-if="!rail" class="text-overline text-medium-emphasis mt-2">
        {{ group.label }}
      </VListSubheader>
      <VDivider v-else class="my-1" />

      <!-- Nav items -->
      <VListItem
        v-for="item in group.items"
        :key="item.path"
        :to="item.path"
        :prepend-icon="toRiClass(item.icon)"
        :title="rail ? '' : item.label"
        color="primary"
        rounded="lg"
        active-class="active-nav-item"
        class="mb-1"
      >
        <VTooltip v-if="rail" activator="parent" location="end">
          {{ item.label }}
        </VTooltip>
      </VListItem>
    </template>
  </VList>
</template>

<script setup>
import { sidebarMenu } from '@/config/sidebar'

defineProps({
  rail: { type: Boolean, default: false },
})

function toRiClass(icon) {
  return icon.replace(':', '-')
}
</script>

<style scoped>
:deep(.active-nav-item) {
  font-weight: 600;
}
</style>
