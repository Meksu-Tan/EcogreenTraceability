<template>
  <div>
    <!-- Tank Table -->
    <DataTable
      :columns="tankColumns"
      :data="store.tanks"
      :loading="store.loading"
      row-key="id_tank"
      @edit="onEditTank"
      @toggle-status="onToggleTank"
    >
      <template #cell-total_tank="{ value }">
        <span class="badge badge-info">{{ value }} detail</span>
      </template>
    </DataTable>

    <!-- Storage Detail sub-section -->
    <div v-if="selectedTank" style="margin-top:2rem;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
        <div>
          <div style="font-weight:700;color:#0f172a;">Detail Tank: {{ selectedTank.description }}</div>
          <div style="font-size:.8125rem;color:#94a3b8;">ID Tank: {{ selectedTank.id_tank }}</div>
        </div>
        <button class="btn btn-primary btn-sm" @click="openDetailModal">
          <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
          Tambah Detail
        </button>
      </div>
      <DataTable
        :columns="detailColumns"
        :data="store.details"
        row-key="id_tank_tail"
        @edit="onEditDetail"
        @toggle-status="onToggleDetail"
      />
    </div>

    <!-- Modals -->
    <StorageTankModal   v-model="showTankModal"   :edit-data="editTank"   :loading="submitting" @submit="onSubmitTank" />
    <StorageDetailModal v-model="showDetailModal" :edit-data="editDetail" :loading="submitting" :tank-id="selectedTank?.id_tank" @submit="onSubmitDetail" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/components/shared/DataTable.vue'
import StorageTankModal   from './StorageTankModal.vue'
import StorageDetailModal from './StorageDetailModal.vue'
import { useSetupStorageStore } from '@/stores/setupStorage'
import { useToastStore } from '@/stores/toast'

defineExpose({ openTankModal })

const store          = useSetupStorageStore()
const toast          = useToastStore()
const showTankModal  = ref(false)
const showDetailModal= ref(false)
const editTank       = ref(null)
const editDetail     = ref(null)
const selectedTank   = ref(null)
const submitting     = ref(false)

const tankColumns = [
  { key: 'code',       label: 'Kode' },
  { key: 'description',label: 'Deskripsi' },
  { key: 'total_tank', label: 'Total Detail' },
  { key: 'status',     label: 'Status' },
]
const detailColumns = [
  { key: 'tf_number',  label: 'TF Number' },
  { key: 'status',     label: 'Status' },
]

onMounted(() => store.fetchTanks())

function openTankModal() { editTank.value = null; showTankModal.value = true }
function openDetailModal() { editDetail.value = null; showDetailModal.value = true }

function onEditTank(row) {
  selectedTank.value = row
  store.fetchDetails(row.id_tank)
  editTank.value = row
  showTankModal.value = true
}

function onEditDetail(row) { editDetail.value = row; showDetailModal.value = true }

async function onToggleTank(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} tank "${row.description}"?`)) return
  const r = await store.toggleTank(row.id_tank, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onToggleDetail(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} detail "${row.tf_number}"?`)) return
  const r = await store.toggleDetail(row.id_tank_tail, row.status, selectedTank.value?.id_tank)
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

async function onSubmitDetail(data) {
  submitting.value = true
  try {
    const r = editDetail.value ? await store.editDetail(editDetail.value.id_tank_tail, data) : await store.createDetail(data)
    if (r.status===1) { toast.success(r.message); showDetailModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>
