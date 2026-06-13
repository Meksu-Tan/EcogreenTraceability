import { ref, readonly } from 'vue'

const HIGHLIGHT_ID = 'dev-panel-highlight'

/**
 * Inject the highlight overlay CSS once
 */
function ensureHighlightStyle() {
  if (document.getElementById(HIGHLIGHT_ID)) return
  const style = document.createElement('style')
  style.id = HIGHLIGHT_ID
  style.textContent = `
    .dev-panel-highlight-overlay {
      position: absolute;
      z-index: 99998;
      border: 2px solid #3B82F6;
      border-radius: 6px;
      background: rgba(59, 130, 246, 0.08);
      pointer-events: none;
      transition: all 0.3s ease;
      animation: dev-panel-pulse 1s ease-in-out infinite;
    }
    @keyframes dev-panel-pulse {
      0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.5); }
      50%       { box-shadow: 0 0 0 6px rgba(59, 130, 246, 0); }
    }
  `
  document.head.appendChild(style)
}

let overlayEl = null

function showOverlay(el) {
  removeOverlay()
  if (!el) return
  ensureHighlightStyle()

  const rect = el.getBoundingClientRect()
  overlayEl = document.createElement('div')
  overlayEl.className = 'dev-panel-highlight-overlay'
  Object.assign(overlayEl.style, {
    top:    `${rect.top + window.scrollY}px`,
    left:   `${rect.left + window.scrollX}px`,
    width:  `${rect.width}px`,
    height: `${rect.height}px`,
  })
  document.body.appendChild(overlayEl)
}

function removeOverlay() {
  overlayEl?.remove()
  overlayEl = null
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms))
}

function waitForElement(selector, timeout = 3000) {
  return new Promise((resolve, reject) => {
    const el = document.querySelector(selector)
    if (el) return resolve(el)

    const observer = new MutationObserver(() => {
      const el = document.querySelector(selector)
      if (el) { observer.disconnect(); resolve(el) }
    })
    observer.observe(document.body, { childList: true, subtree: true })

    setTimeout(() => {
      observer.disconnect()
      reject(new Error(`Element not found: ${selector}`))
    }, timeout)
  })
}

async function executeStep(step) {
  const { type, selector, value, rawValue } = step

  if (type === 'navigate') {
    // Navigation is handled externally via router — but we can do a location change
    // We rely on the caller passing a router
    return { success: true, skip: true }
  }

  if (!selector) return { success: false, error: 'No selector' }

  const el = await waitForElement(selector)
  showOverlay(el)

  el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  await sleep(300)

  switch (type) {
    case 'click':
      el.click()
      break

    case 'fill': {
      const val = rawValue || value || ''
      // Support Vuetify inputs: set value then dispatch input event
      const nativeInputValueSetter =
        Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value')?.set ||
        Object.getOwnPropertyDescriptor(window.HTMLTextAreaElement.prototype, 'value')?.set
      if (nativeInputValueSetter) {
        nativeInputValueSetter.call(el, val)
      } else {
        el.value = val
      }
      el.dispatchEvent(new Event('input', { bubbles: true }))
      el.dispatchEvent(new Event('change', { bubbles: true }))
      break
    }

    case 'select':
      el.value = value
      el.dispatchEvent(new Event('change', { bubbles: true }))
      break

    case 'check':
      if (el.checked !== value) el.click()
      break

    case 'submit':
      el.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
      break

    default:
      return { success: false, error: `Unknown step type: ${type}` }
  }

  return { success: true }
}

/**
 * useReplayer composable — replays recorded steps
 */
export function useReplayer() {
  const playing      = ref(false)
  const currentIndex = ref(-1)
  const totalSteps   = ref(0)
  const error        = ref(null)

  let cancelled = false

  async function play(steps, { delay = 800, router = null, onComplete, onError } = {}) {
    if (playing.value) return
    playing.value   = true
    currentIndex.value = 0
    totalSteps.value   = steps.length
    error.value        = null
    cancelled          = false

    for (let i = 0; i < steps.length; i++) {
      if (cancelled) break
      currentIndex.value = i

      const step = steps[i]

      // Handle navigation via router
      if (step.type === 'navigate' && router) {
        try {
          await router.push(step.value)
          await sleep(delay)
        } catch (e) {
          error.value = `Navigation failed: ${step.value}`
          onError?.(i, step, e)
          break
        }
        continue
      }

      try {
        const result = await executeStep(step)
        if (!result.success) {
          error.value = result.error
          onError?.(i, step, new Error(result.error))
          break
        }
        await sleep(delay)
      } catch (e) {
        error.value = e.message
        onError?.(i, step, e)
        break
      }
    }

    removeOverlay()
    playing.value      = false
    currentIndex.value = -1
    if (!error.value) onComplete?.()
  }

  function stop() {
    cancelled = true
    removeOverlay()
    playing.value      = false
    currentIndex.value = -1
  }

  return {
    playing:      readonly(playing),
    currentIndex: readonly(currentIndex),
    totalSteps:   readonly(totalSteps),
    error:        readonly(error),
    play,
    stop,
  }
}
