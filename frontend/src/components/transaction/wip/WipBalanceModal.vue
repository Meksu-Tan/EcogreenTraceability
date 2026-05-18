<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/60 p-4" @mousedown.self="$emit('close')">
      <div class="max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b px-5 py-4">
          <h3 class="text-lg font-bold">Balance Per Batches — {{ panel?.title }}</h3>
          <button type="button" @click="$emit('close')"><i class="fas fa-times"></i></button>
        </div>
        <div class="overflow-x-auto p-4">
          <p v-if="loading" class="py-8 text-center text-slate-500">Loading...</p>
          <table v-else class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase"><tr>
              <th class="px-2 py-2">Entry Date</th><th class="px-2 py-2">Trace No</th><th class="px-2 py-2">Matl Doc</th>
              <th class="px-2 py-2">Material</th><th class="px-2 py-2">Sloc</th><th class="px-2 py-2 text-right">Init Mat</th>
              <th class="px-2 py-2 text-right">Init Sup</th><th class="px-2 py-2 text-right">Qty Balance</th><th class="px-2 py-2">Supplier</th>
            </tr></thead>
            <tbody>
              <tr v-for="row in rows" :key="row.trace_no" class="border-t">
                <td class="px-2 py-2">{{ row.entry_date }}</td>
                <td class="px-2 py-2">{{ row.trace_no }}</td>
                <td class="px-2 py-2">{{ row.material_document || '-' }}</td>
                <td class="px-2 py-2"><span v-for="(b,i) in splitBadges(row.material)" :key="i" class="mr-1 rounded bg-slate-200 px-1 text-xs">{{ b }}</span></td>
                <td class="px-2 py-2">{{ row.sloc }}</td>
                <td class="px-2 py-2 text-right" :class="qtyMatchClass(row.init_qty, row.balance_supplier)">{{ row.init_qty }}</td>
                <td class="px-2 py-2 text-right" :class="qtyMatchClass(row.init_qty, row.balance_supplier)">{{ row.balance_supplier }}</td>
                <td class="px-2 py-2 text-right">{{ row.qty }}</td>
                <td class="px-2 py-2"><span v-for="(b,i) in splitBadges(row.supplier)" :key="i" class="mr-1 rounded bg-blue-600 px-1 text-xs text-white">{{ b }}</span></td>
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
import { splitBadges, qtyMatchClass } from './wipFormat'

const props = defineProps({ open: Boolean, panel: Object, plantId: { type: [String, Number], default: null } })
defineEmits(['close'])
const store = useTransactionWipStore()
const rows = ref([])
const loading = ref(false)

async function load() {
  if (!props.panel?.balanceRundownId) return
  loading.value = true
  try {
    rows.value = await store.fetchBalance(props.panel.balanceRundownId, props.plantId)
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (v) => { if (v) load() })
</script>
