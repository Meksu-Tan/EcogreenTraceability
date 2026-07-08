<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Warehouse</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Warehouse</span>
        </div>
      </div>
      <VBtn id="btn-tambah-warehouse" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-database-2-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Warehouse</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.warehouses"
          :loading="store.loading"
          row-key="id"
        >
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

    <WarehouseModal v-model="showModal" :edit-data="editData" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import WarehouseModal from './WarehouseModal.vue'
import { useSetupWarehouseStore } from '@/modules/m-tank/stores'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store = useSetupWarehouseStore()
const toast = useToastStore()
const confirmStore = useConfirmStore()
const showModal = ref(false)
const editData = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'id',          label: 'ID' },
  { key: 'code',        label: 'Code' },
  { key: 'description', label: 'Description' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(async () => {
  try {
    await store.fetchWarehouses()
  } catch (error) {
    toast.error('Failed to fetch warehouses:', error)
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
    message: `${row.status == 1 ? 'Deactivate' : 'Activate'} warehouse "${row.description}"?`
  })
  if (!isConfirmed) return
  const r = await store.toggleWarehouse(row.id, row.status)
  if (r.status === 1) {
    toast.success(r.message)
  } else {
    toast.error(r.message)
  }
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editWarehouse(editData.value.id, data) : await store.createWarehouse(data)
    if (r.status === 1) {
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
