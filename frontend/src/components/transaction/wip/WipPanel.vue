<template>
  <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
    <div class="px-4 py-3 text-center bg-white">
      <span class="inline-block w-full rounded border border-slate-200 bg-slate-100 px-3 py-2 text-lg font-bold text-slate-900">
        {{ panel.title }}
      </span>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-50 px-4 py-2">
      <p class="text-sm font-medium text-slate-600">LATEST LOG OF {{ panel.entryLabel }}</p>
      <div class="flex flex-wrap justify-end gap-2">
        <button type="button" class="inline-flex items-center gap-1 rounded bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800" @click="emit('entry', panel)">
          <i class="fas fa-edit"></i> {{ panel.entryLabel }}
        </button>
        <button v-if="panel.showBalance && panel.balanceRundownId" type="button" class="inline-flex items-center gap-1 rounded bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800" @click="emit('balance', panel)">
          <i class="fas fa-bars"></i> View Balance Per Batches
        </button>
        <button type="button" class="inline-flex items-center gap-1 rounded bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-800" @click="emit('log', panel)">
          <i class="fas fa-bars"></i> {{ panel.kind === 'feed' ? 'View Feed Logs' : 'View Rundown Logs' }}
        </button>
        <span class="inline-flex items-center rounded bg-red-600 px-2 py-1.5 text-xs font-bold text-white">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.</span>
      </div>
    </div>
    <div class="overflow-x-auto p-4">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-xs font-bold uppercase text-slate-600">
          <tr>
            <th class="px-3 py-2 text-center">{{ traceLabel }}</th>
            <th class="px-3 py-2 text-center">Entry Date</th>
            <th class="px-3 py-2 text-center">Matl Doc</th>
            <th class="px-3 py-2 text-center">Sloc</th>
            <th class="px-3 py-2 text-right">Total Material (MT)</th>
            <th class="px-3 py-2 text-right">Total Supplier (MT)</th>
            <th class="px-3 py-2 text-left">{{ supplierLabel }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading"><td colspan="7" class="px-3 py-6 text-center text-slate-500">Loading...</td></tr>
          <tr v-else-if="rows.length === 0"><td colspan="7" class="px-3 py-6 text-center text-slate-500">No entries</td></tr>
          <tr v-else v-for="row in rows" :key="row.id_trace_head || traceNo(row)" class="hover:bg-slate-50">
            <td class="px-3 py-2 text-center font-medium">{{ traceNo(row) }}</td>
            <td class="px-3 py-2 text-center">{{ row.entry_date }}</td>
            <td class="px-3 py-2 text-center">
              <template v-if="row.material_document">
                <span>{{ row.material_document }}</span>
                <button type="button" class="ml-1 text-slate-500 hover:text-green-700" title="Edit Material Document" @click="onMatlDoc(row)">
                  <i class="fas fa-pencil-alt text-xs"></i>
                </button>
              </template>
              <button v-else type="button" class="rounded bg-amber-400 px-2 py-0.5 text-xs font-bold text-black" @click="onMatlDoc(row)">Add Doc No</button>
            </td>
            <td class="px-3 py-2 text-center">
              <button type="button" class="text-slate-600 underline" @click="onSloc(row)">{{ row.sloc || '-' }}</button>
            </td>
            <td class="px-3 py-2 text-right" :class="qtyMatchClass(row[qtyField], row.balance_supplier)">{{ row[qtyField] }}</td>
            <td class="px-3 py-2 text-right" :class="qtyMatchClass(row[qtyField], row.balance_supplier)">{{ row.balance_supplier }}</td>
            <td class="px-3 py-2">
              <span v-for="(badge, i) in splitBadges(row.supplier)" :key="i" class="mr-1 mb-1 inline-block rounded bg-blue-600 px-2 py-0.5 text-xs text-white">{{ badge }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useTransactionWipStore } from '@/stores/transactionWip'
import { splitBadges, qtyMatchClass } from './wipFormat'

const props = defineProps({
  panel: { type: Object, required: true },
  plantId: { type: [String, Number], default: null },
  refreshKey: { type: Number, default: 0 },
})

const emit = defineEmits(['entry', 'balance', 'log', 'edit-sloc', 'matl-doc'])

const store = useTransactionWipStore()
const rows = ref([])
const loading = ref(false)

const traceLabel = props.panel.kind === 'feed' ? 'Feed Trace No' : 'WIP Trace No'
const qtyField = props.panel.kind === 'feed' ? 'out_qty' : 'in_qty'
const supplierLabel =
  props.panel.kind === 'feed'
    ? 'RM Trace No./ Supplier / Batch SAP / Out_Qty (MT)'
    : 'Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)'

async function load() {
  loading.value = true
  try {
    if (props.panel.kind === 'feed') {
      rows.value = await store.fetchLatestFeed(props.panel.feedId, props.plantId)
    } else {
      rows.value = await store.fetchLatestRundown(props.panel.rundownId, props.plantId)
    }
  } catch {
    rows.value = []
  } finally {
    loading.value = false
  }
}

function traceNo(row) {
  return row.to_trace_no ?? row.rundown_trace_no ?? '-'
}

function onMatlDoc(row) {
  emit('matl-doc', { row, panel: props.panel })
}

function onSloc(row) {
  emit('edit-sloc', { row, panel: props.panel })
}

watch(() => [props.refreshKey, props.plantId, props.panel.id], load, { immediate: true })
onMounted(load)

defineExpose({ reload: load })
</script>