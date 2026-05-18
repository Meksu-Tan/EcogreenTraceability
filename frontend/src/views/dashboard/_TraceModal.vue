<template>
  <div class="space-y-4">
    <h4 class="text-sm font-black uppercase text-slate-700">{{ title }}</h4>
    <div class="overflow-x-auto rounded-md border border-slate-200">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs font-black uppercase text-slate-600">
          <tr>
            <th class="px-3 py-3 text-left">Trace No</th>
            <th v-if="hasPlantColumn" class="px-3 py-3 text-left">Plant</th>
            <th class="px-3 py-3 text-left">Entry Date</th>
            <th class="px-3 py-3 text-left">Material</th>
            <th class="px-3 py-3 text-right">Qty (MT)</th>
            <th class="px-3 py-3 text-left">Supplier / Batch</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td :colspan="columnCount" class="px-3 py-6 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="rows.length === 0">
            <td :colspan="columnCount" class="px-3 py-6 text-center text-slate-500">No trace detail data</td>
          </tr>
          <tr v-else v-for="row in rows" :key="`${row.id_trace_head}-${row.path}`" class="hover:bg-slate-50">
            <td class="px-3 py-3 font-semibold text-slate-800">{{ row.trace_no || row.to_trace_no || '-' }}</td>
            <td v-if="hasPlantColumn" class="px-3 py-3 font-semibold text-slate-600">{{ row.plant_code || '-' }}</td>
            <td class="px-3 py-3">{{ row.entry_date || '-' }}</td>
            <td class="px-3 py-3">{{ row.material || '-' }}</td>
            <td class="px-3 py-3 text-right">{{ row.in_qty || row.out_qty || '-' }}</td>
            <td class="whitespace-pre-line px-3 py-3">{{ formatSupplier(row.supplier) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const hasPlantColumn = computed(() => props.rows.some((row) => row?.plant_code || row?.plant_name))
const columnCount = computed(() => hasPlantColumn.value ? 6 : 5)

function formatSupplier(value) {
  if (!value) return '-'
  return String(value).replaceAll(' || ', '\n').replaceAll(' | ', '\n')
}
</script>
