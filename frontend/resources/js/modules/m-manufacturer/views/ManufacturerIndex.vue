<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Manufacturer</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Manufacturer</span>
        </div>
      </div>
      <VBtn id="btn-tambah-manufacturer" color="primary" prepend-icon="ri-add-line" @click="openModal">
        Add Manufacturer
      </VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-file-list-3-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Manufacturer</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.manufacturers"
          :loading="store.loading"
          row-key="id_manufacturer"
          :show-top-info="false"
          @edit="onEdit"
          @toggle-status="onToggle"
        >
          <template #cell-type="{ value }">
            <span class="text-body-2 text-medium-emphasis">{{ value }}</span>
          </template>
        </DataTable>
      </VCardText>
    </VCard>

    <ManufacturerModal v-model="showModal" :edit-data="editRow" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import ManufacturerModal from './ManufacturerModal.vue'
import { useSetupManufacturerStore } from '@/modules/m-manufacturer/stores'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store      = useSetupManufacturerStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code',        label: 'Code' },
  { key: 'batch_code',  label: 'Batch Code' },
  { key: 'description', label: 'Description' },
  { key: 'type',        label: 'Sloc' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(async () => {
  try {
    await store.fetchManufacturers()
  } catch (error) {
    toast.error('Failed to fetch manufacturers:', error)
  }
})

function openModal() { editRow.value = null; showModal.value = true }
function onEdit(row) { editRow.value = row; showModal.value = true }

async function onToggle(row) {
  const isConfirmed = await confirmStore.show({ message: `${row.status==1?'Deactivate':'Activate'} manufacturer "${row.description}"?` })
  if (!isConfirmed) return
  const r = await store.toggleManufacturer(row.id_manufacturer, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editRow.value ? await store.editManufacturer(editRow.value.id_manufacturer, data) : await store.createManufacturer(data)
    if (r.status===1) { toast.success(r.message); showModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>