<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Material</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-500">Material</span>
        </div>
      </div>
      <button
        id="btn-tambah-material"
        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95"
        @click="openModal"
      >
        <Icon icon="ri:add-line" class="w-4 h-4" /> Tambah
      </button>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-4 flex-wrap">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <Icon icon="ri:flask-line" class="text-green-500 w-4 h-4" />
          Data Material
        </h4>
        <div class="flex bg-gray-100 p-1 rounded-lg">
          <button
            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
            :class="activeTab==='wip' ? 'bg-slate-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            @click="activeTab='wip'"
          >
            Material WIP
          </button>
          <button
            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
            :class="activeTab==='packaging' ? 'bg-slate-700 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            @click="activeTab='packaging'"
          >
            Packaging
          </button>
        </div>
      </div>
      <div class="p-6">
        <!-- Tab: WIP -->
        <div v-if="activeTab === 'wip'">
          <DataTable
            :columns="materialColumns"
            :data="store.materials"
            :loading="store.loading"
            row-key="id_material"
            @edit="onEditMaterial"
            @toggle-status="onToggleMaterial"
          >
            <template #cell-yield>{{ value }}%</template>
            <template #cell-status_packaging="{ value }">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                :class="value == 1 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                {{ value == 1 ? 'Ya' : 'Tidak' }}
              </span>
            </template>
          </DataTable>
        </div>
        <!-- Tab: Packaging -->
        <div v-if="activeTab === 'packaging'">
          <MaterialPackagingTab ref="packagingTabRef" />
        </div>
      </div>
    </div>

    <!-- Modal -->
    <MaterialModal
      v-model="showModal"
      :edit-data="editRow"
      :loading="submitting"
      @submit="onSubmitMaterial"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import MaterialModal from './MaterialModal.vue'
import MaterialPackagingTab from './MaterialPackagingTab.vue'
import { useSetupMaterialStore } from '@/modules/m-material/stores'
import { useToastStore } from '@/stores/toast'

const store           = useSetupMaterialStore()
const toast           = useToastStore()
const activeTab       = ref('wip')
const showModal       = ref(false)
const editRow         = ref(null)
const submitting      = ref(false)
const packagingTabRef = ref(null)

const materialColumns = [
  { key: 'code',               label: 'Code' },
  { key: 'code_noneudr',       label: 'Code (Non-EUDR)' },
  { key: 'code_matl_supplier', label: 'Code (Supplier)' },
  { key: 'description',        label: 'Description' },
  { key: 'type',               label: 'Type' },
  { key: 'qtf_feed',           label: 'Flowmeter Feed' },
  { key: 'qtf_rundown',        label: 'Flowmeter Rundown' },
  { key: 'id_feed',            label: 'ID Feed' },
  { key: 'id_rundown',         label: 'ID Rundown' },
  { key: 'status_packaging',   label: 'For Packaging' },
  { key: 'status',             label: 'Status' },
  { key: 'created_at',         label: 'Created at' },
  { key: 'updated_at',         label: 'Updated at' }
]

onMounted(async () => {
  try {
    await store.fetchMaterials()
  } catch (error) {
    toast.error('Failed to fetch materials:', error)
  }
})

function openModal() {
  if (activeTab.value === 'packaging') { packagingTabRef.value?.openModal(); return }
  editRow.value = null; showModal.value = true
}

function onEditMaterial(row) { editRow.value = row; showModal.value = true }

async function onToggleMaterial(row) {
  if (!confirm(`${row.status == 1 ? 'Deactivate' : 'Activate'} material "${row.description}"?`)) return
  const r = await store.toggleMaterial(row.id_material, row.status)
  r.status === 1 ? toast.success(r.message) : toast.error(r.message)
}

async function onSubmitMaterial(data) {
  submitting.value = true
  try {
    const r = editRow.value ? await store.editMaterial(editRow.value.id_material, data) : await store.createMaterial(data)
    if (r.status === 1) { toast.success(r.message); showModal.value = false }
    else toast.error(r.message)
  } finally { submitting.value = false }
}
</script>