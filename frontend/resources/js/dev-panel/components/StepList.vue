<template>
  <div class="dev-step-list">
    <div class="dev-step-list-header">
      <span class="dev-step-list-title">
        <i class="ri-list-check-2"></i>
        Steps
        <span v-if="steps.length" class="dev-step-count">({{ steps.length }})</span>
      </span>
      <button
        v-if="steps.length"
        class="dev-text-btn"
        @click="$emit('clear')"
      >
        <i class="ri-delete-bin-line"></i> Clear
      </button>
    </div>

    <div v-if="!steps.length" class="dev-step-empty">
      No steps recorded yet. Press REC to start.
    </div>

    <div v-else class="dev-step-scroll">
      <StepItem
        v-for="(step, i) in steps"
        :key="step.id"
        :step="step"
        :index="i"
        :active="activeIndex === i"
        :is-error="errorIndex === i"
      />
    </div>
  </div>
</template>

<script setup>
import StepItem from './StepItem.vue'

defineProps({
  steps: { type: Array, default: () => [] },
  activeIndex: { type: Number, default: -1 },
  errorIndex: { type: Number, default: -1 },
})

defineEmits(['clear'])
</script>

<style scoped>
.dev-step-list {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.dev-step-list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}

.dev-step-list-title {
  font-size: 12px;
  font-weight: 600;
  color: #94A3B8;
  display: flex;
  align-items: center;
  gap: 4px;
}

.dev-step-count {
  color: #64748B;
  font-weight: 400;
}

.dev-text-btn {
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 3px;
  transition: color 0.15s, background 0.15s;
}

.dev-text-btn:hover {
  color: #F87171;
  background: rgba(248, 113, 113, 0.1);
}

.dev-step-empty {
  padding: 16px 12px;
  text-align: center;
  font-size: 12px;
  color: #475569;
  font-style: italic;
}

.dev-step-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 4px 6px;
  max-height: 180px;
}
</style>
