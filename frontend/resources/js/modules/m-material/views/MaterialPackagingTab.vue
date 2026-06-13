<template>
  <DataTable
    :columns="columns"
    :data="store.packagings"
    :loading="store.loadingPkg"
    row-key="id_materialpck"
    :show-top-info="false"
    @edit="onEdit"
    @toggle-status="onToggle"
  >
    <template #cell-source_product="{ value }">
      <span class="text-body-2 text-medium-emphasis">{{ value }}</span>
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
import DataTable from '@/modules/shared/components/DataTable.vue'
import MaterialPackagingModal from './MaterialPackagingModal.vue'
import { useSetupMaterialStore } from '@/modules/m-material/stores'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'

defineExpose({ openModal })

const store      = useSetupMaterialStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code',           label: 'Code' },
  { key: 'code_noneudr',   label: 'Code (Non-EUDR)' },
  { key: 'description',    label: 'Description' },
  { key: 'source_product', label: 'Source Product' },
  { key: 'status',         label: 'Status' },
  { key: 'created_at',     label: 'Created at' },
  { key: 'updated_at',     label: 'Updated at' }
]

onMounted(async () => {
  try {
    await store.fetchPackagings()
    await store.fetchSourceProducts()
  } catch (error) {
    toast.error('Failed to fetch packaging data:', error)
  }
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
  const isConfirmed = await confirmStore.show({ message: `${row.status == 1 ? 'Deactivate' : 'Activate'} packaging "${row.description}"?` })
  if (!isConfirmed) return
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