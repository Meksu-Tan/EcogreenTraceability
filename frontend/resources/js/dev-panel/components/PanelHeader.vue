<template>
  <div
    class="dev-panel-header"
    @mousedown="$emit('drag-start', $event)"
  >
    <span class="dev-panel-title">
      <i class="ri-tools-line"></i>
      Dev Panel
    </span>
    <div class="dev-panel-header-actions">
      <button
        v-if="recording"
        class="dev-badge dev-badge-rec"
        title="Recording…"
      >
        REC {{ stepCount }}
      </button>
      <button
        v-if="playing"
        class="dev-badge dev-badge-play"
        title="Replaying…"
      >
        ▶ {{ currentIndex + 1 }}/{{ totalSteps }}
      </button>
      <button class="dev-icon-btn" title="Minimize" @click.stop="$emit('minimize')">
        <i class="ri-subtract-line"></i>
      </button>
      <button class="dev-icon-btn" title="Close" @click.stop="$emit('close')">
        <i class="ri-close-line"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  recording: Boolean,
  playing: Boolean,
  stepCount: { type: Number, default: 0 },
  currentIndex: { type: Number, default: -1 },
  totalSteps: { type: Number, default: 0 },
})

defineEmits(['minimize', 'close', 'drag-start'])
</script>

<style scoped>
.dev-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  cursor: move;
  user-select: none;
  background: rgba(255,255,255,0.04);
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.dev-panel-title {
  font-family: var(--font-heading, sans-serif);
  font-size: 13px;
  font-weight: 600;
  color: #E2E8F0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.dev-panel-header-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.dev-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 9999px;
  border: none;
  cursor: default;
  letter-spacing: 0.05em;
}

.dev-badge-rec {
  background: #EF4444;
  color: #fff;
  animation: dev-blink 1s ease-in-out infinite;
}

.dev-badge-play {
  background: #3B82F6;
  color: #fff;
}

@keyframes dev-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.dev-icon-btn {
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 14px;
  line-height: 1;
  transition: color 0.15s, background 0.15s;
}

.dev-icon-btn:hover {
  color: #E2E8F0;
  background: rgba(255,255,255,0.08);
}
</style>
