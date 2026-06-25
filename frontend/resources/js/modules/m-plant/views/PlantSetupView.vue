<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Plant</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Plant</span>
        </div>
      </div>
      <VBtn id="btn-tambah-plant" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-building-4-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Plant</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="sortedPlants"
          :loading="store.loading"
          row-key="id_plant"
        >
          <template #cell-code_internal="{ row }">
            <span class="text-caption text-medium-emphasis" style="font-family: var(--font-mono);">{{ row.code || '-' }}</span>
          </template>
          <template #cell-code="{ row }">
            <span class="font-weight-bold">{{ row.code_2 }}</span>
            <span class="mx-2 text-disabled">|</span>
            <span>{{ row.code_3 }}</span>
          </template>
          <template #cell-status="{ row }">
            <VChip
              :color="row.status == 1 ? 'success' : 'error'"
              variant="tonal"
              size="x-small"
              :prepend-icon="row.status == 1 ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'"
            >
              {{ row.status == 1 ? 'Active' : 'Inactive' }}
            </VChip>
          </template>
          <template #actions="{ row }">
            <div class="d-flex justify-center gap-1">
              <VBtn size="x-small" icon="ri-edit-line" color="primary" variant="tonal" @click.stop="onEdit(row)" />
              <VBtn
                size="x-small"
                :icon="row.status == 1 ? 'ri-close-line' : 'ri-check-line'"
                :color="row.status == 1 ? 'error' : 'success'"
                variant="tonal"
                @click.stop="onToggle(row)"
              />
            </div>
          </template>
        </DataTable>
      </VCardText>
    </VCard>

    <PlantModal v-model="showModal" :edit-data="editData" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import PlantModal from './PlantModal.vue'
import { useSetupPlantStore } from '@/stores/plant.js'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store      = useSetupPlantStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const plantOrder = ['EOB-1', 'EOB-2', 'EOB-3', 'EOB-5', 'EOMB']

const sortedPlants = computed(() => {
  const list = store.plants || []
  return [...list].sort((a, b) => {
    const aCode = (a.code_3 || '').toUpperCase()
    const bCode = (b.code_3 || '').toUpperCase()
    const aIdx = plantOrder.indexOf(aCode)
    const bIdx = plantOrder.indexOf(bCode)
    if (aIdx !== -1 && bIdx !== -1) return aIdx - bIdx
    if (aIdx !== -1) return -1
    if (bIdx !== -1) return 1
    return aCode.localeCompare(bCode)
  })
})

const showModal  = ref(false)
const editData   = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code_internal', label: 'ID' },
  { key: 'code',        label: 'Code | Name' },
  { key: 'description', label: 'Description' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(async () => {
  try {
    await store.fetchPlants()
  } catch (error) {
    toast.error('Failed to fetch plants:', error)
  }
})

function openModal() {
  editData.value = null
  showModal.value = true
}

function onEdit(row) {
  editData.value = row
  showModal.value = true
}

async function onToggle(row) {
  const isConfirmed = await confirmStore.show({ message: `${row.status==1?'Deactivate':'Activate'} plant "${row.description}"?` })
  if (!isConfirmed) return
  const r = await store.togglePlant(row.id_plant, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editPlant(editData.value.id_plant, data) : await store.createPlant(data)
    if (r.status===1) {
      toast.success(r.message)
      showModal.value = false
    } else {
      toast.error(r.message)
    }
  } finally {
    submitting.value = false
  }
}
</script>