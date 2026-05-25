<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Supplier</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Supplier</span>
        </div>
      </div>
      <button 
        id="btn-tambah-supplier" 
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95" 
        @click="openModal"
      >
        <i class="fas fa-plus"></i> Tambah Supplier
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/30">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-diagnoses text-green-600"></i>
          Data Supplier
        </h4>
      </div>
      <div class="p-6">
        <DataTable
          :columns="columns"
          :data="store.suppliers"
          :loading="store.loading"
          row-key="id_supplier"
          @edit="onEdit"
          @toggle-status="onToggle"
        >
          <template #cell-type="{ value }">
            <span class="text-sm font-medium text-slate-500">{{ value }}</span>
          </template>
        </DataTable>
      </div>
    </div>

    <SupplierModal
      v-model="showModal"
      :edit-data="editRow"
      :loading="submitting"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/components/shared/DataTable.vue'
import SupplierModal from './SupplierModal.vue'
import { useSetupSupplierStore } from '@/modules/m-supplier/stores'
import { useToastStore } from '@/stores/toast'

const store      = useSetupSupplierStore()
const toast      = useToastStore()
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
    console.error('Failed to fetch suppliers:', error)
  }
})

function openModal() { editRow.value = null; showModal.value = true }
function onEdit(row) { editRow.value = row; showModal.value = true }

async function onToggle(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} supplier "${row.description}"?`)) return
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
