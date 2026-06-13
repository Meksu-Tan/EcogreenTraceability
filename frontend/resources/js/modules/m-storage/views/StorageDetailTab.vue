<template>
  <div>
    <!-- Tank Table -->
    <DataTable
      :columns="tankColumns"
      :data="store.tanks"
      :loading="store.loading"
      row-key="id_tank"
      :show-top-info="false"
    >
      <template #cell-total_tank="{ row, value }">
        <VChip size="x-small" color="primary" variant="tonal" @click.stop="onViewDetail(row)" style="cursor: pointer;">
          {{ value }} detail
        </VChip>
      </template>
      <template #actions="{ row }">
        <div class="d-flex justify-center gap-1">
          <VBtn size="x-small" icon="ri-list-check-2" color="primary" variant="tonal" @click.stop="onViewDetail(row)" />
          <VBtn size="x-small" icon="ri-edit-line" color="primary" variant="tonal" @click.stop="onEditTank(row)" />
          <VBtn
            size="x-small"
            :icon="row.status == 1 ? 'ri-close-line' : 'ri-check-line'"
            :color="row.status == 1 ? 'error' : 'success'"
            variant="tonal"
            @click.stop="onToggleTank(row)"
          />
        </div>
      </template>
    </DataTable>

    <!-- Modals -->
    <StorageTankModal   v-model="showTankModal"   :edit-data="editTank"   :loading="submitting" @submit="onSubmitTank" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from '@/modules/shared/components/DataTable.vue'
import StorageTankModal   from './StorageTankModal.vue'
import { useSetupStorageStore } from '@/modules/m-storage/stores'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'

defineExpose({ openTankModal })

const router         = useRouter()
const store          = useSetupStorageStore()
const toast          = useToastStore()
const confirmStore = useConfirmStore()
const showTankModal  = ref(false)
const editTank       = ref(null)
const submitting     = ref(false)

const tankColumns = [
  { key: 'id_plant',   label: 'Plant' },
  { key: 'description',label: 'Storage Description' },
  { key: 'total_tank', label: 'Total Tank' },
  { key: 'status',     label: 'Status' },
  { key: 'created_at', label: 'Created at' },
  { key: 'updated_at', label: 'Updated at' },
]
onMounted(async () => {
  try {
    await store.fetchTanks()
  } catch (error) {
    toast.error('Failed to fetch tanks:', error)
  }
})

function openTankModal() { editTank.value = null; showTankModal.value = true }

function onViewDetail(row) {
  router.push({ name: 'setup.storage.detail', params: { id: row.id_tank } })
}

function onEditTank(row) {
  editTank.value = row
  showTankModal.value = true
}

async function onToggleTank(row) {
  const isConfirmed = await confirmStore.show({ message: `${row.status==1?'Deactivate':'Activate'} tank "${row.description}"?` })
  if (!isConfirmed) return
  const r = await store.toggleTank(row.id_tank, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmitTank(data) {
  submitting.value = true
  try {
    const r = editTank.value ? await store.editTank(editTank.value.id_tank, data) : await store.createTank(data)
    if (r.status===1) { toast.success(r.message); showTankModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>