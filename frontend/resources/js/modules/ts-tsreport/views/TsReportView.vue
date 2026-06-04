<template>
  <div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <h1 class="text-xl font-bold mb-4 flex items-center gap-2"><Icon icon="ri:file-list-3-line" class="w-6 h-6 text-blue-600" /> Summary of Daily Transaction</h1>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Select Entry Date</label><input v-model="entryDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" @change="loadAll" /></div>
        <div class="flex items-end"><button @click="loadAll" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 flex items-center gap-1"><Icon icon="ri:search-line" class="w-4 h-4" /> Search</button></div>
      </div>
    </div>
    <div v-if="loading" class="text-center py-8 text-gray-500"><svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg><p class="mt-2">Loading report data...</p></div>
    <template v-else>
      <!-- RM -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b flex items-center justify-between"><span class="inline-flex px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded items-center gap-1"><Icon icon="ri:oil-line" class="w-4 h-4" /> RM Transaction</span><span class="text-xs text-gray-500">{{ rm.length }} records</span></div>
        <div class="overflow-x-auto">
          <table class="w-full"><thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Entry Date</th><th class="px-3 py-3 text-left">Prev Trace</th><th class="px-3 py-3 text-left">Trace</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-right">Qty In (MT)</th><th class="px-3 py-3 text-left">SLoc</th><th class="px-3 py-3 text-right">Qty Out (MT)</th><th class="px-3 py-3 text-right">Qty Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">Supplier / Batch SAP / Qty (MT)</th></tr></thead>
          <tbody v-if="rm.length === 0">
            <tr><td colspan="10" class="p-8 text-center text-gray-400"><Icon icon="ri:inbox-line" class="w-10 h-10 mx-auto mb-2 text-gray-300" /><p class="text-sm">No RM transactions for this date.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(r,i) in rm" :key="i" class="hover:bg-gray-50"><td class="px-3 py-3 text-center">{{ i + 1 }}</td><td class="px-3 py-3">{{ r.entry_date }}</td><td class="px-3 py-3 font-mono">{{ r.from_trace_no || '-' }}</td><td class="px-3 py-3 font-mono">{{ r.to_trace_no }}</td><td class="px-3 py-3 max-w-xs truncate font-medium">{{ r.material }}</td><td class="px-3 py-3 text-right font-mono">{{ r.in_qty }}</td><td class="px-3 py-3">{{ r.sloc || '-' }}</td><td class="px-3 py-3 text-right font-mono">{{ r.out_qty }}</td><td class="px-3 py-3 text-right font-mono" :class="qtyColor(r)">{{ r.balance_supplier }}</td><td class="px-3 py-3 max-w-xs truncate text-xs" :title="r.supplier">{{ r.supplier || '-' }}</td></tr>
          </tbody></table>
        </div>
      </div>
      <!-- WIP -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b flex items-center justify-between"><span class="inline-flex px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded items-center gap-1"><Icon icon="ri:settings-4-line" class="w-4 h-4" /> WIP Transaction</span><span class="text-xs text-gray-500">{{ wip.length }} records</span></div>
        <div class="overflow-x-auto">
          <table class="w-full"><thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Entry Date</th><th class="px-3 py-3 text-left">Prev Trace</th><th class="px-3 py-3 text-left">Trace</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-right">WIP Out (MT)</th><th class="px-3 py-3 text-left">Section</th><th class="px-3 py-3 text-right">WIP In (MT)</th><th class="px-3 py-3 text-right">WIP Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">Supplier</th></tr></thead>
          <tbody v-if="wip.length === 0">
            <tr><td colspan="10" class="p-8 text-center text-gray-400"><Icon icon="ri:inbox-line" class="w-10 h-10 mx-auto mb-2 text-gray-300" /><p class="text-sm">No WIP transactions for this date.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(r,i) in wip" :key="i" class="hover:bg-gray-50"><td class="px-3 py-3 text-center">{{ i + 1 }}</td><td class="px-3 py-3">{{ r.entry_date }}</td><td class="px-3 py-3 font-mono">{{ r.from_trace_no || '-' }}</td><td class="px-3 py-3 font-mono">{{ r.to_trace_no }}</td><td class="px-3 py-3 max-w-xs truncate font-medium">{{ r.material }}</td><td class="px-3 py-3 text-right font-mono">{{ r.wip_out || r.out_qty }}</td><td class="px-3 py-3">{{ r.section || '-' }}</td><td class="px-3 py-3 text-right font-mono">{{ r.wip_in || r.in_qty }}</td><td class="px-3 py-3 text-right font-mono" :class="qtyColor(r)">{{ r.balance_supplier }}</td><td class="px-3 py-3 max-w-xs truncate text-xs" :title="r.supplier">{{ r.supplier || '-' }}</td></tr>
          </tbody></table>
        </div>
      </div>
      <!-- TRANSFER -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b flex items-center justify-between"><span class="inline-flex px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded items-center gap-1"><Icon icon="ri:swap-line" class="w-4 h-4" /> TRANSFER Transaction</span><span class="text-xs text-gray-500">{{ transfer.length }} records</span></div>
        <div class="overflow-x-auto">
          <table class="w-full"><thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Entry Date</th><th class="px-3 py-3 text-left">Prev Trace</th><th class="px-3 py-3 text-left">Trace</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-right">Qty In (MT)</th><th class="px-3 py-3 text-left">SLOC</th><th class="px-3 py-3 text-right">Qty Out (MT)</th><th class="px-3 py-3 text-right">Qty Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">Supplier</th></tr></thead>
          <tbody v-if="transfer.length === 0">
            <tr><td colspan="10" class="p-8 text-center text-gray-400"><Icon icon="ri:inbox-line" class="w-10 h-10 mx-auto mb-2 text-gray-300" /><p class="text-sm">No Transfer transactions for this date.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(r,i) in transfer" :key="i" class="hover:bg-gray-50"><td class="px-3 py-3 text-center">{{ i + 1 }}</td><td class="px-3 py-3">{{ r.entry_date }}</td><td class="px-3 py-3 font-mono">{{ r.from_trace_no || '-' }}</td><td class="px-3 py-3 font-mono">{{ r.to_trace_no }}</td><td class="px-3 py-3 max-w-xs truncate font-medium">{{ r.material }}</td><td class="px-3 py-3 text-right font-mono">{{ r.in_qty }}</td><td class="px-3 py-3">{{ r.sloc || '-' }}</td><td class="px-3 py-3 text-right font-mono">{{ r.out_qty }}</td><td class="px-3 py-3 text-right font-mono" :class="qtyColor(r)">{{ r.balance_supplier }}</td><td class="px-3 py-3 max-w-xs truncate text-xs" :title="r.supplier">{{ r.supplier || '-' }}</td></tr>
          </tbody></table>
        </div>
      </div>
      <!-- PCK -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b flex items-center justify-between"><span class="inline-flex px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded items-center gap-1"><Icon icon="ri:package-line" class="w-4 h-4" /> PACKAGING Transaction</span><span class="text-xs text-gray-500">{{ pck.length }} records</span></div>
        <div class="overflow-x-auto">
          <table class="w-full"><thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Entry Date</th><th class="px-3 py-3 text-left">Prev Trace</th><th class="px-3 py-3 text-left">Trace</th><th class="px-3 py-3 text-left">PPH Batch</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-right">Qty In (MT)</th><th class="px-3 py-3 text-right">Qty Out (MT)</th><th class="px-3 py-3 text-right">Qty Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">Supplier</th></tr></thead>
          <tbody v-if="pck.length === 0">
            <tr><td colspan="10" class="p-8 text-center text-gray-400"><Icon icon="ri:inbox-line" class="w-10 h-10 mx-auto mb-2 text-gray-300" /><p class="text-sm">No Packaging transactions for this date.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(r,i) in pck" :key="i" class="hover:bg-gray-50"><td class="px-3 py-3 text-center">{{ i + 1 }}</td><td class="px-3 py-3">{{ r.entry_date }}</td><td class="px-3 py-3 font-mono">{{ r.from_trace_no || '-' }}</td><td class="px-3 py-3 font-mono">{{ r.to_trace_no }}</td><td class="px-3 py-3 font-mono">{{ r.batch_no || '-' }}</td><td class="px-3 py-3 max-w-xs truncate font-medium">{{ r.material }}</td><td class="px-3 py-3 text-right font-mono">{{ r.in_qty }}</td><td class="px-3 py-3 text-right font-mono">{{ r.out_qty }}</td><td class="px-3 py-3 text-right font-mono" :class="qtyColor(r)">{{ r.balance_supplier }}</td><td class="px-3 py-3 max-w-xs truncate text-xs" :title="r.supplier">{{ r.supplier || '-' }}</td></tr>
          </tbody></table>
        </div>
      </div>
      <!-- SHIPMENT -->
      <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="p-4 border-b flex items-center justify-between"><span class="inline-flex px-3 py-1 bg-blue-600 text-white text-sm font-semibold rounded items-center gap-1"><Icon icon="ri:ship-line" class="w-4 h-4" /> SHIPMENT Transaction</span><span class="text-xs text-gray-500">{{ shipment.length }} records</span></div>
        <div class="overflow-x-auto">
          <table class="w-full"><thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase"><th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Entry Date</th><th class="px-3 py-3 text-left">Prev Trace</th><th class="px-3 py-3 text-left">Trace</th><th class="px-3 py-3 text-left">SO No</th><th class="px-3 py-3 text-left">Material</th><th class="px-3 py-3 text-right">Qty In (MT)</th><th class="px-3 py-3 text-right">Qty Out (MT)</th><th class="px-3 py-3 text-right">Qty Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">Supplier</th></tr></thead>
          <tbody v-if="shipment.length === 0">
            <tr><td colspan="10" class="p-8 text-center text-gray-400"><Icon icon="ri:inbox-line" class="w-10 h-10 mx-auto mb-2 text-gray-300" /><p class="text-sm">No Shipment transactions for this date.</p></td></tr>
          </tbody>
          <tbody v-else class="divide-y text-sm">
            <tr v-for="(r,i) in shipment" :key="i" class="hover:bg-gray-50"><td class="px-3 py-3 text-center">{{ i + 1 }}</td><td class="px-3 py-3">{{ r.entry_date }}</td><td class="px-3 py-3 font-mono">{{ r.from_trace_no || '-' }}</td><td class="px-3 py-3 font-mono">{{ r.to_trace_no }}</td><td class="px-3 py-3 font-mono">{{ r.so_no || '-' }}</td><td class="px-3 py-3 max-w-xs truncate font-medium">{{ r.material }}</td><td class="px-3 py-3 text-right font-mono">{{ r.in_qty }}</td><td class="px-3 py-3 text-right font-mono">{{ r.out_qty }}</td><td class="px-3 py-3 text-right font-mono" :class="qtyColor(r)">{{ r.balance_supplier }}</td><td class="px-3 py-3 max-w-xs truncate text-xs" :title="r.supplier">{{ r.supplier || '-' }}</td></tr>
          </tbody></table>
        </div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import tsReportApi from '@/modules/ts-tsreport/api'
const entryDate = ref(new Date().toISOString().split('T')[0]), loading = ref(false)
const rm = ref([]), wip = ref([]), transfer = ref([]), pck = ref([]), shipment = ref([])
const loadAll = async () => {
  loading.value = true
  try { const r = await tsReportApi.getAllSections({ entry_date: entryDate.value }); const d = r.data?.data || {}; rm.value = d.rm || []; wip.value = d.wip || []; transfer.value = d.transfer || []; pck.value = d.pck || []; shipment.value = d.shipment || [] }
  catch { rm.value = []; wip.value = []; transfer.value = []; pck.value = []; shipment.value = [] }
  finally { loading.value = false }
}
const qtyColor = (row) => {
  const inQty = parseFloat(row.in_qty || row.wip_in || 0), outQty = parseFloat(row.out_qty || row.wip_out || 0), sup = parseFloat(row.balance_supplier || 0), cmp = outQty > 0 ? outQty : inQty
  return cmp > 0 && Math.abs(cmp - sup) < 0.001 ? 'text-green-600' : cmp > 0 ? 'text-red-500' : ''
}
</script>
