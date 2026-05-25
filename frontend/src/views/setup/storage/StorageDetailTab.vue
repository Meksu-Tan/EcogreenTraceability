<template>
  <div>
    <!-- Tank Table -->
    <DataTable
      :columns="tankColumns"
      :data="store.tanks"
      :loading="store.loading"
      row-key="id_tank"
    >
      <template #cell-total_tank="{ row, value }">
        <button 
          class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-green-50 text-green-600 border border-green-100 hover:bg-green-100 transition-colors"
          @click.stop="onViewDetail(row)"
        >
          {{ value }} detail
        </button>
      </template>
      <template #actions="{ row }">
        <div class="flex items-center justify-center gap-1.5">
          <button
            type="button"
            class="p-1.5 rounded-md bg-green-600 text-white hover:bg-green-700 transition-colors shadow-sm active:scale-90 cursor-pointer"
            title="View Details"
            @click.stop="onViewDetail(row)"
          >
            <i class="fas fa-list text-[11px] pointer-events-none"></i>
          </button>
          <button
            type="button"
            class="p-1.5 rounded-md bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm active:scale-90 cursor-pointer"
            title="Edit"
            @click.stop="onEditTank(row)"
          >
            <i class="fas fa-pencil-alt text-[11px] pointer-events-none"></i>
          </button>
          <button
            type="button"
            class="p-1.5 rounded-md transition-colors shadow-sm active:scale-90 text-white cursor-pointer"
            :class="row.status == 1 ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'"
            :title="row.status == 1 ? 'Deactivate' : 'Activate'"
            @click.stop="onToggleTank(row)"
          >
            <i :class="row.status == 1 ? 'fas fa-times' : 'fas fa-check'" class="text-[11px] pointer-events-none"></i>
          </button>
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
import DataTable from '@/components/shared/DataTable.vue'
import StorageTankModal   from './StorageTankModal.vue'
import { useSetupStorageStore } from '@/modules/m-storage/stores'
import { useToastStore } from '@/stores/toast'

defineExpose({ openTankModal })

const router         = useRouter()
const store          = useSetupStorageStore()
const toast          = useToastStore()
const showTankModal  = ref(false)
const editTank       = ref(null)
const submitting     = ref(false)

const tankColumns = [
  { key: 'id_plant',   label: 'Plant' },
  { key: 'code',       label: 'Code' },
  { key: 'code_2',     label: 'Storage Type 1' },
  { key: 'code_3',     label: 'Storage Type 2' },
  { key: 'code_4',     label: 'Supplier' },
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
    console.error('Failed to fetch tanks:', error)
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
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} tank "${row.description}"?`)) return
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
