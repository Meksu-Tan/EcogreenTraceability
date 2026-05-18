<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">{{ title }}</h1>
        <div v-if="subtitle" class="mt-1 text-sm font-semibold text-slate-500">{{ subtitle }}</div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <slot name="toolbar" />
      </div>
    </div>

    <div v-if="banner" class="rounded-lg bg-slate-950 px-6 py-5 text-center shadow-sm">
      <h2 class="text-2xl font-black uppercase tracking-wide text-white">{{ banner }}</h2>
    </div>

    <div v-if="$slots.filters" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <slot name="filters" />
    </div>

    <div v-for="table in tables" :key="table.title" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div v-if="table.title" class="border-b border-slate-100 px-5 py-4">
        <span class="inline-flex rounded-md bg-green-600 px-3 py-1.5 text-sm font-black text-white">{{ table.title }}</span>
      </div>
      <div class="overflow-x-auto p-5">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-600">
            <tr>
              <th v-for="column in table.columns" :key="column" class="px-3 py-3 text-left whitespace-nowrap">{{ column }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="table.loading">
              <td :colspan="table.columns.length" class="px-3 py-8 text-center text-slate-500">
                Loading...
              </td>
            </tr>
            <tr v-else-if="!table.rows || table.rows.length === 0">
              <td :colspan="table.columns.length" class="px-3 py-8 text-center text-slate-500">
                {{ table.emptyText || 'No data found' }}
              </td>
            </tr>
            <tr v-else v-for="(row, rowIndex) in table.rows" :key="rowKey(table, row, rowIndex)" class="hover:bg-slate-50">
              <td v-for="(column, columnIndex) in table.columns" :key="`${column}-${columnIndex}`" class="px-3 py-3 align-top text-slate-700">
                <button
                  v-if="isButton(cellValue(table, row, columnIndex, rowIndex))"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-700"
                  @click="cellValue(table, row, columnIndex, rowIndex).onClick(row)"
                >
                  <i v-if="cellValue(table, row, columnIndex, rowIndex).icon" :class="['fas', cellValue(table, row, columnIndex, rowIndex).icon]"></i>
                  {{ cellValue(table, row, columnIndex, rowIndex).label }}
                </button>
                <span v-else class="whitespace-pre-line" :class="cellClass(table, row, columnIndex, rowIndex)">
                  {{ cellValue(table, row, columnIndex, rowIndex) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="activeModal" class="fixed inset-0 z-[1050] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @mousedown.self="$emit('close-modal')">
      <div class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
          <h3 class="text-base font-black text-slate-800">{{ activeModal.title }}</h3>
          <button class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="$emit('close-modal')">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="overflow-y-auto p-5">
          <slot name="modal" :modal="activeModal" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  banner: { type: String, default: '' },
  tables: { type: Array, required: true },
  activeModal: { type: Object, default: null },
})

defineEmits(['close-modal'])

function rowKey(table, row, index) {
  if (typeof table.rowKey === 'function') return table.rowKey(row, index)
  if (table.rowKey && row?.[table.rowKey] !== undefined) return row[table.rowKey]
  return row?.id || row?.trace_no || row?.id_trace_head || index
}

function cellValue(table, row, columnIndex, rowIndex) {
  const field = table.fields?.[columnIndex]

  if (field === '__index') return rowIndex + 1
  if (typeof field === 'function') return field(row, rowIndex)
  if (field) return normalizeCell(row?.[field])

  return '-'
}

function cellClass(table, row, columnIndex, rowIndex) {
  const classField = table.cellClasses?.[columnIndex]
  if (typeof classField === 'function') return classField(row, rowIndex)
  return classField || ''
}

function normalizeCell(value) {
  if (value === null || value === undefined || value === '') return '-'
  return String(value).replaceAll(' || ', '\n').replaceAll(' | ', '\n')
}

function isButton(value) {
  return value && typeof value === 'object' && value.type === 'button' && typeof value.onClick === 'function'
}
</script>
