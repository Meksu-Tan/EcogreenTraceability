<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Tank</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Tank</span>
        </div>
      </div>
      <div class="d-flex gap-2">
        <VBtn
          id="btn-sync-tank"
          color="primary"
          variant="tonal"
          :loading="syncing"
          prepend-icon="ri-refresh-line"
          @click="onSync"
        >
          Update Data
        </VBtn>
        <VBtn id="btn-tambah-tank" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
      </div>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-water-flash-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Tank</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.tanks"
          :loading="store.loading"
          row-key="id"
        >
          <template #cell-id="{ row }">
            <span class="text-caption text-medium-emphasis" style="font-family: var(--font-mono); font-weight: 600;">{{ row.id }}</span>
          </template>
          <template #cell-plant="{ row }">
            <span class="font-weight-bold">{{ row.plant_code }}</span>
            <span class="mx-2 text-disabled">|</span>
            <span>{{ row.plant_name }}</span>
          </template>
          <template #cell-tank_height="{ row }">
            <span class="text-body-2 font-weight-semibold">{{ parseFloat(row.tank_height).toLocaleString() }}</span>
            <span class="text-caption text-medium-emphasis ms-1">m</span>
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

    <TankModal v-model="showModal" :edit-data="editData" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import TankModal from './TankModal.vue'
import { useSetupTankStore } from '@/modules/m-tank/stores'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store      = useSetupTankStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const showModal  = ref(false)
const editData   = ref(null)
const submitting = ref(false)
const syncing    = ref(false)

const columns = [
  { key: 'id',          label: 'ID' },
  { key: 'plant',       label: 'Plant Code | Name' },
  { key: 'tank_number', label: 'Tank Number' },
  { key: 'tank_height', label: 'Tank Height' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(async () => {
  try {
    await store.fetchTanks()
  } catch (error) {
    toast.error('Failed to fetch tanks:', error)
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
  const isConfirmed = await confirmStore.show({
    title: 'Confirmation',
    message: `${row.status==1?'Deactivate':'Activate'} tank "${row.tank_number}" for plant "${row.plant_name}"?`
  })
  if (!isConfirmed) return
  const r = await store.toggleTank(row.id, row.status)
  if (r.status === 1) {
    toast.success(r.message)
  } else {
    toast.error(r.message)
  }
}

async function onSync() {
  syncing.value = true
  try {
    const r = await store.syncTanks()
    if (r.status === 1) {
      toast.success(r.message)
    } else {
      toast.error(r.message)
    }
  } catch (error) {
    toast.error('Failed to sync tanks', error)
  } finally {
    syncing.value = false
  }
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editTank(editData.value.id, data) : await store.createTank(data)
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