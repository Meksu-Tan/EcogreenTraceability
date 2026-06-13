<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Supplier</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Supplier</span>
        </div>
      </div>
      <VBtn id="btn-tambah-supplier" color="primary" prepend-icon="ri-add-line" @click="openModal">Add Supplier</VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-truck-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Supplier</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.suppliers"
          :loading="store.loading"
          row-key="id_supplier"
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

    <SupplierModal v-model="showModal" :edit-data="editRow" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import SupplierModal from './SupplierModal.vue'
import { useSetupSupplierStore } from '@/modules/m-supplier/stores'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'

const store      = useSetupSupplierStore()
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
    await store.fetchSuppliers()
  } catch (error) {
    toast.error('Failed to fetch suppliers:', error)
  }
})

function openModal() { editRow.value = null; showModal.value = true }
function onEdit(row) { editRow.value = row; showModal.value = true }

async function onToggle(row) {
  const isConfirmed = await confirmStore.show({
    message: `${row.status==1?'Deactivate':'Activate'} supplier "${row.description}"?`
  })
  if (!isConfirmed) return
  const r = await store.toggleSupplier(row.id_supplier, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editRow.value ? await store.editSupplier(editRow.value.id_supplier, data) : await store.createSupplier(data)
    if (r.status===1) { toast.success(r.message); showModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>