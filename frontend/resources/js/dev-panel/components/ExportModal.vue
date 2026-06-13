<template>
  <Teleport to="body">
    <div v-if="visible" class="dev-export-overlay" @click.self="$emit('close')">
      <div class="dev-export-modal">
        <div class="dev-export-header">
          <span><i class="ri-code-s-slash-line"></i> Export Script</span>
          <button class="dev-close-btn" @click="$emit('close')"><i class="ri-close-line"></i></button>
        </div>

        <div class="dev-export-tabs">
          <button
            :class="{ active: tab === 'playwright' }"
            @click="tab = 'playwright'"
          >Playwright</button>
          <button
            :class="{ active: tab === 'cypress' }"
            @click="tab = 'cypress'"
          >Cypress</button>
        </div>

        <div class="dev-export-code">
          <pre><code>{{ code }}</code></pre>
        </div>

        <div class="dev-export-footer">
          <button class="dev-copy-btn" @click="copyCode">
            <i class="ri-file-copy-line"></i>
            {{ copied ? 'Copied!' : 'Copy' }}
          </button>
          <button class="dev-download-btn" @click="downloadScript">
            <i class="ri-download-2-line"></i>
            Download
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { generatePlaywright, generateCypress } from '../utils/exportScript.js'

const props = defineProps({
  visible: Boolean,
  name: { type: String, default: 'Scenario' },
  steps: { type: Array, default: () => [] },
})

defineEmits(['close'])

const tab    = ref('playwright')
const copied = ref(false)

const code = computed(() => {
  return tab.value === 'playwright'
    ? generatePlaywright(props.name, props.steps)
    : generateCypress(props.name, props.steps)
})

async function copyCode() {
  try {
    await navigator.clipboard.writeText(code.value)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch {
    // Fallback
    const ta = document.createElement('textarea')
    ta.value = code.value
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    ta.remove()
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  }
}

function downloadScript() {
  const ext   = tab.value === 'playwright' ? 'spec.ts' : 'cy.js'
  const fname = props.name.replace(/\s+/g, '_').toLowerCase() + '.' + ext
  const blob  = new Blob([code.value], { type: 'text/plain' })
  const url   = URL.createObjectURL(blob)
  const a     = document.createElement('a')
  a.href      = url
  a.download  = fname
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.dev-export-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999999;
}

.dev-export-modal {
  background: #1E293B;
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 12px;
  width: 540px;
  max-width: 90vw;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 24px 48px rgba(0,0,0,0.5);
}

.dev-export-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  color: #E2E8F0;
  font-size: 14px;
  font-weight: 600;
}

.dev-close-btn {
  background: none;
  border: none;
  color: #94A3B8;
  cursor: pointer;
  font-size: 16px;
  padding: 2px 4px;
  border-radius: 4px;
}

.dev-close-btn:hover { color: #E2E8F0; background: rgba(255,255,255,0.08); }

.dev-export-tabs {
  display: flex;
  gap: 0;
  border-bottom: 1px solid rgba(255,255,255,0.08);
  padding: 0 16px;
}

.dev-export-tabs button {
  padding: 8px 16px;
  font-size: 12px;
  font-weight: 500;
  color: #64748B;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
  transition: all 0.15s;
}

.dev-export-tabs button:hover { color: #CBD5E1; }

.dev-export-tabs button.active {
  color: #93C5FD;
  border-bottom-color: #3B82F6;
}

.dev-export-code {
  flex: 1;
  overflow: auto;
  padding: 12px 16px;
}

.dev-export-code pre {
  margin: 0;
  font-family: var(--font-mono, monospace);
  font-size: 12px;
  line-height: 1.6;
  color: #CBD5E1;
  white-space: pre;
}

.dev-export-footer {
  display: flex;
  gap: 8px;
  padding: 10px 16px;
  border-top: 1px solid rgba(255,255,255,0.08);
  justify-content: flex-end;
}

.dev-copy-btn, .dev-download-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.dev-copy-btn {
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  color: #CBD5E1;
}

.dev-copy-btn:hover { background: rgba(255,255,255,0.12); color: #E2E8F0; }

.dev-download-btn {
  background: #3B82F6;
  border: none;
  color: #fff;
}

.dev-download-btn:hover { background: #2563EB; }
</style>
