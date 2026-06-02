<template>
  <div class="overflow-x-auto border border-gray-200 rounded bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-xs">
      <thead class="bg-gray-50">
        <tr>
          <th v-for="col in columns" :key="col.key" class="px-3 py-2 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">
            {{ col.label }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <tr v-for="(row, idx) in displayData" :key="idx" class="hover:bg-gray-50">
          <td v-for="col in columns" :key="col.key" class="px-3 py-2 text-gray-700" :class="col.class || ''">
            <!-- Trace numbers -->
            <template v-if="col.key === 'to_trace_no' || col.key === 'rundown_trace_no' || col.key === 'trace_no'">
              <span class="font-mono font-bold text-gray-800">{{ row[col.key] || '-' }}</span>
            </template>

            <!-- Quantities -->
            <template v-else-if="col.key === 'out_qty'">
              <span class="font-mono font-bold text-amber-700">{{ formatQty(row[col.key]) }}</span>
            </template>
            <template v-else-if="col.key === 'in_qty'">
              <span class="font-mono font-bold text-green-700">{{ formatQty(row[col.key]) }}</span>
            </template>
            <template v-else-if="col.key === 'qty'">
              <span class="font-mono font-bold">{{ formatQty(row[col.key]) }}</span>
            </template>

            <!-- Init Qty - Audit Tally -->
            <template v-else-if="col.key === 'init_qty'">
              <span class="font-mono font-bold" :class="tallyClass(row.init_qty, row.balance_supplier)">{{ formatQty(row[col.key]) }}</span>
            </template>

            <!-- Balance Supplier -->
            <template v-else-if="col.key === 'balance_supplier'">
              <span class="font-mono font-bold" :class="tallyClass(row.balance_supplier, row.init_qty)">{{ formatQty(row[col.key]) }}</span>
            </template>

            <!-- Sloc -->
            <template v-else-if="col.key === 'sloc'">
              {{ row[col.key] || '-' }}
            </template>

            <!-- Suppliers / Materials -->
            <template v-else-if="col.key === 'supplier' || col.key === 'material'">
              <div class="flex flex-wrap gap-1">
                <span v-for="(item, i) in splitPipe(row[col.key])" :key="i" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border" :class="getTagColorClass(item)">
                  {{ item }}
                </span>
                <span v-if="!row[col.key]" class="text-gray-300">-</span>
              </div>
            </template>

            <!-- Material Document -->
            <template v-else-if="col.key === 'material_document'">
              <span v-if="row.material_document" class="font-mono font-bold text-purple-700">{{ row.material_document }}</span>
              <span v-else class="text-gray-300">-</span>
            </template>

            <!-- Dates -->
            <template v-else-if="col.key === 'entry_date'">
              <span class="text-gray-600 whitespace-nowrap">{{ row[col.key] || '-' }}</span>
            </template>

            <!-- Default -->
            <template v-else>
              {{ row[col.key] || '-' }}
            </template>
          </td>
        </tr>
        <tr v-if="displayData.length === 0">
          <td :colspan="columns.length" class="px-4 py-8 text-center text-gray-400">
            <span class="text-xs">No data available</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  columns: { type: Array, default: () => [] },
  data: { type: [Array, Object], default: () => [] },
})

const displayData = computed(() => {
  if (Array.isArray(props.data)) return props.data
  if (props.data && typeof props.data === 'object') return [props.data]
  return []
})

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

function tallyClass(valA, valB) {
  const diff = Math.abs(parseNum(valA) - parseNum(valB))
  if (diff <= 0.005) {
    return 'bg-green-50 text-green-700 border-green-200/60'
  }
  return 'bg-red-50 text-red-700 border-red-200/60'
}

function getTagColorClass(val) {
  if (!val) return 'bg-gray-50 text-gray-600 border-gray-200'

  // Calculate a hash of the string
  let hash = 0
  for (let i = 0; i < val.length; i++) {
    hash = val.charCodeAt(i) + ((hash << 5) - hash)
  }
  hash = Math.abs(hash)

  const palettes = [
    'bg-blue-50 text-blue-700 border-blue-200/60',
    'bg-emerald-50 text-emerald-700 border-emerald-200/60',
    'bg-purple-50 text-purple-700 border-purple-200/60',
    'bg-amber-50 text-amber-700 border-amber-200/60',
    'bg-indigo-50 text-indigo-700 border-indigo-200/60',
    'bg-rose-50 text-rose-700 border-rose-200/60',
    'bg-cyan-50 text-cyan-700 border-cyan-200/60',
    'bg-orange-50 text-orange-700 border-orange-200/60',
    'bg-teal-50 text-teal-700 border-teal-200/60',
  ]

  return palettes[hash % palettes.length]
}
</script>