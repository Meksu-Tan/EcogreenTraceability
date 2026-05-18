<template>
  <TransactionShell
    title="Stock Transfer"
    entry-label="New Transfer Entry"
    entry-icon="fa-edit"
    :columns="columns"
    :fields="fields"
    :rows="rows"
    :loading="loading"
    row-key="idHead"
    empty-text="No transfer entries found"
    :plant-selection-store="plantSelectionStore"
    @entry="openModal('entry', 'New Transfer Entry')"
    @plant-change="fetchData"
  />

  <TransactionModal v-if="activeModal" :modal="activeModal" @close="activeModal = null" />
  <PlantSelectionModal ref="plantSelectionModal" @selected="fetchData" />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import legacyApi from '@/api/legacy'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import PlantSelectionModal from '@/components/shared/PlantSelectionModal.vue'
import TransactionShell from './_TransactionShell.vue'
import TransactionModal from './_TransactionModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const plantSelectionModal = ref(null)
const activeModal = ref(null)
const rows = ref([])
const loading = ref(false)

const isAllPlants = computed(() => !plantSelectionStore.selectedPlantId)

const columns = computed(() => [
  'No',
  ...(isAllPlants.value ? ['Plant'] : []),
  'Action', 'Entry Date', 'Matl Doc', 'Trace No', 'Material',
  'Sloc (From >>> To)', 'Init Material (MT)', 'Init Supplier (MT)',
  'On-Hand Material (MT)', 'On-Hand Supplier (MT)',
  'Supplier / Batch SAP / Init Qty (MT) / TO Sloc On-hand Qty (MT)',
])

const fields = computed(() => [
  '__index',
  ...(isAllPlants.value ? ['plant_code'] : []),
  (row) => ({ type: 'button', label: 'Detail', icon: 'fa-eye', onClick: openDetail }),
  'entry_date',
  'material_document',
  'trace_no',
  'material',
  'sloc',
  'init_qty',
  'balance_supplier',
  'qty',
  'balance_supplier',
  'supplier',
])

async function fetchData() {
  loading.value = true
  try {
    const response = await legacyApi.transferList({ id_plant: plantSelectionStore.selectedPlantId || 0 })
    rows.value = response.data || []
  } finally {
    loading.value = false
  }
}

function openModal(kind, title) {
  activeModal.value = { kind, title, subtitle: plantSelectionStore.selectedPlantName, module: 'transfer' }
}

function openDetail(row) {
  activeModal.value = {
    kind: 'doc',
    title: `Transfer Detail - ${row.trace_no || row.entry_no || ''}`,
    subtitle: plantSelectionStore.selectedPlantName,
    module: 'transfer',
  }
}

onMounted(() => {
  if (!plantSelectionStore.hasSelectedPlant) plantSelectionModal.value?.open()
  else fetchData()
})
</script>
