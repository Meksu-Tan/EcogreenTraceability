import { ref } from 'vue'

// ── Cancel mechanism ───────────────────────────────────────────
let _abortController = null

function checkCancelled() {
  if (_abortController?.signal.aborted) throw new Error('CANCELLED')
}

// ── Helpers ────────────────────────────────────────────────────

function sleep(ms) {
  return new Promise((resolve, reject) => {
    if (_abortController?.signal.aborted) return reject(new Error('CANCELLED'))
    const id = setTimeout(resolve, ms)
    if (_abortController) {
      _abortController.signal.addEventListener('abort', () => {
        clearTimeout(id)
        reject(new Error('CANCELLED'))
      }, { once: true })
    }
  })
}

async function waitFor(conditionFn, { timeout = 6000, interval = 80, label = '' } = {}) {
  const start = Date.now()
  while (Date.now() - start < timeout) {
    checkCancelled()
    const result = conditionFn()
    if (result) return result
    await sleep(interval)
  }
  throw new Error(`Timeout: ${label}`)
}

function setNativeValue(el, value) {
  const proto = el.tagName === 'TEXTAREA'
    ? window.HTMLTextAreaElement.prototype
    : window.HTMLInputElement.prototype
  const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set
  if (setter) setter.call(el, value)
  else el.value = value
}

// ── Dialog helpers ────────────────────────────────────────────

function getAllActiveDialogCards() {
  return [
    ...document.querySelectorAll(
      '.v-dialog.v-overlay--active > .v-overlay__content > .v-card'
    ),
  ]
}

function getTopDialogCard() {
  const cards = getAllActiveDialogCards()
  return cards[cards.length - 1] || null
}

async function waitForDialog(timeout = 10000) {
  await waitFor(
    () => getAllActiveDialogCards().length > 0,
    { timeout, label: 'dialog card to appear' }
  )
  await sleep(200)
  await waitFor(() => {
    const card = getTopDialogCard()
    if (!card) return false
    return !card.querySelector('.v-progress-circular')
  }, { timeout: 10000, label: 'dialog loading to finish' })
  await sleep(300)
  return getTopDialogCard()
}

async function waitForNewDialog(previousCount, timeout = 6000) {
  await waitFor(
    () => getAllActiveDialogCards().length > previousCount,
    { timeout, label: 'new sub-dialog to open' }
  )
  await sleep(300)
  return getTopDialogCard()
}

// ── VMenu helpers ─────────────────────────────────────────────

function getOpenMenuItems() {
  let items = [...document.querySelectorAll('.v-menu.v-overlay--active .v-list-item')]
  if (items.length > 0) return items
  items = [...document.querySelectorAll('.v-overlay-container .v-list-item')]
    .filter(it => !it.closest('.v-dialog'))
  return items
}

function isMenuOpen() {
  return getOpenMenuItems().length > 0
}

async function waitForMenuItems(label = 'dropdown', timeout = 4000) {
  await waitFor(() => isMenuOpen(), { timeout, label: `"${label}" menu items` })
  await sleep(60)
  return getOpenMenuItems()
}

function closeOpenMenu() {
  const menuOverlay = document.querySelector('.v-menu.v-overlay--active .v-overlay__scrim')
  if (menuOverlay) {
    menuOverlay.dispatchEvent(new MouseEvent('click', { bubbles: true }))
  } else if (isMenuOpen()) {
    document.dispatchEvent(new KeyboardEvent('keydown', {
      key: 'Escape', code: 'Escape', keyCode: 27, bubbles: true,
    }))
  }
}

// ── Full pointer + mouse event simulation ─────────────────────

function simulateClick(el) {
  if (!el) return
  const rect = el.getBoundingClientRect()
  const cx   = rect.left + rect.width  / 2
  const cy   = rect.top  + rect.height / 2

  const base  = { bubbles: true, cancelable: true, composed: true, clientX: cx, clientY: cy, screenX: cx, screenY: cy, view: window }
  const pBase = { ...base, pointerId: 1, pointerType: 'mouse', isPrimary: true }

  el.dispatchEvent(new PointerEvent('pointerover',  pBase))
  el.dispatchEvent(new PointerEvent('pointerenter', { ...pBase, bubbles: false }))
  el.dispatchEvent(new MouseEvent  ('mouseover',    base))
  el.dispatchEvent(new MouseEvent  ('mouseenter',   { ...base, bubbles: false }))
  el.dispatchEvent(new PointerEvent('pointermove',  pBase))
  el.dispatchEvent(new MouseEvent  ('mousemove',    base))
  el.dispatchEvent(new PointerEvent('pointerdown',  { ...pBase, button: 0, buttons: 1 }))
  el.dispatchEvent(new MouseEvent  ('mousedown',    { ...base,  button: 0, buttons: 1 }))
  el.dispatchEvent(new PointerEvent('pointerup',    { ...pBase, button: 0, buttons: 0 }))
  el.dispatchEvent(new MouseEvent  ('mouseup',      { ...base,  button: 0, buttons: 0 }))
  el.dispatchEvent(new MouseEvent  ('click',        { ...base,  button: 0 }))
}

// ── Vue 3 internal — directly open VSelect ────────────────────

function openViaVue(field) {
  let node = field
  while (node && node !== document.body) {
    const comp = node.__vueParentComponent
    if (comp) {
      const setup = comp.setupState
      if (setup && 'menu' in setup && setup.menu && typeof setup.menu === 'object') {
        try {
          if ('isActive' in setup.menu) {
            setup.menu.isActive.value = true
            return true
          }
          if (setup.menu.value !== undefined) {
            const menuComp = setup.menu.value
            if (menuComp?.isActive) { menuComp.isActive.value = true; return true }
          }
        } catch {
          // ignore vue internals error
        }
      }
    }
    node = node.parentElement
  }
  return false
}

// ── Find VSelect field by label ───────────────────────────────

function findVSelectByLabel(root, labelText) {
  const needle = labelText.toLowerCase().trim()

  let exactAny      = null
  let exactSelect   = null
  let partialAny    = null
  let partialSelect = null

  for (const field of root.querySelectorAll('.v-field')) {
    const lbl      = field.querySelector('.v-label')?.textContent?.trim().toLowerCase() ?? ''
    const isSelect = !!field.querySelector('.v-select__menu-icon, .v-field__append-inner .v-icon')

    if (lbl === needle) {
      if (isSelect && !exactSelect) exactSelect = field
      if (!exactAny) exactAny = field
    } else if (lbl.includes(needle)) {
      if (isSelect && !partialSelect) partialSelect = field
      if (!partialAny) partialAny = field
    }
  }

  return exactSelect || exactAny || partialSelect || partialAny || null
}

// ── Open VSelect: 5-strategy cascade ─────────────────────────

async function openVSelect(field) {
  if (isMenuOpen()) {
    closeOpenMenu()
    await sleep(100)
  }
  if (isMenuOpen()) return

  if (openViaVue(field)) {
    await sleep(150)
    if (isMenuOpen()) return
  }

  const fieldInput = field.querySelector('.v-field__input') || field
  simulateClick(fieldInput)
  await sleep(200)
  if (isMenuOpen()) return

  const icon = field.querySelector('.v-select__menu-icon')
             || field.querySelector('.v-field__append-inner .v-icon')
             || field.querySelector('.v-field__append-inner')
  if (icon) {
    simulateClick(icon)
    await sleep(200)
    if (isMenuOpen()) return
  }

  const input = field.querySelector('input')
  if (input) {
    input.focus()
    await sleep(80)
    input.dispatchEvent(new KeyboardEvent('keydown', {
      key: 'ArrowDown', code: 'ArrowDown', keyCode: 40,
      bubbles: true, cancelable: true, composed: true,
    }))
    await sleep(200)
    if (isMenuOpen()) return
  }

  simulateClick(field)
  await sleep(200)
}

// ── pickSelect ────────────────────────────────────────────────

async function pickSelect(root, labelText, mode = 'first', match = null) {
  const field = findVSelectByLabel(root, labelText)
  if (!field) throw new Error(`VSelect tidak ditemukan: "${labelText}"`)

  let items = []
  for (let attempt = 1; attempt <= 3; attempt++) {
    checkCancelled()
    await openVSelect(field)
    try {
      items = await waitForMenuItems(labelText, 3000)
      break
    } catch {
      if (attempt === 3) throw new Error(`Dropdown "${labelText}" tidak terbuka (3 attempts)`)
      await sleep(400)
    }
  }

  const selectable = items.filter(
    it => !it.classList.contains('v-list-subheader') && !it.classList.contains('v-divider')
  )

  let picked = null
  if (mode === 'first') {
    picked = selectable[0]
  } else if (mode === 'last') {
    picked = selectable[selectable.length - 1]
  } else if (mode === 'text') {
    const needle = String(match).toLowerCase()
    picked = selectable.find(it => it.textContent?.trim().toLowerCase().includes(needle))
          ?? selectable[0]
  }

  if (!picked) {
    closeOpenMenu()
    throw new Error(`Option tidak ditemukan di "${labelText}"`)
  }

  simulateClick(picked)
  await sleep(300)
}

// ── Fill VTextField by label ──────────────────────────────────

async function fillField(root, labelText, value) {
  const needle = labelText.toLowerCase()
  let input = null

  for (const field of root.querySelectorAll('.v-field')) {
    const lbl = field.querySelector('.v-label')?.textContent?.trim().toLowerCase() ?? ''
    if (lbl.includes(needle)) {
      input = field.querySelector('input, textarea')
      break
    }
  }

  if (!input) {
    for (const inp of root.querySelectorAll('input, textarea')) {
      const ph   = (inp.placeholder || '').toLowerCase()
      const aria = (inp.getAttribute('aria-label') || '').toLowerCase()
      if (ph.includes(needle) || aria.includes(needle)) { input = inp; break }
    }
  }

  if (!input) throw new Error(`Field tidak ditemukan: "${labelText}"`)

  input.focus()
  await sleep(40)
  setNativeValue(input, value)
  input.dispatchEvent(new Event('input',  { bubbles: true }))
  input.dispatchEvent(new Event('change', { bubbles: true }))
  input.blur()
  await sleep(80)
}

// ── Click VBtn by text ────────────────────────────────────────

async function clickBtn(root, btnText, delay = 300) {
  const needle = btnText.toLowerCase()
  for (const btn of root.querySelectorAll('.v-btn, button')) {
    const txt = btn.textContent?.trim().toLowerCase() ?? ''
    if (txt.includes(needle) && !btn.disabled && !btn.hasAttribute('disabled')) {
      simulateClick(btn)
      await sleep(delay)
      return
    }
  }
  throw new Error(`Tombol tidak ditemukan: "${btnText}"`)
}

// ── Select all sub-sloc VSelect dropdown ─────────────────────
//
// afterLabel: jika diisi, cari field sub-sloc pertama yang muncul
// setelah field dengan label tersebut dalam DOM order.
// Ini menghindari ambiguitas ketika ada dua "Sub-Sloc" dalam satu dialog.

async function selectAllSubSlocDropdown(root, labelText = 'sub-sloc', afterLabel = null) {
  let field = null

  if (afterLabel) {
    const allFields = [...root.querySelectorAll('.v-field')]
    const anchorIdx = allFields.findIndex(f =>
      f.querySelector('.v-label')?.textContent?.trim().toLowerCase() === afterLabel.toLowerCase()
    )
    if (anchorIdx >= 0) {
      for (let i = anchorIdx + 1; i < allFields.length; i++) {
        const lbl = allFields[i].querySelector('.v-label')?.textContent?.trim().toLowerCase()
        if (lbl === labelText.toLowerCase()) { field = allFields[i]; break }
      }
    }
  } else {
    field = findVSelectByLabel(root, labelText)
  }

  if (!field) return

  await sleep(300)
  checkCancelled()

  let realItems = []
  for (let attempt = 1; attempt <= 5; attempt++) {
    checkCancelled()
    await openVSelect(field)
    try {
      await waitForMenuItems(labelText, 3000)
    } catch {
      if (isMenuOpen()) closeOpenMenu()
      await sleep(800)
      continue
    }

    // Hanya ambil item selectable (ada aria-selected), bukan placeholder "No data"
    realItems = getOpenMenuItems().filter(
      it => !it.classList.contains('v-list-subheader') &&
            !it.classList.contains('v-divider') &&
            it.hasAttribute('aria-selected')
    )

    if (realItems.length > 0) break

    closeOpenMenu()
    await sleep(1000)
  }

  if (realItems.length === 0) {
    if (isMenuOpen()) closeOpenMenu()
    return
  }

  for (const item of realItems) {
    checkCancelled()
    if (item.getAttribute('aria-selected') !== 'true') {
      simulateClick(item)
      await sleep(120)
    }
  }

  closeOpenMenu()
  await sleep(200)
}

// ── Presets ───────────────────────────────────────────────────

export const PRESETS = [
  {
    key:         'login',
    label:       'Login Fill',
    icon:        'ri-login-box-line',
    color:       '#86EFAC',
    description: 'Isi kredensial admin di halaman login',
    async run() {
      const root = document.querySelector('#app')
      if (!root) throw new Error('App root tidak ditemukan')
      await fillField(root, 'email', 'admin@eods.local')
      await fillField(root, 'password', 'password123')
    },
  },

  {
    key:         'rmEntryFill',
    label:       'RM Entry Fill',
    icon:        'ri-file-add-line',
    color:       '#93C5FD',
    description: 'Buka New RM Entry & isi semua field + supplier',
    async run() {
      const root = document.querySelector('#app')
      if (!root) throw new Error('App root tidak ditemukan')

      await clickBtn(root, 'new rm entry', 800)
      const dialog = await waitForDialog(12000)

      await pickSelect(dialog, 'sloc', 'text', 'eob 1')
      await selectAllSubSlocDropdown(dialog)

      await fillField(dialog, 'material doc', 'Testing')
      await fillField(dialog, 'purchase order', '00')

      await pickSelect(dialog, 'material', 'text', 'stearin')
      await sleep(200)

      const dialogCountBefore = getAllActiveDialogCards().length
      await clickBtn(dialog, 'supplier', 500)
      const supplierDialog = await waitForNewDialog(dialogCountBefore, 6000)

      await pickSelect(supplierDialog, 'supplier', 'first')
      await sleep(700)
      await fillField(supplierDialog, 'qty', '10')
      await clickBtn(supplierDialog, 'add', 500)

      await waitFor(
        () => getAllActiveDialogCards().length <= dialogCountBefore,
        { timeout: 5000, label: 'supplier dialog to close' }
      )
      await sleep(300)
      const mainDialog = getTopDialogCard() || dialog
      await clickBtn(mainDialog, 'save rm entry', 300)
    },
  },

  {
    key:         'transferFeedFill',
    label:       'Transfer Feed Fill',
    icon:        'ri-transfer-line',
    color:       '#C4B5FD',
    description: 'Buka Transfer to Feed Tank & isi semua field',
    async run() {
      const root = document.querySelector('#app')
      if (!root) throw new Error('App root tidak ditemukan')

      await clickBtn(root, 'transfer to feed tank', 800)
      const dialog = await waitForDialog(12000)

      await pickSelect(dialog, 'source sloc', 'text', 'storage eob 1')
        .catch(() => pickSelect(dialog, 'source sloc', 'first'))

      await sleep(1800)
      await selectAllSubSlocDropdown(dialog, 'sub-sloc', 'source sloc')
      await sleep(300)

      const destField = findVSelectByLabel(dialog, 'destination sloc')
      const destVal   = destField?.querySelector('.v-field__input')?.textContent?.trim() ?? ''
      if (!destVal) {
        await pickSelect(dialog, 'destination sloc', 'text', 'feed eob 1')
          .catch(() => pickSelect(dialog, 'destination sloc', 'first'))
        await sleep(1500)
      } else {
        await sleep(200)
      }

      await selectAllSubSlocDropdown(dialog, 'sub-sloc', 'destination sloc')
      await sleep(300)

      const dialogCountBefore = getAllActiveDialogCards().length
      await clickBtn(dialog, 'material', 500)
      const materialDialog = await waitForNewDialog(dialogCountBefore, 6000)

      await pickSelect(materialDialog, 'material', 'text', 'stearin')
      await sleep(200)
      await fillField(materialDialog, 'qty', '5')
      await clickBtn(materialDialog, 'add', 500)

      await waitFor(
        () => getAllActiveDialogCards().length <= dialogCountBefore,
        { timeout: 5000, label: 'material dialog to close' }
      )
      await sleep(300)
      const mainDialog = getTopDialogCard() || dialog
      await clickBtn(mainDialog, 'confirm transfer', 300)
    },
  },

  {
    key:         'transferFeedFill10',
    label:       'Transfer Feed Fill ×10',
    icon:        'ri-transfer-line',
    color:       '#A78BFA',
    description: 'Transfer to Feed Tank — qty material 10 MT',
    async run() {
      const root = document.querySelector('#app')
      if (!root) throw new Error('App root tidak ditemukan')

      await clickBtn(root, 'transfer to feed tank', 800)
      const dialog = await waitForDialog(12000)

      await pickSelect(dialog, 'source sloc', 'text', 'storage eob 1')
        .catch(() => pickSelect(dialog, 'source sloc', 'first'))

      await sleep(1800)
      await selectAllSubSlocDropdown(dialog, 'sub-sloc', 'source sloc')
      await sleep(300)

      const destField = findVSelectByLabel(dialog, 'destination sloc')
      const destVal   = destField?.querySelector('.v-field__input')?.textContent?.trim() ?? ''
      if (!destVal) {
        await pickSelect(dialog, 'destination sloc', 'text', 'feed eob 1')
          .catch(() => pickSelect(dialog, 'destination sloc', 'first'))
        await sleep(1500)
      } else {
        await sleep(200)
      }

      await selectAllSubSlocDropdown(dialog, 'sub-sloc', 'destination sloc')
      await sleep(300)

      const dialogCountBefore = getAllActiveDialogCards().length
      await clickBtn(dialog, 'material', 500)
      const materialDialog = await waitForNewDialog(dialogCountBefore, 6000)

      await pickSelect(materialDialog, 'material', 'text', 'stearin')
      await sleep(200)
      await fillField(materialDialog, 'qty', '10')
      await clickBtn(materialDialog, 'add', 500)

      await waitFor(
        () => getAllActiveDialogCards().length <= dialogCountBefore,
        { timeout: 5000, label: 'material dialog to close' }
      )
      await sleep(300)
      const mainDialog = getTopDialogCard() || dialog
      await clickBtn(mainDialog, 'confirm transfer', 300)
    },
  },
]

// ── Composable ────────────────────────────────────────────────

export function useQuickFill() {
  const running     = ref(false)
  const runningKey  = ref(null)
  const lastError   = ref(null)
  const lastSuccess = ref(null)

  async function runPreset(key) {
    const preset = PRESETS.find(p => p.key === key)
    if (!preset) throw new Error(`Unknown preset: ${key}`)

    _abortController  = new AbortController()
    running.value     = true
    runningKey.value  = key
    lastError.value   = null
    lastSuccess.value = null

    try {
      await preset.run()
      lastSuccess.value = `"${preset.label}" selesai`
    } catch (e) {
      if (e.message === 'CANCELLED') return
      lastError.value = e.message
      throw e
    } finally {
      running.value    = false
      runningKey.value = null
      _abortController = null
    }
  }

  function cancelPreset() {
    _abortController?.abort()
  }

  return { presets: PRESETS, running, runningKey, lastError, lastSuccess, runPreset, cancelPreset }
}
