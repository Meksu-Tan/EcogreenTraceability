<template>
  <div
    class="dev-step-item"
    :class="{
      'dev-step-active': active,
      'dev-step-error': isError,
    }"
  >
    <span class="dev-step-num">{{ index + 1 }}.</span>
    <i :class="icon" class="dev-step-icon"></i>
    <span class="dev-step-label" :title="step.selector || ''">
      {{ step.label }}
    </span>
    <span v-if="step.rowContext" class="dev-step-row">
      row: {{ step.rowContext }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  step: Object,
  index: Number,
  active: Boolean,
  isError: Boolean,
})

const icon = computed(() => {
  const map = {
    click:    'ri-cursor-line',
    fill:     'ri-edit-line',
    select:   'ri-arrow-down-s-line',
    check:    'ri-checkbox-line',
    submit:   'ri-arrow-go-forward-line',
    navigate: 'ri-arrow-right-line',
  }
  return map[props.step.type] || 'ri-more-fill'
})
</script>

<style scoped>
.dev-step-item {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  padding: 5px 8px;
  border-radius: 6px;
  font-size: 12px;
  color: #CBD5E1;
  line-height: 1.4;
  transition: background 0.2s;
}

.dev-step-item:hover {
  background: rgba(255,255,255,0.05);
}

.dev-step-active {
  background: rgba(59, 130, 246, 0.18) !important;
  color: #93C5FD;
}

.dev-step-error {
  background: rgba(239, 68, 68, 0.18) !important;
  color: #FCA5A5;
}

.dev-step-num {
  color: #475569;
  font-variant-numeric: tabular-nums;
  flex-shrink: 0;
  min-width: 18px;
}

.dev-step-icon {
  flex-shrink: 0;
  width: 16px;
  text-align: center;
}

.dev-step-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-family: var(--font-mono, monospace);
  font-size: 11px;
}

.dev-step-row {
  font-size: 10px;
  color: #64748B;
  margin-left: auto;
  flex-shrink: 0;
  max-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
