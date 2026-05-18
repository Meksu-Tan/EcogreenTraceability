<template>
  <FeatureTableView title="TS Report" subtitle="Summary of Daily Transaction" :tables="tables">
    <template #filters>
      <div class="flex flex-wrap items-end gap-3">
        <label class="block w-full max-w-xs space-y-1 text-sm font-bold text-slate-700">
          <span>Select Entry Date</span>
          <input v-model="entryDate" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20" />
        </label>
        <div class="space-y-1">
          <span class="block text-sm font-bold text-slate-700">Plant</span>
          <PlantSelector @change="fetchRows" />
        </div>
        <span class="inline-flex items-center rounded-md bg-green-50 px-3 py-2 text-xs font-bold text-green-800 ring-1 ring-green-200">
          <i class="fas fa-industry mr-2"></i>{{ plantSelectionStore.selectedPlantName }}
        </span>
        <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" @click="fetchRows">
          <i class="fas fa-globe mr-2"></i> View
        </button>
      </div>
    </template>
  </FeatureTableView>
  <PlantSelectionModal ref="plantSelectionModal" @selected="fetchRows" />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import legacyApi from '@/api/legacy'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import PlantSelectionModal from '@/components/shared/PlantSelectionModal.vue'
import FeatureTableView from '@/views/_shared/FeatureTableView.vue'

const plantSelectionStore = usePlantSelectionStore()
const plantSelectionModal = ref(null)
const entryDate = ref(new Date().toISOString().slice(0, 10))
const loading = ref(false)
const rmRows = ref([])
const wipRows = ref([])
const transferRows = ref([])
const packagingRows = ref([])
const shipmentRows = ref([])

const isAllPlants = computed(() => !plantSelectionStore.selectedPlantId)

const tables = computed(() => [
  {
    title: 'RM Transaction',
    columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Entry Date', 'Prev Trace No', 'Trace No', 'Material', 'Qty In (MT)', 'SLoc', 'Qty Out (MT)', 'Qty Supplier (MT)', 'Supplier / Batch SAP / Qty (MT)'],
    fields: ['__index', ...(isAllPlants.value ? ['plant_code'] : []), 'entry_date', 'from_trace_no', 'to_trace_no', 'material', 'in_qty', 'sloc', 'out_qty', 'balance_supplier', 'supplier'],
    rows: rmRows.value,
    loading: loading.value,
    emptyText: 'No RM transaction data',
  },
  {
    title: 'WIP Transaction',
    columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Entry Date', 'Prev Trace No', 'Trace No', 'Material', 'WIP Out (MT)', 'WIP Section', 'WIP In (MT)', 'WIP Supplier (MT)', 'Supplier / Batch SAP / Qty (MT)'],
    fields: ['__index', ...(isAllPlants.value ? ['plant_code'] : []), 'entry_date', 'from_trace_no', 'to_trace_no', 'material', 'out_qty', 'section', 'in_qty', 'balance_supplier', 'supplier'],
    rows: wipRows.value,
    loading: loading.value,
    emptyText: 'No WIP transaction data',
  },
  {
    title: 'TRANSFER Transaction',
    columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Entry Date', 'Prev Trace No', 'Trace No', 'Material', 'Qty In (MT)', 'SLOC', 'Qty Out (MT)', 'Qty Supplier (MT)', 'Supplier / Batch SAP / Qty (MT)'],
    fields: ['__index', ...(isAllPlants.value ? ['plant_code'] : []), 'entry_date', 'from_trace_no', 'to_trace_no', 'material', 'in_qty', 'sloc', 'out_qty', 'balance_supplier', 'supplier'],
    rows: transferRows.value,
    loading: loading.value,
    emptyText: 'No transfer transaction data',
  },
  {
    title: 'PACKAGING Transaction',
    columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Entry Date', 'Prev Trace No', 'Trace No', 'PPH Batch No', 'Material', 'Qty In (MT)', 'Qty Out (MT)', 'Qty Supplier (MT)', 'Supplier / Batch SAP / Qty (MT)'],
    fields: ['__index', ...(isAllPlants.value ? ['plant_code'] : []), 'entry_date', 'from_trace_no', 'to_trace_no', 'batch_no', 'material', 'in_qty', 'out_qty', 'balance_supplier', 'supplier'],
    rows: packagingRows.value,
    loading: loading.value,
    emptyText: 'No packaging transaction data',
  },
  {
    title: 'SHIPMENT Transaction',
    columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Entry Date', 'Prev Trace No', 'Trace No', 'SO No', 'Material', 'Qty In (MT)', 'Qty Out (MT)', 'Qty Supplier (MT)', 'Supplier / Batch SAP / Qty (MT)'],
    fields: ['__index', ...(isAllPlants.value ? ['plant_code'] : []), 'entry_date', 'from_trace_no', 'to_trace_no', 'so_no', 'material', 'in_qty', 'out_qty', 'balance_supplier', 'supplier'],
    rows: shipmentRows.value,
    loading: loading.value,
    emptyText: 'No shipment transaction data',
  },
])

async function fetchRows() {
  loading.value = true
  try {
    const params = { entry_date: entryDate.value, id_plant: plantSelectionStore.selectedPlantId || 0 }
    const [rm, wip, transfer, packaging, shipment] = await Promise.all([
      legacyApi.tsReport('rm', params),
      legacyApi.tsReport('all', params),
      legacyApi.tsReport('transfer', params),
      legacyApi.tsReport('packaging', params),
      legacyApi.tsReport('shipment', params),
    ])
    rmRows.value = rm.data || []
    wipRows.value = wip.data || []
    transferRows.value = transfer.data || []
    packagingRows.value = packaging.data || []
    shipmentRows.value = shipment.data || []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (!plantSelectionStore.hasSelectedPlant) plantSelectionModal.value?.open()
  else fetchRows()
})
</script>
