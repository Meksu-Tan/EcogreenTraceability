<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Storage - Detail</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <RouterLink :to="{ name: 'setup.storage' }" class="text-caption text-medium-emphasis text-decoration-none">Storage</RouterLink>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Detail</span>
        </div>
      </div>
      <VBtn variant="outlined" color="medium-emphasis" prepend-icon="ri-arrow-left-line" @click="$router.push({ name: 'setup.storage' })">
        Back to Storage
      </VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <div class="d-flex align-center justify-space-between pa-5 pb-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-database-2-line" color="primary" size="20" />
          <span class="text-body-1 font-weight-bold">Detail Tank: {{ selectedTank?.description || 'Loading...' }}</span>
        </div>
        <VBtn id="storageDetail-add" color="primary" prepend-icon="ri-add-line" @click="openDetailModal">New Tank</VBtn>
      </div>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="detailColumns"
          :data="store.details"
          :loading="store.loading"
          row-key="id_tank_tail"
          :show-top-info="false"
          @edit="onEditDetail"
          @toggle-status="onToggleDetail"
        >
          <template #cell-id_plant>
            <span class="text-body-2">{{ selectedTank?.id_plant }}</span>
          </template>
          <template #cell-storage>
            <span class="text-body-2 font-weight-bold">{{ selectedTank?.code }}</span>
          </template>
        </DataTable>
      </VCardText>
    </VCard>

    <!-- Modal -->
    <StorageDetailModal
      v-model="showDetailModal"
      :edit-data="editDetail"
      :loading="submitting"
      :tank-id="Number(id)"
      @submit="onSubmitDetail"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import StorageDetailModal from './StorageDetailModal.vue'
import { useSetupStorageStore } from '@/modules/m-storage/stores'
import { useToastStore } from '@/stores/toast'
import { useConfirmStore } from '@/stores/confirm'

const props = defineProps({
  id: { type: String, required: true }
})

const store           = useSetupStorageStore()
const toast           = useToastStore()
const confirmStore    = useConfirmStore()
const showDetailModal = ref(false)
const editDetail      = ref(null)
const submitting      = ref(false)

const selectedTank = computed(() => {
  return store.tanks.find(t => t.id_tank == props.id)
})

const detailColumns = [
  { key: 'id_plant',   label: 'Plant' },
  { key: 'storage',    label: 'Storage' },
  { key: 'tf_number',  label: 'Tank Number' },
  { key: 'status',     label: 'Status' },
  { key: 'created_at', label: 'Created at' },
  { key: 'updated_at', label: 'Updated at' },
]

onMounted(async () => {
  try {
    if (store.tanks.length === 0) {
      await store.fetchTanks()
    }
    await store.fetchDetails(props.id)
  } catch (error) {
    toast.error('Failed to fetch storage detail data:', error)
  }
})

function openDetailModal() {
  editDetail.value = null
  showDetailModal.value = true
}

function onEditDetail(row) {
  editDetail.value = row
  showDetailModal.value = true
}

async function onToggleDetail(row) {
  const isConfirmed = await confirmStore.show({
    message: `${row.status==1?'Deactivate':'Activate'} detail "${row.tf_number}"?`
  })
  if (!isConfirmed) return
  const r = await store.toggleDetail(row.id_tank_tail, row.status, props.id)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmitDetail(data) {
  submitting.value = true
  try {
    const r = editDetail.value ? await store.editDetail(editDetail.value.id_tank_tail, data) : await store.createDetail(data)
    if (r.status===1) {
      toast.success(r.message)
      showDetailModal.value = false
    }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>