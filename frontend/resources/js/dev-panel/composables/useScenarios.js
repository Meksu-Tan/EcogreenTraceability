import { ref, readonly } from 'vue'

const STORAGE_KEY = 'dev_panel_scenarios'
const MAX_SCENARIOS = 20

function uid() {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 9)
}

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

function saveToStorage(list) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(list))
}

/**
 * useScenarios composable — save, load, manage recorded scenarios
 */
export function useScenarios() {
  const scenarios = ref(loadFromStorage())

  function persist() {
    saveToStorage(scenarios.value)
  }

  function save(name, description, steps) {
    if (scenarios.value.length >= MAX_SCENARIOS) {
      throw new Error(`Maximum ${MAX_SCENARIOS} scenarios reached. Delete one first.`)
    }
    const scenario = {
      id: uid(),
      name: name || `Scenario ${scenarios.value.length + 1}`,
      description: description || '',
      steps: steps.map(s => ({
        // Strip rawValue to avoid leaking passwords in storage
        id: s.id,
        type: s.type,
        timestamp: s.timestamp,
        selector: s.selector,
        value: s.type === 'fill' && s.value === '••••••' ? '••••••' : s.value,
        label: s.label,
        rowContext: s.rowContext || null,
        from: s.from || null,
      })),
      createdAt: Date.now(),
    }
    scenarios.value.push(scenario)
    persist()
    return scenario
  }

  function remove(id) {
    scenarios.value = scenarios.value.filter(s => s.id !== id)
    persist()
  }

  function rename(id, name) {
    const s = scenarios.value.find(s => s.id === id)
    if (s) { s.name = name; persist() }
  }

  function duplicate(id) {
    const s = scenarios.value.find(s => s.id === id)
    if (!s) return null
    return save(`${s.name} (copy)`, s.description, s.steps)
  }

  function exportJSON(scenario) {
    const blob = new Blob([JSON.stringify(scenario, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${scenario.name.replace(/\s+/g, '_')}.json`
    a.click()
    URL.revokeObjectURL(url)
  }

  function clearAll() {
    scenarios.value = []
    persist()
  }

  return {
    scenarios: readonly(scenarios),
    save,
    remove,
    rename,
    duplicate,
    exportJSON,
    clearAll,
  }
}
