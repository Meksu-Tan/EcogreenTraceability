<template>
  <VCard rounded="lg" elevation="1">
    <VCardTitle class="d-flex flex-column sm:flex-row sm-align-center sm-justify-space-between ga-2 pa-4">
      <div class="d-flex align-center ga-2">
        <VIcon :icon="icon" size="16" :color="iconColor" />
        <h3 class="text-caption font-weight-bold text-on-surface">{{ title }}</h3>
      </div>

      <div class="d-flex flex-wrap align-center ga-2">
        <VChip
          v-if="hasFeed && feedLatest"
          size="x-small"
          color="primary"
          variant="tonal"
        >
          LATEST: <span class="ml-1 font-weight-bold">{{ feedLatest.out_qty }} MT</span>
          <span class="ml-1 text-disabled font-weight-bold">{{ feedLatest.entry_date }}</span>
        </VChip>

        <VChip
          v-if="hasRundown && rundownLatest"
          size="x-small"
          color="success"
          variant="tonal"
        >
          LATEST: <span class="ml-1 font-weight-bold">{{ rundownLatest.in_qty }} MT</span>
          <span class="ml-1 text-disabled font-weight-bold">{{ rundownLatest.entry_date }}</span>
        </VChip>

        <template v-if="hasFeed">
          <VBtn color="primary" size="small" variant="tonal" prepend-icon="ri-add-line" @click="$emit('entry-feed')">
            Entry Feed
          </VBtn>
          <VBtn color="default" size="small" variant="outlined" prepend-icon="ri-history-line" @click="$emit('view-feed-log')">
            Log Feed
          </VBtn>
        </template>

        <template v-if="hasRundown">
          <VBtn color="primary" size="small" variant="tonal" prepend-icon="ri-add-line" @click="$emit('entry-rundown')">
            Entry Rundown
          </VBtn>
          <VBtn color="primary" size="small" variant="outlined" prepend-icon="ri-swap-line" @click="$emit('view-balance')">
            Balance
          </VBtn>
          <VBtn color="default" size="small" variant="outlined" prepend-icon="ri-history-line" @click="$emit('view-rundown-log')">
            Log Rundown
          </VBtn>
        </template>
      </div>
    </VCardTitle>

    <VCardText class="pa-4">
      <div v-if="hasFeed">
        <slot name="feed-table">
          <div class="text-caption text-disabled py-3 text-center bg-neutral-50 rounded border-dashed">No feed data available</div>
        </slot>
      </div>

      <div v-if="hasRundown">
        <slot name="rundown-table">
          <div class="text-caption text-disabled py-3 text-center bg-neutral-50 rounded border-dashed">No rundown data available</div>
        </slot>
      </div>

      <slot />
    </VCardText>
  </VCard>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  icon: { type: String, default: 'ri-database-line' },
  color: { type: String, default: 'blue' },
  hasFeed: { type: Boolean, default: false },
  hasRundown: { type: Boolean, default: false },
  feedLatest: { type: Object, default: null },
  rundownLatest: { type: Object, default: null },
  feedId: { type: String, default: '' },
  rundownId: { type: String, default: '' },
  modeSelector: { type: Boolean, default: false },
  modes: { type: Array, default: () => [] },
  selectedMode: { type: String, default: '' },
})

defineEmits(['entry-feed', 'entry-rundown', 'view-balance', 'view-feed-log', 'view-rundown-log'])

const colorMap = {
  blue: 'primary', green: 'success', amber: 'warning', purple: 'info',
  emerald: 'success', teal: 'info', orange: 'warning', cyan: 'info',
  rose: 'error', pink: 'error', slate: 'medium-emphasis', indigo: 'primary',
}

const iconColor = computed(() => colorMap[props.color] || 'primary')
</script>
