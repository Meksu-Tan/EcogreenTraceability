import { ref, computed } from 'vue'

export function useTableSort(dataRef) {
  const sortKey = ref(null)
  const sortDir = ref(null)

  function detectColumnType(colKey) {
    const rows = typeof dataRef.value === 'function' ? dataRef() : dataRef.value
    if (!rows || rows.length === 0) return 'text'
    for (const row of rows) {
      const val = row[colKey]
      if (val !== null && val !== undefined && val !== '') {
        return !isNaN(parseFloat(val)) && isFinite(val) ? 'number' : 'text'
      }
    }
    return 'text'
  }

  function toggleSort(key) {
    if (sortKey.value === key) {
      if (sortDir.value === 'asc') {
        sortDir.value = 'desc'
      } else if (sortDir.value === 'desc') {
        sortKey.value = null
        sortDir.value = null
      }
    } else {
      sortKey.value = key
      sortDir.value = detectColumnType(key) === 'text' ? 'asc' : 'desc'
    }
  }

  const sortedData = computed(() => {
    if (!sortKey.value || !sortDir.value) return dataRef.value
    const key = sortKey.value
    const dir = sortDir.value
    const rows = [...dataRef.value]
    const type = detectColumnType(key)
    return rows.sort((a, b) => {
      const va = a[key]
      const vb = b[key]
      if (va == null && vb == null) return 0
      if (va == null) return 1
      if (vb == null) return -1
      if (type === 'number') {
        return dir === 'asc' ? va - vb : vb - va
      }
      return dir === 'asc'
        ? String(va).localeCompare(String(vb))
        : String(vb).localeCompare(String(va))
    })
  })

  function resetSort() {
    sortKey.value = null
    sortDir.value = null
  }

  return { sortKey, sortDir, sortedData, toggleSort, detectColumnType, resetSort }
}
