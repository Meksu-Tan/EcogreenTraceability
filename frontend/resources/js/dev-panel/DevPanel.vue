<template>
  <Teleport to="body">
    <!-- Minimized FAB -->
    <button
      v-if="minimized"
      ref="fabRef"
      class="dev-fab"
      :class="{ 'dev-fab-rec': recording }"
      @click="minimized = false"
      title="Open Dev Panel"
    >
      <i class="ri-tools-line"></i>
      <span v-if="recording" class="dev-fab-badge">{{ steps.length }}</span>
    </button>

    <!-- Expanded Panel -->
    <div
      v-else
      ref="panelRef"
      data-dev-panel
      class="dev-panel"
      :style="panelStyle"
    >
      <PanelHeader
        :recording="recording"
        :playing="playing"
        :step-count="steps.length"
        :current-index="currentIndex"
        :total-steps="totalSteps"
        @minimize="minimized = true"
        @close="visible = false"
        @drag-start="onDragStart"
      />

      <!-- Toolbar -->
      <div class="dev-toolbar">
        <button
          class="dev-tool-btn"
          :class="{ 'dev-tool-btn-active': recording, 'dev-tool-btn-rec': recording }"
          @click="toggleRecord"
        >
          <i :class="recording ? 'ri-stop-circle-fill' : 'ri-record-circle-line'"></i>
          {{ recording ? 'STOP' : 'REC' }}
        </button>

        <button
          class="dev-tool-btn dev-tool-btn-play"
          :disabled="!steps.length || playing"
          @click="replaySteps"
        >
          <i class="ri-play-circle-line"></i>
          PLAY
        </button>

        <button
          class="dev-tool-btn"
          :disabled="!steps.length"
          @click="openExport(null)"
        >
          <i class="ri-code-s-slash-line"></i>
          Export
        </button>

        <button
          class="dev-tool-btn"
          :disabled="!steps.length"
          @click="promptSave"
        >
          <i class="ri-save-line"></i>
          Save
        </button>
      </div>

      <!-- Replay progress bar -->
      <div v-if="playing" class="dev-progress">
        <div
          class="dev-progress-bar"
          :style="{ width: progressPct + '%' }"
        ></div>
      </div>

      <!-- Error toast -->
      <div v-if="replayError" class="dev-error-toast">
        <i class="ri-error-warning-line"></i>
        {{ replayError }}
        <button class="dev-error-dismiss" @click="clearReplayError">×</button>
      </div>

      <!-- Quick Fill Section -->
      <div class="dev-qf-section">
        <div class="dev-qf-header">
          <i class="ri-magic-line"></i>
          <span>Quick Fill</span>
          <span v-if="qfRunningKey" class="dev-qf-running-badge">
            <i class="ri-loader-4-line dev-spin"></i> running…
          </span>
          <button
            v-if="qfRunningKey"
            class="dev-qf-cancel-btn"
            @click="handleCancelQuickFill"
          >
            <i class="ri-stop-circle-line"></i>
            Cancel
          </button>
        </div>

        <!-- Success / Error feedback -->
        <div v-if="qfFeedback" class="dev-qf-feedback" :class="qfFeedback.type">
          <i :class="qfFeedback.type === 'success' ? 'ri-check-line' : 'ri-alert-line'"></i>
          {{ qfFeedback.msg }}
        </div>

        <div class="dev-qf-list">
          <button
            v-for="p in quickFillPresets"
            :key="p.key"
            class="dev-qf-btn"
            :class="{ 'dev-qf-btn-running': qfRunningKey === p.key }"
            :style="{
              '--qf-color': p.color,
              borderColor: p.color + '44',
            }"
            :disabled="!!qfRunningKey"
            :title="p.description"
            @click="handleQuickFill(p.key)"
          >
            <span class="dev-qf-icon-wrap">
              <i v-if="qfRunningKey !== p.key" :class="p.icon"></i>
              <i v-else class="ri-loader-4-line dev-spin"></i>
            </span>
            <span class="dev-qf-label">{{ p.label }}</span>
            <i class="ri-arrow-right-line dev-qf-arrow"></i>
          </button>
        </div>
      </div>

      <!-- Steps list -->
      <StepList
        :steps="steps"
        :active-index="currentIndex"
        :error-index="errorIndex"
        @clear="clearSteps"
      />

      <!-- Scenario Manager -->
      <ScenarioManager
        :scenarios="scenarios"
        @play="playScenario"
        @rename="renameScenario"
        @duplicate="duplicateScenario"
        @remove="removeScenario"
        @export-json="exportJSON"
      />

      <!-- Export Modal -->
      <ExportModal
        :visible="showExport"
        :name="exportName"
        :steps="exportSteps"
        @close="showExport = false"
      />
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useRecorder }    from './composables/useRecorder.js'
import { useReplayer }    from './composables/useReplayer.js'
import { useScenarios }   from './composables/useScenarios.js'
import { useQuickFill }   from './composables/useQuickFill.js'
import PanelHeader        from './components/PanelHeader.vue'
import StepList           from './components/StepList.vue'
import ScenarioManager    from './components/ScenarioManager.vue'
import ExportModal        from './components/ExportModal.vue'

const router = useRouter()

// ── Composables ───────────────────────────────────────────────
const { steps, recording, start: startRecord, stop: stopRecord, clear: clearRec } = useRecorder()
const {
  playing, currentIndex, totalSteps,
  error: replayError,
  play: replayPlay,
} = useReplayer()
const {
  scenarios,
  save: saveScenario,
  remove: removeScenario,
  rename: renameScenario,
  duplicate: duplicateScenario,
  exportJSON,
} = useScenarios()
const {
  presets: quickFillPresets,
  running: qfRunning,
  runningKey: qfRunningKey,
  runPreset: runQuickFillPreset,
  cancelPreset: cancelQuickFill,
} = useQuickFill()

// ── UI state ──────────────────────────────────────────────────
const visible     = ref(true)
const minimized   = ref(true)
const showExport  = ref(false)
const exportName  = ref('Scenario')
const exportSteps = ref([])
const errorIndex  = ref(-1)

// Quick Fill feedback
const qfFeedback = ref(null)
let qfFeedbackTimer = null
function setQfFeedback(type, msg) {
  clearTimeout(qfFeedbackTimer)
  qfFeedback.value = { type, msg }
  qfFeedbackTimer = setTimeout(() => { qfFeedback.value = null }, 3500)
}

// Drag state
const panelRef  = ref(null)
const fabRef    = ref(null)
const posX      = ref(0)
const posY      = ref(0)
const positioned = ref(false)
let dragOffset = { x: 0, y: 0 }
let isDragging = false

const panelStyle = computed(() => {
  if (!positioned.value) {
    return { position: 'fixed', bottom: '20px', right: '20px', zIndex: 99999 }
  }
  return {
    position: 'fixed',
    top:  `${posY.value}px`,
    left: `${posX.value}px`,
    zIndex: 99999,
  }
})

const progressPct = computed(() => {
  if (!totalSteps.value) return 0
  return ((currentIndex.value + 1) / totalSteps.value) * 100
})

// ── Actions ───────────────────────────────────────────────────
function toggleRecord() {
  if (recording.value) stopRecord()
  else startRecord(router)
}

function replaySteps() {
  errorIndex.value = -1
  replayPlay([...steps.value], {
    delay: 800,
    router,
    onComplete() { showToast('Scenario completed!') },
    onError(i)   { errorIndex.value = i },
  })
}

function playScenario(scenario) {
  errorIndex.value = -1
  minimized.value  = false
  replayPlay([...scenario.steps], {
    delay: 800,
    router,
    onComplete() { showToast(`"${scenario.name}" completed!`) },
    onError(i)   { errorIndex.value = i },
  })
}

function clearSteps() {
  clearRec()
  errorIndex.value = -1
}

function clearReplayError() {
  errorIndex.value = -1
}

function promptSave() {
  if (!steps.value.length) return
  const name = prompt('Scenario name:', `Scenario ${scenarios.value.length + 1}`)
  if (!name?.trim()) return
  try {
    saveScenario(name.trim(), '', [...steps.value])
    showToast(`Saved "${name.trim()}"`)
  } catch (e) {
    showToast(e.message, 'error')
  }
}

async function handleQuickFill(key) {
  try {
    await runQuickFillPreset(key)
    const label = quickFillPresets.find(p => p.key === key)?.label || key
    setQfFeedback('success', `${label} selesai`)
  } catch (e) {
    setQfFeedback('error', e.message)
  }
}

function handleCancelQuickFill() {
  cancelQuickFill()
  setQfFeedback('error', 'Dibatalkan')
}

// ── Drag ──────────────────────────────────────────────────────
function onDragStart(e) {
  if (!positioned.value && panelRef.value) {
    const rect = panelRef.value.getBoundingClientRect()
    posX.value = rect.left
    posY.value = rect.top
    positioned.value = true
  }
  isDragging = true
  dragOffset.x = e.clientX - posX.value
  dragOffset.y = e.clientY - posY.value
  document.addEventListener('mousemove', onDragMove)
  document.addEventListener('mouseup',   onDragEnd)
}

function onDragMove(e) {
  if (!isDragging) return
  posX.value = Math.max(0, Math.min(window.innerWidth  - 320, e.clientX - dragOffset.x))
  posY.value = Math.max(0, Math.min(window.innerHeight - 60,  e.clientY - dragOffset.y))
}

function onDragEnd() {
  isDragging = false
  document.removeEventListener('mousemove', onDragMove)
  document.removeEventListener('mouseup',   onDragEnd)
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const el = document.createElement('div')
  el.style.cssText = `
    position:fixed;bottom:24px;right:24px;z-index:999999;
    padding:8px 16px;border-radius:8px;font-size:13px;font-weight:500;
    background:${type === 'error' ? '#7F1D1D' : '#14532D'};
    color:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.4);
    transition:opacity 0.3s;font-family:var(--font-body,sans-serif);
  `
  el.textContent = msg
  document.body.appendChild(el)
  setTimeout(() => { el.style.opacity = '0' }, 2500)
  setTimeout(() => el.remove(), 2800)
}

// ── Export ────────────────────────────────────────────────────
function openExport(scenario) {
  exportName.value  = scenario?.name || 'Recorded Scenario'
  exportSteps.value = scenario?.steps || [...steps.value]
  showExport.value  = true
}

// ── Keyboard shortcut ─────────────────────────────────────────
function onKeydown(e) {
  if (e.ctrlKey && e.shiftKey && e.key === 'D') {
    e.preventDefault()
    visible.value = !visible.value
  }
  if (e.ctrlKey && e.shiftKey && e.key === 'R') {
    e.preventDefault()
    if (visible.value && !minimized.value) toggleRecord()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
/* ── FAB (minimized) ──────────────────────────────────────── */
.dev-fab {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: none;
  background: #1E293B;
  color: #94A3B8;
  font-size: 20px;
  cursor: pointer;
  z-index: 99999;
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s, background 0.15s;
}

.dev-fab:hover {
  transform: scale(1.08);
  background: #334155;
  color: #E2E8F0;
}

.dev-fab-rec {
  background: #7F1D1D;
  color: #FCA5A5;
  animation: dev-fab-pulse 1.5s ease-in-out infinite;
}

@keyframes dev-fab-pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
  50%       { box-shadow: 0 0 0 8px rgba(239,68,68,0); }
}

.dev-fab-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #EF4444;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

/* ── Main Panel ───────────────────────────────────────────── */
.dev-panel {
  width: 320px;
  max-height: 560px;
  background: #0F172A;
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0,0,0,0.5);
  overflow: hidden;
  font-family: var(--font-body, sans-serif);
}

/* ── Toolbar ──────────────────────────────────────────────── */
.dev-toolbar {
  display: flex;
  gap: 4px;
  padding: 8px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  background: rgba(255,255,255,0.02);
}

.dev-tool-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 5px 6px;
  border-radius: 7px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.03em;
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  color: #94A3B8;
  cursor: pointer;
  transition: all 0.15s;
}

.dev-tool-btn:hover:not(:disabled) {
  background: rgba(255,255,255,0.08);
  color: #E2E8F0;
}

.dev-tool-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.dev-tool-btn-active {
  background: rgba(239,68,68,0.2) !important;
  border-color: rgba(239,68,68,0.4) !important;
  color: #FCA5A5 !important;
}

.dev-tool-btn-rec {
  animation: dev-btn-blink 1s ease-in-out infinite;
}

@keyframes dev-btn-blink {
  0%, 100% { background: rgba(239,68,68,0.2); }
  50%       { background: rgba(239,68,68,0.35); }
}

.dev-tool-btn-play {
  color: #86EFAC;
  border-color: rgba(134,239,172,0.3);
}

.dev-tool-btn-play:hover:not(:disabled) {
  background: rgba(134,239,172,0.12);
  color: #BBF7D0;
}

/* ── Progress bar ─────────────────────────────────────────── */
.dev-progress {
  height: 3px;
  background: rgba(255,255,255,0.06);
}

.dev-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3B82F6, #818CF8);
  transition: width 0.3s ease;
  border-radius: 0 2px 2px 0;
}

/* ── Error toast (replay) ─────────────────────────────────── */
.dev-error-toast {
  margin: 6px 10px;
  padding: 6px 10px;
  border-radius: 7px;
  background: rgba(239,68,68,0.15);
  border: 1px solid rgba(239,68,68,0.3);
  color: #FCA5A5;
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.dev-error-dismiss {
  margin-left: auto;
  background: none;
  border: none;
  color: #FCA5A5;
  cursor: pointer;
  font-size: 14px;
  padding: 0 4px;
}

/* ── Quick Fill Section ───────────────────────────────────── */
.dev-qf-section {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 6px 0 4px;
}

.dev-qf-header {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 0 12px 5px;
  font-size: 11px;
  font-weight: 700;
  color: #64748B;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

.dev-qf-running-badge {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 10px;
  color: #93C5FD;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
}

.dev-qf-cancel-btn {
  display: flex;
  align-items: center;
  gap: 3px;
  padding: 2px 8px;
  border-radius: 5px;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.04em;
  border: 1px solid rgba(239,68,68,0.4);
  background: rgba(239,68,68,0.12);
  color: #FCA5A5;
  cursor: pointer;
  transition: all 0.15s;
  text-transform: uppercase;
}

.dev-qf-cancel-btn:hover {
  background: rgba(239,68,68,0.22);
  border-color: rgba(239,68,68,0.65);
}

.dev-qf-feedback {
  margin: 0 10px 5px;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11px;
  display: flex;
  align-items: center;
  gap: 5px;
  animation: dev-qf-fadein 0.2s ease;
}

.dev-qf-feedback.success {
  background: rgba(134,239,172,0.12);
  border: 1px solid rgba(134,239,172,0.25);
  color: #86EFAC;
}

.dev-qf-feedback.error {
  background: rgba(239,68,68,0.12);
  border: 1px solid rgba(239,68,68,0.25);
  color: #FCA5A5;
}

@keyframes dev-qf-fadein {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}

.dev-qf-list {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 0 8px;
}

.dev-qf-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 7px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  border: 1px solid;
  background: rgba(255,255,255,0.025);
  color: var(--qf-color, #CBD5E1);
  cursor: pointer;
  transition: all 0.15s;
  text-align: left;
}

.dev-qf-btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--qf-color) 10%, transparent);
  border-color: var(--qf-color);
  transform: translateX(2px);
}

.dev-qf-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.dev-qf-btn-running {
  animation: dev-qf-pulse 0.8s ease-in-out infinite;
}

@keyframes dev-qf-pulse {
  0%, 100% { opacity: 0.6; }
  50%       { opacity: 1; }
}

.dev-qf-icon-wrap {
  width: 18px;
  text-align: center;
  flex-shrink: 0;
  font-size: 14px;
}

.dev-qf-label {
  flex: 1;
}

.dev-qf-arrow {
  font-size: 12px;
  opacity: 0;
  transition: opacity 0.15s, transform 0.15s;
}

.dev-qf-btn:hover:not(:disabled) .dev-qf-arrow {
  opacity: 0.6;
  transform: translateX(2px);
}

/* ── Spin animation ───────────────────────────────────────── */
.dev-spin {
  display: inline-block;
  animation: dev-spin 0.8s linear infinite;
}

@keyframes dev-spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
</style>
