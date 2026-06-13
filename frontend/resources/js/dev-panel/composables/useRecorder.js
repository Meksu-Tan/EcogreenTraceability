import { ref, readonly, onUnmounted } from 'vue'

/**
 * Generate a unique ID (no external dep)
 */
function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 9)
}

/**
 * Build the best CSS selector for an element.
 * Priority: data-testid > name > id > unique tag+class > xpath fallback
 */
function buildSelector(el) {
  if (!el || el === document || el === document.documentElement) return 'html'

  // 1. data-testid
  if (el.dataset?.testid) return `[data-testid="${el.dataset.testid}"]`

  // 2. name attribute
  if (el.name) return `${el.tagName.toLowerCase()}[name="${el.name}"]`

  // 3. id
  if (el.id) return `#${CSS.escape(el.id)}`

  // 4. tag + unique class combo
  const tag = el.tagName.toLowerCase()
  const classes = [...(el.classList || [])].filter(c => !c.startsWith('v-') || c.length < 30).slice(0, 2)
  if (classes.length) {
    const sel = `${tag}.${classes.map(c => CSS.escape(c)).join('.')}`
    if (document.querySelectorAll(sel).length === 1) return sel
  }

  // 5. nth-child fallback
  const parent = el.parentElement
  if (parent) {
    const siblings = [...parent.children].filter(c => c.tagName === el.tagName)
    const idx = siblings.indexOf(el) + 1
    const parentSel = buildSelector(parent)
    return siblings.length === 1
      ? `${parentSel} > ${tag}`
      : `${parentSel} > ${tag}:nth-of-type(${idx})`
  }

  return tag
}

/**
 * Human-readable label for a step
 */
function buildLabel(el, type, value) {
  const text = (el.textContent || '').trim().slice(0, 40)
  const tag  = el.tagName?.toLowerCase() || ''

  if (type === 'navigate') return `Navigate → ${value}`
  if (type === 'submit')   return `Submit form`
  if (type === 'check')    return `${el.checked ? 'Check' : 'Uncheck'} "${el.getAttribute('aria-label') || el.name || 'checkbox'}"`
  if (type === 'select') {
    const opt = el.options?.[el.selectedIndex]
    return `Select "${opt?.textContent?.trim() || value}" in ${el.getAttribute('aria-label') || el.name || 'dropdown'}`
  }
  if (type === 'fill') {
    const label = el.getAttribute('aria-label') || el.name || el.placeholder || tag
    return `Fill "${label}" → ${isPasswordField(el) ? '••••••' : (value || '').slice(0, 30)}`
  }
  // click
  if (text) return `Click "${text}"`
  const aria = el.getAttribute('aria-label')
  if (aria) return `Click "${aria}"`
  return `Click <${tag}>`
}

function isPasswordField(el) {
  return el.type === 'password'
}

/**
 * useRecorder composable — records user actions into steps
 */
export function useRecorder() {
  const steps     = ref([])
  const recording = ref(false)

  let cleanupFns  = []
  let inputTimer  = null
  let routerHook  = null

  function addStep(step) {
    steps.value.push({
      id: uid(),
      timestamp: Date.now(),
      screenshot: null,
      ...step,
    })
  }

  // ── Click handler ──────────────────────────────────────────
  function handleClick(e) {
    const el = e.target
    if (!el || el.closest?.('[data-dev-panel]')) return // ignore panel clicks

    // Detect table row context
    const row  = el.closest('tr')
    const table = el.closest('table')
    let rowContext = null
    if (row && table) {
      const firstCell = row.querySelector('td, th')
      rowContext = firstCell?.textContent?.trim().slice(0, 50) || null
    }

    const type = el.type === 'submit' || el.closest('button[type="submit"]') ? 'click' : 'click'

    addStep({
      type,
      selector: buildSelector(el),
      value: el.href || null,
      label: buildLabel(el, 'click', null),
      rowContext,
    })
  }

  // ── Input handler (debounced 500ms) ────────────────────────
  function handleInput(e) {
    const el = e.target
    if (!el || el.closest?.('[data-dev-panel]')) return
    if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return

    clearTimeout(inputTimer)
    inputTimer = setTimeout(() => {
      const pw = isPasswordField(el)
      addStep({
        type: 'fill',
        selector: buildSelector(el),
        value: pw ? '••••••' : el.value,
        rawValue: el.value, // kept in memory only, masked in UI & export
        label: buildLabel(el, 'fill', el.value),
      })
    }, 500)
  }

  // ── Change handler (select + checkbox) ─────────────────────
  function handleChange(e) {
    const el = e.target
    if (!el || el.closest?.('[data-dev-panel]')) return

    if (el.tagName === 'SELECT') {
      addStep({
        type: 'select',
        selector: buildSelector(el),
        value: el.value,
        label: buildLabel(el, 'select', el.value),
      })
    } else if (el.type === 'checkbox') {
      addStep({
        type: 'check',
        selector: buildSelector(el),
        value: el.checked,
        label: buildLabel(el, 'check', null),
      })
    }
  }

  // ── Form submit ────────────────────────────────────────────
  function handleSubmit(e) {
    const form = e.target
    if (!form || form.closest?.('[data-dev-panel]')) return

    addStep({
      type: 'submit',
      selector: buildSelector(form),
      value: null,
      label: 'Submit form',
    })
  }

  // ── Router navigation hook ─────────────────────────────────
  function installRouterHook(router) {
    if (!router) return
    routerHook = router.afterEach((to, from) => {
      addStep({
        type: 'navigate',
        selector: null,
        value: to.fullPath,
        label: buildLabel({}, 'navigate', to.fullPath),
        from: from.fullPath,
      })
    })
    cleanupFns.push(() => routerHook?.())
  }

  // ── Start / Stop ───────────────────────────────────────────
  function start(router) {
    if (recording.value) return
    recording.value = true
    steps.value = []

    document.addEventListener('click',   handleClick,   { capture: true, passive: true })
    document.addEventListener('input',   handleInput,   { passive: true })
    document.addEventListener('change',  handleChange,  { passive: true })
    document.addEventListener('submit',  handleSubmit,  { passive: true })

    installRouterHook(router)

    cleanupFns.push(
      () => document.removeEventListener('click',   handleClick, true),
      () => document.removeEventListener('input',   handleInput),
      () => document.removeEventListener('change',  handleChange),
      () => document.removeEventListener('submit',  handleSubmit),
    )
  }

  function stop() {
    recording.value = false
    clearTimeout(inputTimer)
    cleanupFns.forEach(fn => fn())
    cleanupFns = []
  }

  function clear() {
    steps.value = []
  }

  onUnmounted(() => stop())

  return {
    steps: readonly(steps),
    recording: readonly(recording),
    start,
    stop,
    clear,
  }
}
