<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Tank</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-500">Tank</span>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <button
          id="btn-sync-tank"
          class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95 cursor-pointer disabled:opacity-50"
          @click="onSync"
          :disabled="syncing"
        >
          <Icon icon="ri:refresh-line" :class="{'animate-spin': syncing}" class="w-4 h-4" /> {{ syncing ? 'Updating...' : 'Update Data' }}
        </button>
        <button
          id="btn-tambah-tank"
          class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95 cursor-pointer"
          @click="openModal"
        >
          <Icon icon="ri:add-line" class="w-4 h-4" /> Tambah
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <Icon icon="ri:water-flash-line" class="text-green-500 w-4 h-4" />
          Data Tank
        </h4>
      </div>
      <div class="p-6">
        <DataTable
          :columns="columns"
          :data="store.tanks"
          :loading="store.loading"
          row-key="id"
        >
          <template #cell-id="{ row }">
            <span class="text-xs font-mono text-slate-500 font-semibold">{{ row.id }}</span>
          </template>
          <template #cell-plant="{ row }">
            <span class="font-bold text-slate-700">{{ row.plant_code }}</span>
            <span class="mx-2 text-slate-300">|</span>
            <span class="text-slate-600">{{ row.plant_name }}</span>
          </template>
          <template #cell-tank_height="{ row }">
            <span class="text-sm font-semibold text-slate-700">{{ parseFloat(row.tank_height).toLocaleString() }}</span>
            <span class="text-[10px] text-gray-400 ml-1">m</span>
          </template>
          <template #cell-status="{ row }">
            <span
              class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase inline-flex items-center gap-1"
              :class="row.status == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="row.status == 1 ? 'bg-green-500' : 'bg-red-500'"></span>
              {{ row.status == 1 ? 'Active' : 'Inactive' }}
            </span>
          </template>
          <template #actions="{ row }">
            <div class="flex items-center justify-center gap-1.5">
              <button
                type="button"
                class="p-1.5 rounded-md bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm active:scale-90 cursor-pointer"
                title="Edit"
                @click.stop="onEdit(row)"
              >
                <Icon icon="ri:edit-line" class="text-[11px] pointer-events-none w-3 h-3" />
              </button>
              <button
                type="button"
                class="p-1.5 rounded-md transition-colors shadow-sm active:scale-90 text-white cursor-pointer"
                :class="row.status == 1 ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'"
                :title="row.status == 1 ? 'Deactivate' : 'Activate'"
                @click.stop="onToggle(row)"
              >
                <Icon :icon="row.status == 1 ? 'ri:close-line' : 'ri:check-line'" class="text-[11px] pointer-events-none w-3 h-3" />
              </button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <TankModal
      v-model="showModal"
      :edit-data="editData"
      :loading="submitting"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import TankModal from './TankModal.vue'
import { useSetupTankStore } from '@/modules/m-tank/stores'
import { useToastStore } from '@/stores/toast'

const store      = useSetupTankStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editData   = ref(null)
const submitting = ref(false)
const syncing    = ref(false)

const columns = [
  { key: 'id',          label: 'ID' },
  { key: 'plant',       label: 'Plant Code | Name' },
  { key: 'tank_number', label: 'Tank Number' },
  { key: 'tank_height', label: 'Tank Height' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(async () => {
  try {
    await store.fetchTanks()
  } catch (error) {
    toast.error('Failed to fetch tanks:', error)
  }
})

function openModal() {
  editData.value = null
  showModal.value = true
}

function onEdit(row) {
  editData.value = row
  showModal.value = true
}

async function onToggle(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} tank "${row.tank_number}" for plant "${row.plant_name}"?`)) return
  const r = await store.toggleTank(row.id, row.status)
  if (r.status === 1) {
    toast.success(r.message)
  } else {
    toast.error(r.message)
  }
}

async function onSync() {
  syncing.value = true
  try {
    const r = await store.syncTanks()
    if (r.status === 1) {
      toast.success(r.message)
    } else {
      toast.error(r.message)
    }
  } catch (error) {
    toast.error('Failed to sync tanks', error)
  } finally {
    syncing.value = false
  }
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editTank(editData.value.id, data) : await store.createTank(data)
    if (r.status===1) {
      toast.success(r.message)
      showModal.value = false
    } else {
      toast.error(r.message)
    }
  } finally {
    submitting.value = false
  }
}
</script>