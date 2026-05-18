<template>
  <TransactionShell
    title="Blending"
    entry-label="New Blending Entry"
    entry-icon="fa-edit"
    :columns="columns"
    :fields="fields"
    :rows="rows"
    :loading="loading"
    row-key="idHead"
    empty-text="No blending entries found"
    :plant-selection-store="plantSelectionStore"
    @entry="openModal('entry', 'New Blending Entry')"
    @plant-change="fetchData"
  />

  <TransactionModal v-if="activeModal" :modal="activeModal" @close="activeModal = null" />
  <PlantSelectionModal ref="plantSelectionModal" @selected="fetchData" />
</template>

<script setup>
import { onMounted, ref } from 'vue'
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

const columns = [
  'No', 'Action', 'Entry Date', 'Matl Doc', 'Trace No', 'Material',
  'Blending Source', 'Sloc', 'Init Material (MT)', 'Init Supplier (MT)',
  'On-Hand (MT)', 'Supplier / Batch SAP / Init Qty (MT) / Remark',
]

const fields = [
  '__index',
  (row) => ({ type: 'button', label: 'Detail', icon: 'fa-eye', onClick: openDetail }),
  'entry_date',
  'material_document',
  'trace_no',
  'material',
  'from_trace_no',
  'sloc',
  'init_qty',
  'balance_supplier',
  'qty',
  'supplier',
]

async function fetchData() {
  loading.value = true
  try {
    const response = await legacyApi.blendingList({ id_plant: plantSelectionStore.selectedPlantId })
    rows.value = response.data || []
  } finally {
    loading.value = false
  }
}

function openModal(kind, title) {
  activeModal.value = { kind, title, subtitle: plantSelectionStore.selectedPlantName, module: 'blending' }
}

function openDetail(row) {
  activeModal.value = {
    kind: 'doc',
    title: `Blending Detail - ${row.trace_no || row.entry_no || ''}`,
    subtitle: plantSelectionStore.selectedPlantName,
    module: 'blending',
  }
}

onMounted(() => {
  if (!plantSelectionStore.hasSelectedPlant) plantSelectionModal.value?.open()
  else fetchData()
})
</script>
