<template>
  <VTable density="compact" class="wip-mini-table">
    <thead>
      <tr>
        <th
          v-for="col in columns"
          :key="col.key"
          class="text-caption font-weight-bold text-uppercase text-medium-emphasis"
          :class="{ 'sort-active': sortKey === col.key }"
          style="cursor: pointer; user-select: none; white-space: nowrap;"
          @click="toggleSort(col.key)"
        >
          {{ col.label }}
          <VIcon
            v-if="sortKey === col.key"
            :icon="sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
            size="14"
            class="sort-icon"
          />
          <VIcon
            v-else
            icon="ri-arrow-up-down-line"
            size="12"
            class="sort-icon"
          />
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(row, idx) in displayData" :key="idx">
        <td v-for="col in columns" :key="col.key" :class="col.class || ''">
          <!-- Trace numbers -->
          <template v-if="col.key === 'to_trace_no' || col.key === 'rundown_trace_no' || col.key === 'trace_no'">
            <span class="text-caption font-weight-bold" style="font-family: var(--font-mono);">{{ row[col.key] || '-' }}</span>
          </template>

          <!-- Out Qty -->
          <template v-else-if="col.key === 'out_qty'">
            <span class="text-caption font-weight-bold text-primary" style="font-family: var(--font-mono);">{{ formatQty(row[col.key]) }}</span>
          </template>
          <!-- In Qty -->
          <template v-else-if="col.key === 'in_qty'">
            <span class="text-caption font-weight-bold text-success" style="font-family: var(--font-mono);">{{ formatQty(row[col.key]) }}</span>
          </template>
          <!-- Qty -->
          <template v-else-if="col.key === 'qty'">
            <span class="text-caption font-weight-bold" style="font-family: var(--font-mono);">{{ formatQty(row[col.key]) }}</span>
          </template>

          <!-- Init Qty -->
          <template v-else-if="col.key === 'init_qty'">
            <span class="text-caption font-weight-bold" :class="tallyVClass(row.init_qty, row.balance_supplier)" style="font-family: var(--font-mono);">{{ formatQty(row[col.key]) }}</span>
          </template>

          <!-- Balance Supplier -->
          <template v-else-if="col.key === 'balance_supplier'">
            <span class="text-caption font-weight-bold" :class="tallyVClass(row.balance_supplier, row.init_qty)" style="font-family: var(--font-mono);">{{ formatQty(row[col.key]) }}</span>
          </template>

          <!-- Suppliers / Materials -->
          <template v-else-if="col.key === 'supplier' || col.key === 'material'">
            <div class="d-flex flex-wrap gap-1">
              <VChip v-for="(item, i) in splitPipe(row[col.key])" :key="i" size="x-small" variant="tonal">{{ item }}</VChip>
              <span v-if="!row[col.key]" class="text-disabled">-</span>
            </div>
          </template>

          <!-- Material Document -->
          <template v-else-if="col.key === 'material_document'">
            <span v-if="row.material_document" class="text-caption font-weight-bold" style="font-family: var(--font-mono); color: rgb(var(--v-theme-secondary));">{{ row.material_document }}</span>
            <span v-else class="text-disabled">-</span>
          </template>

          <!-- Default -->
          <template v-else>
            <span class="text-caption">{{ row[col.key] || '-' }}</span>
          </template>
        </td>
      </tr>
      <tr v-if="displayData.length === 0">
        <td :colspan="columns.length" class="text-center pa-6">
          <span class="text-caption text-medium-emphasis">No data available</span>
        </td>
      </tr>
    </tbody>
  </VTable>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  columns: { type: Array, default: () => [] },
  data: { type: [Array, Object], default: () => [] },
})

const sortKey = ref(null)
const sortDir = ref(null)

function detectColumnType(colKey) {
  const rows = Array.isArray(props.data) ? props.data : props.data ? [props.data] : []
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
    const type = detectColumnType(key)
    sortDir.value = type === 'text' ? 'asc' : 'desc'
  }
}

const sortedData = computed(() => {
  if (!sortKey.value || !sortDir.value) {
    return Array.isArray(props.data) ? props.data : props.data ? [props.data] : []
  }
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...(Array.isArray(props.data) ? props.data : props.data ? [props.data] : [])]
  return rows.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    const type = detectColumnType(key)
    if (type === 'number') {
      return dir === 'asc' ? va - vb : vb - va
    }
    return dir === 'asc'
      ? String(va).localeCompare(String(vb))
      : String(vb).localeCompare(String(va))
  })
})

const displayData = computed(() => sortedData.value)

function splitPipe(val) {
  if (!val) return []
  return String(val).split('|').map(s => s.trim()).filter(Boolean)
}

function parseNum(val) {
  if (val === null || val === undefined) return 0
  const cleaned = String(val).replace(/,/g, '')
  const num = parseFloat(cleaned)
  return isNaN(num) ? 0 : num
}

function formatQty(val) {
  if (val === null || val === undefined) return '-'
  const num = parseFloat(String(val).replace(/,/g, ''))
  if (isNaN(num)) return val
  return num.toLocaleString('en-US', { minimumFractionDigits: 3, maximumFractionDigits: 3 })
}

function tallyVClass(valA, valB) {
  const diff = Math.abs(parseNum(valA) - parseNum(valB))
  return diff <= 0.005 ? 'text-success' : 'text-error'
}
</script>

<style scoped>
.sort-icon {
  vertical-align: middle;
  transition: opacity 0.15s;
  opacity: 0.35;
}
.wip-mini-table th:hover .sort-icon {
  opacity: 0.7;
}
.wip-mini-table th.sort-active .sort-icon {
  opacity: 1 !important;
  color: rgb(var(--v-theme-primary));
}
</style>
