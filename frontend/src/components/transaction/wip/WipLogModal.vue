<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4" @mousedown.self="$emit('close')">
      <div class="max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-5 py-4">
          <h3 class="text-lg font-bold">{{ panel?.logPrefix || panel?.title }} — Logs</h3>
          <button type="button" @click="$emit('close')"><i class="fas fa-times"></i></button>
        </div>
        <div class="overflow-x-auto p-4">
          <p v-if="loading" class="py-8 text-center">Loading...</p>
          <table v-else class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase">
              <tr>
                <th class="px-2 py-2">Action</th>
                <th class="px-2 py-2">Trace No</th>
                <th class="px-2 py-2">Entry Date</th>
                <th class="px-2 py-2">Matl Doc</th>
                <th class="px-2 py-2 text-right">Qty</th>
                <th class="px-2 py-2">Supplier</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id_trace_head" class="border-t">
                <td class="px-2 py-2">
                  <button
                    v-if="row.is_last_row == 1 && !row.next_process"
                    type="button"
                    class="rounded bg-red-600 px-2 py-1 text-xs text-white"
                    @click="cancelRow(row)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
                <td class="px-2 py-2">{{ row.to_trace_no || row.rundown_trace_no }}</td>
                <td class="px-2 py-2">{{ row.entry_date }}</td>
                <td class="px-2 py-2">
                  <template v-if="row.material_document">
                    {{ row.material_document }}
                    <button type="button" class="ml-1 text-slate-500 hover:text-green-700" @click="openMatlDoc(row)"><i class="fas fa-pencil-alt text-xs"></i></button>
                  </template>
                  <button v-else type="button" class="rounded bg-amber-400 px-2 py-0.5 text-xs font-bold text-black" @click="openMatlDoc(row)">Add Doc No</button>
                </td>
                <td class="px-2 py-2 text-right">{{ row.out_qty || row.in_qty }}</td>
                <td class="px-2 py-2 text-xs">{{ row.supplier }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTransactionWipStore } from '@/stores/transactionWip'
import { useToastStore } from '@/stores/toast'

const props = defineProps({ open: Boolean, panel: Object, plantId: { type: [String, Number], default: null } })
const emit = defineEmits(['close', 'changed', 'matl-doc'])
const store = useTransactionWipStore()
const toast = useToastStore()
const rows = ref([])
const loading = ref(false)

async function load() {
  if (!props.panel) return
  loading.value = true
  try {
    if (props.panel.kind === 'feed') {
      rows.value = await store.fetchFeedLog(props.panel.feedId, props.plantId)
    } else {
      rows.value = await store.fetchRundownLog(props.panel.rundownId, props.plantId)
    }
  } finally {
    loading.value = false
  }
}

function openMatlDoc(row) {
  emit('matl-doc', row)
}

async function cancelRow(row) {
  const traceNo = row.to_trace_no || row.rundown_trace_no
  const { default: Swal } = await import('sweetalert2')
  const confirm = await Swal.fire({
    title: 'Cancel Entry',
    text: `Delete trace no. ${traceNo}?`,
    icon: 'warning',
    showCancelButton: true,
  })
  if (!confirm.isConfirmed) return
  const payload = {
    idTraceHead: row.id_trace_head,
    idBalanceHead: row.id_balance_head,
    traceNo,
  }
  const result =
    props.panel.kind === 'feed'
      ? await store.cancelFeed(payload, props.plantId)
      : await store.cancelRundown(payload, props.plantId)
  if (result?.status === 1 || result?.success) {
    toast.success(result.message || 'Cancelled')
    emit('changed')
    await load()
  } else {
    toast.error(result?.message || 'Cancel failed')
  }
}

watch(() => props.open, (v) => { if (v) load() })
</script>
