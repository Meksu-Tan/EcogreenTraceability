<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Plant</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Plant</span>
        </div>
      </div>
      <button 
        id="btn-tambah-plant" 
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95" 
        @click="openModal"
      >
        <i class="fas fa-plus"></i> Tambah
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between gap-4 flex-wrap">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-building text-green-600"></i>
          Data Plant
        </h4>
      </div>
      <div class="p-6">
        <DataTable
          :columns="columns"
          :data="store.plants"
          :loading="store.loading"
          row-key="id_plant"
        >
          <template #cell-code_internal="{ row }">
            <span class="text-xs font-mono text-slate-500">{{ row.code || '-' }}</span>
          </template>
          <template #cell-code="{ row }">
            <span class="font-bold text-slate-700">{{ row.code_2 }}</span>
            <span class="mx-2 text-slate-300">|</span>
            <span class="text-slate-600">{{ row.code_3 }}</span>
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
                <i class="fas fa-pencil-alt text-[11px] pointer-events-none"></i>
              </button>
              <button
                type="button"
                class="p-1.5 rounded-md transition-colors shadow-sm active:scale-90 text-white cursor-pointer"
                :class="row.status == 1 ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'"
                :title="row.status == 1 ? 'Deactivate' : 'Activate'"
                @click.stop="onToggle(row)"
              >
                <i :class="row.status == 1 ? 'fas fa-times' : 'fas fa-check'" class="text-[11px] pointer-events-none"></i>
              </button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <PlantModal 
      v-model="showModal" 
      :edit-data="editData" 
      :loading="submitting" 
      @submit="onSubmit" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/components/shared/DataTable.vue'
import PlantModal from './PlantModal.vue'
import { useSetupPlantStore } from '@/stores/plant'
import { useToastStore } from '@/stores/toast'

const store      = useSetupPlantStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editData   = ref(null)
const submitting = ref(false)

const columns = [
  { key: 'code_internal', label: 'ID' },
  { key: 'code',        label: 'Code | Name' },
  { key: 'description', label: 'Description' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

onMounted(() => store.fetchPlants())

function openModal() {
  editData.value = null
  showModal.value = true
}

function onEdit(row) {
  editData.value = row
  showModal.value = true
}

async function onToggle(row) {
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} plant "${row.description}"?`)) return
  const r = await store.togglePlant(row.id_plant, row.status)
  r.status===1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editPlant(editData.value.id_plant, data) : await store.createPlant(data)
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
