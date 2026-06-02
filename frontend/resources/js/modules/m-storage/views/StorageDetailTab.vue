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
            class="p-1.5 rounded-md bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm active:scale-90 cursor-pointer"
            title="View Details"
            @click.stop="onViewDetail(row)"
          >
            <Icon icon="ri:list-check-2" class="text-[11px] pointer-events-none w-3 h-3" />
          </button>
          <button
            type="button"
            class="p-1.5 rounded-md bg-green-400 text-white hover:bg-green-500 transition-colors shadow-sm active:scale-90 cursor-pointer"
            title="Edit"
            @click.stop="onEditTank(row)"
          >
            <Icon icon="ri:edit-line" class="text-[11px] pointer-events-none w-3 h-3" />
          </button>
          <button
            type="button"
            class="p-1.5 rounded-md transition-colors shadow-sm active:scale-90 text-white cursor-pointer"
            :class="row.status == 1 ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
            :title="row.status == 1 ? 'Deactivate' : 'Activate'"
            @click.stop="onToggleTank(row)"
          >
            <Icon :icon="row.status == 1 ? 'ri:close-line' : 'ri:check-line'" class="text-[11px] pointer-events-none w-3 h-3" />
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
import { Icon } from '@iconify/vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
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