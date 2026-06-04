<template>
  <div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <h1 class="text-xl font-bold mb-4 flex items-center gap-2"><Icon icon="ri:flask-line" class="w-6 h-6 text-orange-600" /> Summary of Raw Material to Product</h1>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Year</label><select v-model="selectedYear" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></div>
        <div class="flex items-end"><button @click="loadData" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm font-semibold hover:bg-orange-700 flex items-center gap-1"><Icon icon="ri:search-line" class="w-4 h-4" /> Search</button></div>
      </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border">
      <div v-if="loading" class="p-8 text-center text-gray-500"><svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg><p class="mt-2">Loading report data...</p></div>
      <template v-else>
        <div class="overflow-x-auto"><table class="w-full">
          <thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase">
            <th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-center w-20">Action</th><th class="px-3 py-3 text-left">Trace No</th><th class="px-3 py-3 text-left">Entry Date</th>
            <th class="px-3 py-3 text-left">Matl Doc</th><th class="px-3 py-3 text-left">PurchO</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-left">Sloc</th>
            <th class="px-3 py-3 text-right">Init (MT)</th><th class="px-3 py-3 text-right">On-Hand (MT)</th><th class="px-3 py-3 text-right">On-WIP (MT)</th><th class="px-3 py-3 text-right">On-PRD (MT)</th><th class="px-3 py-3 text-right">On-ADJOUT (MT)</th><th class="px-3 py-3 text-left max-w-sm">Supplier / Batch SAP / Init Qty (MT)</th>
          </tr></thead>
          <tbody v-if="tableData.length === 0">
            <tr><td colspan="14" class="p-8 text-center text-gray-400"><Icon icon="ri:flask-line" class="w-12 h-12 mx-auto mb-4 text-gray-300" /><p>No data for {{ selectedYear }}.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(row, i) in tableData" :key="row.id_balance_head || i" class="hover:bg-gray-50">
              <td class="px-3 py-3 text-center">{{ i + 1 }}</td>
              <td class="px-3 py-3 text-center"><button @click="openDetail(row)" class="px-2 py-1 text-xs bg-[#47c363] text-white rounded hover:bg-[#58d474] flex items-center gap-1 mx-auto"><Icon icon="ri:eye-line" class="w-3 h-3" /> Detail</button></td>
              <td class="px-3 py-3 font-mono">{{ row.trace_no }}</td><td class="px-3 py-3">{{ row.entry_date }}</td>
              <td class="px-3 py-3 font-mono">{{ row.material_document || '-' }}</td><td class="px-3 py-3 font-mono">{{ row.po_so || '-' }}</td>
              <td class="px-3 py-3 font-medium max-w-xs truncate" :title="row.material">{{ row.material }}</td><td class="px-3 py-3">{{ row.tank || '-' }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ row.init_qty || row.qty }}</td><td class="px-3 py-3 text-right font-mono font-semibold">{{ row.qty }}</td>
              <td class="px-3 py-3 text-right font-mono">{{ row.qty_tank || '-' }}</td><td class="px-3 py-3 text-right font-mono">{{ row.qty_warehouse || '-' }}</td><td class="px-3 py-3 text-right font-mono">{{ row.qty_adjustment || '-' }}</td>
              <td class="px-3 py-3 max-w-xs truncate text-xs" :title="row.supplier">{{ row.supplier || '-' }}</td>
            </tr>
          </tbody>
        </table></div>
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex justify-between items-center"><span class="text-xs text-gray-500">{{ tableData.length }} records</span></div>
      </template>
    </div>
    <!-- Detail Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="showModal = false">
      <div class="absolute inset-0 bg-black/50" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-4 border-b"><h2 class="text-lg font-semibold flex items-center gap-2"><Icon icon="ri:flask-line" class="text-orange-500 w-5 h-5" /> Detail RM Traceability <span class="text-sm font-normal text-gray-500 ml-2">Batch SAP: {{ detailBatch }} | Qty RM: {{ detailQty }} MT</span></h2><button @click="showModal = false" class="p-1 hover:bg-gray-100 rounded"><Icon icon="ri:close-line" class="w-5 h-5" /></button></div>
        <div class="flex-1 overflow-auto">
          <div class="border-b"><div class="flex">
            <button v-for="tab in detailTabs" :key="tab.key" @click="detailTab = tab.key" :class="detailTab === tab.key ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-3 text-sm flex items-center gap-1"><Icon :icon="tab.icon" class="w-4 h-4" /> {{ tab.label }}</button>
          </div></div>
          <div v-if="detailTab === 'wip'" class="p-4">
            <table class="w-full border"><thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-2 text-center w-12">No</th><th class="px-3 py-2 text-left">Sloc</th><th class="px-3 py-2 text-left">Material</th><th class="px-3 py-2 text-right">IN Qty</th><th class="px-3 py-2 text-right">OUT Qty</th><th class="px-3 py-2 text-right">Balance</th></tr></thead><tbody class="divide-y text-sm"><tr v-for="(d,i) in detailWip" :key="i"><td class="px-3 py-2 text-center">{{ i+1 }}</td><td class="px-3 py-2">{{ d.sloc || '-' }}</td><td class="px-3 py-2">{{ d.material || '-' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.in_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.out_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono font-semibold">{{ d.balance || '0.000' }}</td></tr></tbody></table><div v-if="detailWip.length===0" class="p-4 text-center text-gray-400 text-sm">No WIP data.</div>
          </div>
          <div v-if="detailTab === 'prd'" class="p-4">
            <table class="w-full border"><thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-2 text-center w-12">No</th><th class="px-3 py-2 text-left">Sloc</th><th class="px-3 py-2 text-left">Material</th><th class="px-3 py-2 text-right">IN Qty</th><th class="px-3 py-2 text-right">OUT Qty</th><th class="px-3 py-2 text-right">Balance</th><th class="px-3 py-2 text-left">Shipment</th></tr></thead><tbody class="divide-y text-sm"><tr v-for="(d,i) in detailPrd" :key="i"><td class="px-3 py-2 text-center">{{ i+1 }}</td><td class="px-3 py-2">{{ d.sloc || '-' }}</td><td class="px-3 py-2">{{ d.material || '-' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.in_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.out_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono font-semibold">{{ d.balance || '0.000' }}</td><td class="px-3 py-2">{{ d.shipment || '-' }}</td></tr></tbody></table><div v-if="detailPrd.length===0" class="p-4 text-center text-gray-400 text-sm">No PRODUCT data.</div>
          </div>
          <div v-if="detailTab === 'adj'" class="p-4">
            <table class="w-full border"><thead><tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-2 text-center w-12">No</th><th class="px-3 py-2 text-left">Sloc</th><th class="px-3 py-2 text-left">Material</th><th class="px-3 py-2 text-right">IN Qty</th><th class="px-3 py-2 text-right">OUT Qty</th><th class="px-3 py-2 text-right">Balance</th></tr></thead><tbody class="divide-y text-sm"><tr v-for="(d,i) in detailAdj" :key="i"><td class="px-3 py-2 text-center">{{ i+1 }}</td><td class="px-3 py-2">{{ d.sloc || '-' }}</td><td class="px-3 py-2">{{ d.material || '-' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.in_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono">{{ d.out_qty || '0.000' }}</td><td class="px-3 py-2 text-right font-mono font-semibold">{{ d.balance || '0.000' }}</td></tr></tbody></table><div v-if="detailAdj.length===0" class="p-4 text-center text-gray-400 text-sm">No ADJUSTMENT data.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useRmReportStore } from '../stores/rmReportStore'
const rmReportStore = useRmReportStore()
const selectedYear = ref(new Date().getFullYear()), loading = ref(false), tableData = ref([])
const showModal = ref(false), detailBatch = ref(''), detailQty = ref(''), detailTab = ref('wip')
const detailWip = ref([]), detailPrd = ref([]), detailAdj = ref([])
const years = []; for (let i = 0; i < 5; i++) years.push(new Date().getFullYear() - i)
const detailTabs = [
  { key: 'wip', label: 'On-WIP', icon: 'ri:settings-4-line' },
  { key: 'prd', label: 'On-PRODUCT', icon: 'ri:box-3-line' },
  { key: 'adj', label: 'On-ADJUSTMENT', icon: 'ri:tune-line' }
]
onMounted(() => loadData())
const loadData = async () => {
  loading.value = true
  try { await rmReportStore.fetchRmReportSummary({ year: selectedYear.value }); tableData.value = rmReportStore.rmReportSummary || [] }
  catch { tableData.value = [] } finally { loading.value = false }
}
const openDetail = (row) => {
  showModal.value = true; detailBatch.value = row.batch_sap || row.trace_no || '-'; detailQty.value = row.qty || '0'; detailTab.value = 'wip'
  detailWip.value = row.supplier ? [{ sloc: row.tank || '-', material: row.material, in_qty: row.init_qty || '0', out_qty: '0', balance: row.qty || '0' }] : []
  detailPrd.value = row.supplier ? [{ sloc: row.tank || '-', material: row.material, in_qty: row.init_qty || '0', out_qty: '0', balance: row.qty || '0', shipment: '-' }] : []
  detailAdj.value = row.supplier ? [{ sloc: row.tank || '-', material: row.material, in_qty: row.init_qty || '0', out_qty: '0', balance: row.qty || '0' }] : []
}
</script>
