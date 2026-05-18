<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ title }}</h1>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Lokasi:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
              <i class="fas fa-industry mr-1.5 opacity-70"></i>
              {{ plantSelectionStore.selectedPlantName }}
            </span>
          </div>
        </div>
        <div class="hidden h-10 w-px bg-gray-200 md:block"></div>
        <PlantSelector @change="$emit('plant-change')" />
      </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
      <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-6 py-4">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-700 active:scale-95"
          @click="$emit('entry')"
        >
          <i :class="['fas', entryIcon]"></i>
          {{ entryLabel }}
        </button>
        <slot name="actions" />
        <span class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-xs font-black text-white">
          QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
        </span>
      </div>

      <div class="overflow-x-auto p-6">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-600">
            <tr>
              <th v-for="column in columns" :key="column" class="px-3 py-3 text-left whitespace-nowrap">
                {{ column }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="loading">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-slate-500">
                Loading...
              </td>
            </tr>
            <tr v-else-if="rows.length === 0">
              <td :colspan="columns.length" class="px-3 py-8 text-center text-slate-500">
                {{ emptyText }}
              </td>
            </tr>
            <tr v-else v-for="(row, rowIndex) in rows" :key="rowKeyValue(row, rowIndex)" class="hover:bg-slate-50">
              <td v-for="(column, columnIndex) in columns" :key="`${column}-${columnIndex}`" class="px-3 py-3 align-top text-slate-700">
                <button
                  v-if="isButton(cellValue(row, columnIndex, rowIndex))"
                  type="button"
                  class="inline-flex items-center gap-2 rounded-md bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-700"
                  @click="cellValue(row, columnIndex, rowIndex).onClick(row)"
                >
                  <i v-if="cellValue(row, columnIndex, rowIndex).icon" :class="['fas', cellValue(row, columnIndex, rowIndex).icon]"></i>
                  {{ cellValue(row, columnIndex, rowIndex).label }}
                </button>
                <span v-else class="whitespace-pre-line">{{ cellValue(row, columnIndex, rowIndex) }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import PlantSelector from '@/components/shared/PlantSelector.vue'

const props = defineProps({
  title: { type: String, required: true },
  entryLabel: { type: String, required: true },
  entryIcon: { type: String, default: 'fa-edit' },
  columns: { type: Array, required: true },
  fields: { type: Array, default: () => [] },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  rowKey: { type: [String, Function], default: '' },
  emptyText: { type: String, required: true },
  plantSelectionStore: { type: Object, required: true },
})

defineEmits(['entry', 'plant-change'])

function cellValue(row, columnIndex, rowIndex) {
  const field = props.fields?.[columnIndex]
  if (field === '__index') return rowIndex + 1
  if (typeof field === 'function') return field(row, rowIndex)
  if (field) return normalizeCell(row?.[field])
  return '-'
}

function rowKeyValue(row, index) {
  if (typeof props.rowKey === 'function') return props.rowKey(row, index)
  if (props.rowKey && row?.[props.rowKey] !== undefined) return row[props.rowKey]
  return row?.idHead || row?.id_balance_head || row?.trace_no || index
}

function normalizeCell(value) {
  if (value === null || value === undefined || value === '') return '-'
  return String(value).replaceAll(' || ', '\n').replaceAll(' | ', '\n')
}

function isButton(value) {
  return value && typeof value === 'object' && value.type === 'button' && typeof value.onClick === 'function'
}
</script>
