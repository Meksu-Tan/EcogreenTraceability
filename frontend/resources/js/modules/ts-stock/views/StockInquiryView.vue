<template>
  <div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
      <h1 class="text-xl font-bold text-slate-800 mb-4">Stock On-Hand (WIP / Warehouse)</h1>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">On-Hand Report Type</label>
          <select v-model="reportType" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" @change="onReportTypeChange">
            <option value="detail">- Detail Per Material -</option>
            <option value="summary">- Summary All Material -</option>
          </select>
        </div>
      </div>
      <!-- Detail Filters -->
      <div v-if="reportType === 'detail'" class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-4">
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Start Date</label><input v-model="detailFilters.startDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">End Date</label><input v-model="detailFilters.endDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Type</label><select v-model="detailFilters.stockType" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" @change="onMaterialSearch"><option value="WIP">WIP</option><option value="WH">WH</option></select></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material</label><input v-model="detailFilters.materialSearch" type="text" placeholder="Search..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" @input="onMaterialSearch" />
          <select v-model="detailFilters.materialId" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">-- Select Material --</option><option v-for="m in materialOptions" :key="m.id_material" :value="m.id_material">{{ m.material }}</option></select>
        </div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc</label><select v-model="detailFilters.sloc" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">All SLOC</option><option v-for="s in slocOptions" :key="s.id_plant" :value="s.id_plant">{{ s.description }}</option></select></div>
        <div class="flex items-end"><button @click="onView" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 flex items-center justify-center gap-2"><Icon icon="ri:globe-line" class="w-4 h-4" /> View</button></div>
      </div>
      <!-- Summary Filters -->
      <div v-if="reportType === 'summary'" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Start Date</label><input v-model="summaryFilters.startDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">End Date</label><input v-model="summaryFilters.endDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc</label><select v-model="summaryFilters.sloc" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">All SLOC</option><option v-for="s in slocOptions" :key="s.id_plant" :value="s.id_plant">{{ s.description }}</option></select></div>
        <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc Type</label><select v-model="summaryFilters.slocType" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="SUMMARY_WIP">WIP</option><option value="SUMMARY_WH">WAREHOUSE</option></select></div>
        <div class="flex items-end"><button @click="onView" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 flex items-center justify-center gap-2"><Icon icon="ri:globe-line" class="w-4 h-4" /> View</button></div>
      </div>
    </div>
    <!-- Stock Detail Table -->
    <div v-if="reportType === 'detail'" class="bg-white rounded-lg shadow-sm border mb-6">
      <div class="p-4 border-b font-semibold text-sm">Stock Detail</div>
      <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
      <template v-else><div class="overflow-x-auto"><table class="w-full">
        <thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase">
          <th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Stock Date</th><th class="px-3 py-3 text-left">Description</th>
          <th class="px-3 py-3 text-right">In (MT)</th><th class="px-3 py-3 text-left">Sloc</th><th class="px-3 py-3 text-right">Out (MT)</th>
          <th class="px-3 py-3 text-right">Balance Material (MT)</th><th class="px-3 py-3 text-right">Balance Supplier (MT)</th>
          <th class="px-3 py-3 text-left max-w-xs">ID / Supplier / Batch SAP / Balance (MT) / Trace</th>
        </tr></thead>
        <tbody v-if="stockData.length === 0">
          <tr><td colspan="9" class="p-8 text-center text-gray-400">No data.</td></tr>
        </tbody>
        <tbody v-else class="divide-y text-sm">
          <tr v-for="(row, i) in stockData" :key="row.id_balance_head" class="hover:bg-gray-50">
            <td class="px-3 py-3 text-center text-gray-500">{{ i + 1 }}</td>
            <td class="px-3 py-3">{{ row.entry_date }}</td>
            <td class="px-3 py-3 font-medium max-w-xs truncate" :title="row.material">{{ row.material || row.description }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ formatQty(row.in_qty) }}</td>
            <td class="px-3 py-3">{{ row.tank || row.sloc || '-' }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ formatQty(row.out_qty) }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold" :class="bc(row)">{{ formatQty(row.balance || row.current_qty || row.qty) }}</td>
            <td class="px-3 py-3 text-right font-mono" :class="bc(row)">{{ row.balance_supplier || '0.000' }}</td>
            <td class="px-3 py-3 max-w-xs truncate text-xs" :title="row.supplier || row.supplier_details">{{ row.supplier || row.supplier_details || '-' }}</td>
          </tr>
        </tbody>
      </table></div></template>
    </div>
    <!-- RM Storage Table -->
    <div v-if="reportType === 'detail' && showRmTable" class="bg-white rounded-lg shadow-sm border mb-6">
      <div class="p-4 border-b font-semibold text-sm">RM Storage Detail</div>
      <div class="overflow-x-auto"><table class="w-full">
        <thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase">
          <th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Stock Date</th><th class="px-3 py-3 text-left">Description</th>
          <th class="px-3 py-3 text-right">In (MT)</th><th class="px-3 py-3 text-left">Sloc</th><th class="px-3 py-3 text-right">Out (MT)</th>
          <th class="px-3 py-3 text-right">Balance Material (MT)</th><th class="px-3 py-3 text-right">Balance Supplier (MT)</th>
          <th class="px-3 py-3 text-left max-w-xs">ID / Batch SAP / Balance (MT) / Trace</th>
        </tr></thead>
        <tbody v-if="rmData.length === 0">
          <tr><td colspan="9" class="p-8 text-center text-gray-400">No RM storage data.</td></tr>
        </tbody>
        <tbody v-else class="divide-y text-sm">
          <tr v-for="(row, i) in rmData" :key="i" class="hover:bg-gray-50">
            <td class="px-3 py-3 text-center text-gray-500">{{ i + 1 }}</td>
            <td class="px-3 py-3">{{ row.entry_date }}</td>
            <td class="px-3 py-3 font-medium max-w-xs truncate">{{ row.material }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ formatQty(row.in_qty) }}</td>
            <td class="px-3 py-3">{{ row.sloc || '-' }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ formatQty(row.out_qty) }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold" :class="bc(row)">{{ formatQty(row.balance || row.qty) }}</td>
            <td class="px-3 py-3 text-right font-mono" :class="bc(row)">{{ row.balance_supplier || '0.000' }}</td>
            <td class="px-3 py-3 max-w-xs truncate text-xs">{{ row.supplier || '-' }}</td>
          </tr>
        </tbody>
      </table></div>
    </div>
    <!-- Summary Table -->
    <div v-if="reportType === 'summary'" class="bg-white rounded-lg shadow-sm border">
      <div class="p-4 border-b font-semibold text-sm">Stock Summary</div>
      <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
      <template v-else><div class="overflow-x-auto"><table class="w-full">
        <thead><tr class="bg-gray-50 border-b text-xs font-semibold text-gray-500 uppercase">
          <th class="px-3 py-3 text-center w-12">No</th><th class="px-3 py-3 text-left">Stock Date</th><th class="px-3 py-3 text-left">Description</th>
          <th class="px-3 py-3 text-right">Init Bal (MT)</th><th class="px-3 py-3 text-right">Total In (MT)</th><th class="px-3 py-3 text-left">Sloc</th>
          <th class="px-3 py-3 text-right">Total Out (MT)</th><th class="px-3 py-3 text-right">Bal Material (MT)</th>
          <th class="px-3 py-3 text-right">Bal Supplier (MT)</th><th class="px-3 py-3 text-left max-w-xs">ID / Supplier / Batch SAP / Balance (MT) / Trace</th>
        </tr></thead>
        <tbody v-if="summaryData.length === 0">
          <tr><td colspan="10" class="p-8 text-center text-gray-400">No data.</td></tr>
        </tbody>
        <tbody v-else class="divide-y text-sm">
          <tr v-for="(row, i) in summaryData" :key="i" class="hover:bg-gray-50">
            <td class="px-3 py-3 text-center text-gray-500">{{ i + 1 }}</td>
            <td class="px-3 py-3">{{ row.entry_date }}</td>
            <td class="px-3 py-3 font-medium max-w-xs truncate">{{ row.material }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ row.init_balance || '0.000' }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ row.in || '0.000' }}</td>
            <td class="px-3 py-3">{{ row.sloc || '-' }}</td>
            <td class="px-3 py-3 text-right font-mono">{{ row.out || '0.000' }}</td>
            <td class="px-3 py-3 text-right font-mono font-semibold" :class="sc(row)">{{ row.last_balance || '0.000' }}</td>
            <td class="px-3 py-3 text-right font-mono" :class="sc(row)">{{ row.balance_supplier || '0.000' }}</td>
            <td class="px-3 py-3 max-w-xs truncate text-xs">{{ row.supplier || '-' }}</td>
          </tr>
        </tbody>
      </table></div></template>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useStockStore } from '../stores/stockStore'
import stockApi from '../api'
const stockStore = useStockStore()
const reportType = ref('detail'), loading = ref(false), resultVisible = ref(false), showRmTable = ref(false), materialOptions = ref([]), slocOptions = ref([])
const stockData = ref([]), rmData = ref([]), summaryData = ref([])
const detailFilters = reactive({ startDate: '', endDate: '', stockType: 'WIP', materialSearch: '', materialId: '', sloc: '' })
const summaryFilters = reactive({ startDate: '', endDate: '', sloc: '', slocType: 'SUMMARY_WIP' })
onMounted(async () => {
  const d = new Date(), y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), day = String(d.getDate()).padStart(2, '0')
  detailFilters.startDate = `${y}-${m}-01`; detailFilters.endDate = `${y}-${m}-${day}`
  summaryFilters.startDate = `${y}-${m}-01`; summaryFilters.endDate = `${y}-${m}-${day}`
  try { const r = await stockApi.getActiveSlocs(); slocOptions.value = r.data?.data || [] } catch { slocOptions.value = [] }
  onMaterialSearch()
})
const onReportTypeChange = () => { showRmTable.value = false; stockData.value = []; rmData.value = []; summaryData.value = [] }
const onMaterialSearch = async () => { try { await stockStore.fetchActiveMaterials({ search: detailFilters.materialSearch, type: detailFilters.stockType }); materialOptions.value = stockStore.activeMaterials || [] } catch { materialOptions.value = [] } }
const onView = async () => {
  resultVisible.value = true; loading.value = true
  try {
    if (reportType.value === 'detail') {
      const p = {}; if (detailFilters.startDate) p.date_from = detailFilters.startDate; if (detailFilters.endDate) p.date_to = detailFilters.endDate; if (detailFilters.materialId) p.material_id = detailFilters.materialId; if (detailFilters.sloc) p.storage_id = detailFilters.sloc
      await stockStore.fetchStock(p); stockData.value = stockStore.stockData || []
      const sel = materialOptions.value.find(m => m.id_material === detailFilters.materialId)
      showRmTable.value = sel && sel.material && sel.material.includes('/RM)')
      if (showRmTable.value) rmData.value = stockData.value.filter(r => r.material && r.material.includes('/RM)'))
    } else {
      await stockStore.fetchStock({}); const raw = stockStore.stockData || []
      summaryData.value = raw.map(r => ({ entry_date: r.entry_date, material: r.material, sloc: r.tank || r.sloc, init_balance: r.init_qty || r.qty, in: r.in_qty, out: r.out_qty, last_balance: r.current_qty || r.qty, balance_supplier: r.balance_supplier || '0.000', supplier: r.supplier || r.supplier_details }))
    }
  } catch { stockData.value = []; summaryData.value = [] } finally { loading.value = false }
}
const formatQty = (q) => parseFloat(q || 0).toFixed(3)
const bc = (row) => { const bal = parseFloat(row.balance || row.current_qty || row.qty || 0), sup = parseFloat(row.balance_supplier || 0); return Math.abs(bal - sup) < 0.001 ? 'text-green-600' : 'text-red-500' }
const sc = (row) => { const bal = parseFloat(row.last_balance || 0), sup = parseFloat(row.balance_supplier || 0); return Math.abs(bal - sup) < 0.001 ? 'text-green-600' : 'text-red-500' }
</script>
