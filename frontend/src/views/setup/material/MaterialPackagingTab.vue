<template>
  <DataTable
    :columns="columns"
    :data="store.packagings"
    :loading="store.loadingPkg"
    row-key="id_materialpck"
    @edit="onEdit"
    @toggle-status="onToggle"
  >
    <template #cell-source_product="{ value }">
      <span style="font-size:.8125rem;color:#64748b;">{{ value }}</span>
    </template>
  </DataTable>

  <MaterialPackagingModal
    v-model="showModal"
    :edit-data="editRow"
    :loading="submitting"
    :source-products="store.sourceProducts"
    @submit="onSubmit"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/components/shared/DataTable.vue'
import MaterialPackagingModal from './MaterialPackagingModal.vue'
import { useSetupMaterialStore } from '@/stores/setupMaterial'
import { useToastStore } from '@/stores/toast'

defineExpose({ openModal })

const store      = useSetupMaterialStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code',           label: 'Kode' },
  { key: 'code_noneudr',   label: 'Kode Non-EUDR' },
  { key: 'description',    label: 'Deskripsi' },
  { key: 'source_product', label: 'Source Product' },
  { key: 'status',         label: 'Status' },
]

onMounted(() => {
  store.fetchPackagings()
  store.fetchSourceProducts()
})

function openModal() {
  editRow.value   = null
  showModal.value = true
}

function onEdit(row) {
  editRow.value   = row
  showModal.value = true
}

async function onToggle(row) {
  if (!confirm(`${row.status == 1 ? 'Deactivate' : 'Activate'} packaging "${row.description}"?`)) return
  const result = await store.togglePackaging(row.id_materialpck, row.status)
  result.status === 1 ? toast.success(result.message) : toast.error(result.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const result = editRow.value
      ? await store.editPackaging(editRow.value.id_materialpck, data)
      : await store.createPackaging(data)
    if (result.status === 1) { toast.success(result.message); showModal.value = false }
    else toast.error(result.message)
  } finally {
    submitting.value = false
  }
}
</script>
