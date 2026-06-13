<template>
  <div class="dev-scenario-mgr">
    <div class="dev-scenario-header">
      <span class="dev-section-title"><i class="ri-save-line"></i> Saved Scenarios</span>
      <span class="dev-scenario-count">{{ scenarios.length }}/20</span>
    </div>

    <div v-if="!scenarios.length" class="dev-scenario-empty">No saved scenarios</div>

    <div v-else class="dev-scenario-scroll">
      <div
        v-for="s in scenarios"
        :key="s.id"
        class="dev-scenario-item"
      >
        <div class="dev-scenario-info">
          <span class="dev-scenario-name">{{ s.name }}</span>
          <span class="dev-scenario-meta">{{ s.steps.length }} steps</span>
        </div>
        <div class="dev-scenario-actions">
          <button title="Play" @click.stop="$emit('play', s)"><i class="ri-play-line"></i></button>
          <button title="Rename" @click.stop="doRename(s)"><i class="ri-edit-line"></i></button>
          <button title="Duplicate" @click.stop="$emit('duplicate', s.id)"><i class="ri-file-copy-line"></i></button>
          <button title="Export JSON" @click.stop="$emit('export-json', s)"><i class="ri-download-2-line"></i></button>
          <button title="Delete" class="dev-del-btn" @click.stop="$emit('remove', s.id)"><i class="ri-delete-bin-line"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  scenarios: { type: Array, default: () => [] },
})

const emit = defineEmits(['play', 'rename', 'duplicate', 'remove', 'export-json'])

function doRename(s) {
  const name = prompt('New name:', s.name)
  if (name && name.trim()) emit('rename', s.id, name.trim())
}
</script>

<style scoped>
.dev-scenario-mgr {
  border-top: 1px solid rgba(255,255,255,0.06);
}

.dev-scenario-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
}

.dev-section-title {
  font-size: 12px;
  font-weight: 600;
  color: #94A3B8;
  display: flex;
  align-items: center;
  gap: 4px;
}

.dev-scenario-count {
  font-size: 10px;
  color: #475569;
}

.dev-scenario-empty {
  padding: 8px 12px;
  font-size: 11px;
  color: #475569;
  font-style: italic;
}

.dev-scenario-scroll {
  max-height: 120px;
  overflow-y: auto;
  padding: 0 6px 6px;
}

.dev-scenario-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 6px;
  border-radius: 6px;
  transition: background 0.15s;
}

.dev-scenario-item:hover {
  background: rgba(255,255,255,0.05);
}

.dev-scenario-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.dev-scenario-name {
  font-size: 12px;
  color: #E2E8F0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dev-scenario-meta {
  font-size: 10px;
  color: #475569;
}

.dev-scenario-actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

.dev-scenario-actions button {
  background: none;
  border: none;
  color: #64748B;
  cursor: pointer;
  padding: 2px 4px;
  border-radius: 4px;
  font-size: 12px;
  transition: color 0.15s, background 0.15s;
}

.dev-scenario-actions button:hover {
  color: #93C5FD;
  background: rgba(147,197,253,0.1);
}

.dev-del-btn:hover {
  color: #F87171 !important;
  background: rgba(248,113,113,0.1) !important;
}
</style>
