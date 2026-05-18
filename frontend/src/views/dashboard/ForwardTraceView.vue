<template>
  <FeatureTableView
    title="Forward Trace"
    banner="EUDR-TS FORWARD TRACING"
    :tables="tables"
    :active-modal="activeModal"
    @close-modal="activeModal = null"
  >
    <template #toolbar>
      <span class="inline-flex items-center rounded-md bg-green-50 px-3 py-2 text-xs font-bold text-green-800 ring-1 ring-green-200">
        <i class="fas fa-industry mr-2"></i>{{ plantSelectionStore.selectedPlantName }}
      </span>
      <PlantSelector @change="fetchRows" />
      <button class="rounded-md bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700" @click="fetchRows">
        <i class="fas fa-sync mr-2"></i> Refresh
      </button>
    </template>
    <template #modal>
      <TraceModal title="Forward Trace Detail" :rows="detailRows" :loading="detailLoading" />
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
import TraceModal from './_TraceModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const plantSelectionModal = ref(null)
const activeModal = ref(null)
const rows = ref([])
const loading = ref(false)
const detailRows = ref([])
const detailLoading = ref(false)

const isAllPlants = computed(() => !plantSelectionStore.selectedPlantId)

const tables = computed(() => [{
  columns: ['No', ...(isAllPlants.value ? ['Plant'] : []), 'Action', 'RM Batch No', 'Entry Date', 'Matl Doc', 'Material', 'Tank', 'Init (MT)', 'On-Hand (MT)', 'Supplier / Batch SAP / Init Qty (MT) / Remark', 'Created at', 'Created by'],
  fields: [
    '__index',
    ...(isAllPlants.value ? ['plant_code'] : []),
    (row) => ({ type: 'button', label: 'View', icon: 'fa-eye', onClick: openTrace }),
    'trace_no',
    'entry_date',
    'material_document',
    'material',
    'tf_number',
    'init_qty',
    'qty',
    'supplier',
    'created_at',
    'created_by',
  ],
  rows: rows.value,
  loading: loading.value,
  rowKey: 'id_balance_head',
  emptyText: 'No forward trace data found',
}])

async function fetchRows() {
  loading.value = true
  try {
    const response = await legacyApi.forwardList({ id_plant: plantSelectionStore.selectedPlantId || 0 })
    rows.value = response.data || []
  } finally {
    loading.value = false
  }
}

async function openTrace(row) {
  activeModal.value = { title: `Forward Trace Detail - ${row.trace_no}` }
  detailRows.value = []
  detailLoading.value = true
  try {
    const response = await legacyApi.forwardTrace(row.trace_no, {
      idMaterial: row.id_material,
      id_plant: plantSelectionStore.selectedPlantId || 0,
    })
    detailRows.value = response.data || []
  } finally {
    detailLoading.value = false
  }
}

onMounted(() => {
  if (!plantSelectionStore.hasSelectedPlant) plantSelectionModal.value?.open()
  else fetchRows()
})
</script>
