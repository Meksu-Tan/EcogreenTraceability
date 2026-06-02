<template>
  <div>
    <DataTable
      :columns="columns"
      :data="store.warehouses"
      :loading="store.loading"
      row-key="id_warehouse"
      @edit="onEdit"
      @toggle-status="onToggle"
    />
    <StorageWarehouseModal v-model="showModal" :edit-data="editRow" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import StorageWarehouseModal from './StorageWarehouseModal.vue'
import { useSetupStorageStore } from '@/modules/m-storage/stores'
import { useToastStore } from '@/stores/toast'

defineExpose({ openModal })

const store      = useSetupStorageStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'id_batch',    label: 'ID Batch' },
  { key: 'code',        label: 'Kode' },
  { key: 'description', label: 'Deskripsi' },
  { key: 'status',      label: 'Status' },
]

onMounted(async () => {
  try {
    await store.fetchWarehouses()
  } catch (error) {
    toast.error('Failed to fetch warehouses:', error)
  }
})

function openModal() { editRow.value = null; showModal.value = true }
function onEdit(row) { editRow.value = row; showModal.value = true }

async function onToggle(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} warehouse "${row.description}"?`)) return
  const r = await store.toggleWarehouse(row.id_warehouse, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editRow.value ? await store.editWarehouse(editRow.value.id_warehouse, data) : await store.createWarehouse(data)
    if (r.status===1) { toast.success(r.message); showModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>