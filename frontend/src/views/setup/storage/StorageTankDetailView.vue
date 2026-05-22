<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Storage - Detail</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <router-link :to="{ name: 'setup.storage' }" class="text-sm text-gray-500 hover:text-green-600 transition-colors">Storage</router-link>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Detail</span>
        </div>
      </div>
      <button 
        class="bg-slate-500 hover:bg-slate-600 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95" 
        @click="$router.push({ name: 'setup.storage' })"
      >
        <i class="fas fa-binoculars"></i> Back to Storage
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between gap-4 flex-wrap">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-database text-green-600"></i>
          Detail Tank: {{ selectedTank?.description || 'Loading...' }}
        </h4>
        <button 
          id="storageDetail-add"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95" 
          @click="openDetailModal"
        >
          <i class="fas fa-plus"></i> New Tank
        </button>
      </div>
      <div class="p-6">
        <DataTable
          :columns="detailColumns"
          :data="store.details"
          :loading="store.loading"
          row-key="id_tank_tail"
          @edit="onEditDetail"
          @toggle-status="onToggleDetail"
        >
          <template #cell-id_plant>
            <span class="text-sm text-slate-600">{{ selectedTank?.id_plant }}</span>
          </template>
          <template #cell-storage>
            <span class="text-sm font-bold text-slate-700">{{ selectedTank?.code }}</span>
          </template>
        </DataTable>
      </div>
    </div>

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
import DataTable from '@/components/shared/DataTable.vue'
import StorageDetailModal from './StorageDetailModal.vue'
import { useSetupStorageStore } from '@/modules/storage/stores'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  id: { type: String, required: true }
})

const store           = useSetupStorageStore()
const toast           = useToastStore()
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
  if (store.tanks.length === 0) {
    await store.fetchTanks()
  }
  await store.fetchDetails(props.id)
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
  if (!confirm(`${row.status==1?'Deactivate':'Activate'} detail "${row.tf_number}"?`)) return
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
