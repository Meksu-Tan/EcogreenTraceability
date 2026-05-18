<template>
  <FeatureTableView
    title="RM Report"
    subtitle="Summary of Raw Material to Product"
    :tables="tables"
    :active-modal="activeModal"
    @close-modal="activeModal = null"
  >
    <template #filters>
      <select v-model="entryYear" class="w-full max-w-40 rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
      </select>
      <button class="ml-2 rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" @click="fetchSummary">
        <i class="fas fa-globe mr-2"></i> View
      </button>
    </template>
    <template #toolbar>
      <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700" @click="fetchSummary">
        <i class="fas fa-sync mr-2"></i> Refresh
      </button>
    </template>
    <template #modal>
      <FeatureTableView title="Detail RM Traceability" :tables="detailTables" />
    </template>
  </FeatureTableView>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import legacyApi from '@/api/legacy'
import FeatureTableView from '@/views/_shared/FeatureTableView.vue'

const entryYear = ref(new Date().getFullYear())
const years = Array.from({ length: 6 }, (_, i) => new Date().getFullYear() - i)
const activeModal = ref(null)
const rows = ref([])
const loading = ref(false)
const detailLoading = ref(false)
const tankRows = ref([])
const warehouseRows = ref([])
const adjustmentRows = ref([])

const tables = computed(() => [{
  columns: ['No', 'Action', 'Trace No', 'Entry Date', 'Matl Doc', 'PurchO', 'Material', 'Sloc', 'Init (MT)', 'On-Hand (MT)', 'Supplier / Batch SAP / Init Qty (MT)', 'On-WIP (MT)', 'On-PRD (MT)', 'On-ADJOUT (MT)'],
  fields: [
    '__index',
    (row) => ({ type: 'button', label: 'Detail', icon: 'fa-eye', onClick: openDetail }),
    'trace_no',
    'entry_date',
    'material_document',
    'po_so',
    'material',
    'tf_number',
    'init_qty',
    'qty',
    'supplier',
    'qty_tank',
    'qty_warehouse',
    'qty_adjustment',
  ],
  rows: rows.value,
  loading: loading.value,
  rowKey: 'id_balance_head',
  emptyText: 'No RM to product data found',
}])

const detailTables = computed(() => [
  {
    title: 'On Tank',
    columns: ['No', 'Sloc', 'Material', 'In Qty (MT)', 'Out Qty (MT)', 'Balance (MT)'],
    fields: ['__index', 'sloc', 'material', 'in_qty', 'out_qty', 'balance'],
    rows: tankRows.value,
    loading: detailLoading.value,
    emptyText: 'No on-tank data',
  },
  {
    title: 'On Warehouse',
    columns: ['No', 'Warehouse', 'Material', 'In Qty (MT)', 'Out Qty (MT)', 'Balance (MT)', 'Shipment'],
    fields: ['__index', 'sloc', 'material', 'in_qty', 'out_qty', 'balance', 'shipment'],
    rows: warehouseRows.value,
    loading: detailLoading.value,
    emptyText: 'No warehouse data',
  },
  {
    title: 'On Adj Out',
    columns: ['No', 'Sloc', 'Material', 'In Qty (MT)', 'Out Qty (MT)', 'Balance (MT)'],
    fields: ['__index', 'sloc', 'material', 'in_qty', 'out_qty', 'balance'],
    rows: adjustmentRows.value,
    loading: detailLoading.value,
    emptyText: 'No adjustment data',
  },
])

async function fetchSummary() {
  loading.value = true
  try {
    const response = await legacyApi.rmReportSummary({ selectedYear: entryYear.value })
    rows.value = response.data || []
  } finally {
    loading.value = false
  }
}

async function openDetail(row) {
  const batchSap = row.batch_sap
  activeModal.value = { title: `Detail RM Traceability - ${batchSap || row.trace_no}` }
  tankRows.value = []
  warehouseRows.value = []
  adjustmentRows.value = []
  detailLoading.value = true
  try {
    const params = { batchSap }
    const [tank, warehouse, adjustment] = await Promise.all([
      legacyApi.rmReportTank(params),
      legacyApi.rmReportWarehouse(params),
      legacyApi.rmReportAdjustmentOut(params),
    ])
    tankRows.value = tank.data || []
    warehouseRows.value = warehouse.data || []
    adjustmentRows.value = adjustment.data || []
  } finally {
    detailLoading.value = false
  }
}

watch(entryYear, fetchSummary)
onMounted(fetchSummary)
</script>
