<template>
  <div>
    <!-- Section header -->
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h1 style="font-size:18px;font-weight:700;color:var(--text-main);margin:0 0 4px;">Setup Supplier</h1>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item">TS Setup</div>
          <div class="breadcrumb-item" style="color:var(--primary);">Supplier</div>
        </div>
      </div>
      <button id="btn-tambah-supplier" class="btn btn-primary" @click="openModal">
        <i class="fas fa-plus"></i> Tambah Supplier
      </button>
    </div>

    <div class="card card-primary">
      <div class="card-header">
        <h4><i class="fas fa-diagnoses" style="color:var(--primary);margin-right:8px;"></i>Data Supplier</h4>
      </div>
      <div class="card-body">
        <DataTable
          :columns="columns"
          :data="store.suppliers"
          :loading="store.loading"
          row-key="id_supplier"
          @edit="onEdit"
          @toggle-status="onToggle"
        >
          <template #cell-sloc="{ value }">
            <span style="font-size:12px;color:var(--text-muted);">{{ value }}</span>
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
import { useSetupSupplierStore } from '@/stores/setupSupplier'
import { useToastStore } from '@/stores/toast'

const store      = useSetupSupplierStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code',        label: 'Kode' },
  { key: 'description', label: 'Deskripsi' },
  { key: 'batch_code',  label: 'Batch Code' },
  { key: 'sloc',        label: 'Sloc / Tank' },
  { key: 'status',      label: 'Status' },
]

onMounted(() => store.fetchSuppliers())

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
