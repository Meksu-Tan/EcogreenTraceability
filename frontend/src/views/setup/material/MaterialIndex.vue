<template>
  <div>
    <!-- Section header -->
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h1 style="font-size:18px;font-weight:700;color:var(--text-main);margin:0 0 4px;">Setup Material</h1>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item">TS Setup</div>
          <div class="breadcrumb-item" style="color:var(--primary);">Material</div>
        </div>
      </div>
      <button id="btn-tambah-material" class="btn btn-primary" @click="openModal">
        <i class="fas fa-plus"></i> Tambah
      </button>
    </div>

    <!-- Card -->
    <div class="card card-primary">
      <div class="card-header">
        <h4><i class="fab fa-asymmetrik" style="color:var(--primary);margin-right:8px;"></i>Data Material</h4>
        <div class="card-header-action">
          <!-- Tabs inside card header -->
          <div class="nav-tabs" style="border:none;margin:0;gap:0;">
            <button class="nav-tab" :class="{ active: activeTab==='wip' }" @click="activeTab='wip'">Material WIP</button>
            <button class="nav-tab" :class="{ active: activeTab==='packaging' }" @click="activeTab='packaging'">Packaging</button>
          </div>
        </div>
      </div>
      <div class="card-body">
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
            <template #cell-yield="{ value }">{{ value }}%</template>
            <template #cell-status_packaging="{ value }">
              <span class="badge" :class="value == 1 ? 'badge-success' : 'badge-secondary'">
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
import DataTable from '@/components/shared/DataTable.vue'
import MaterialModal from './MaterialModal.vue'
import MaterialPackagingTab from './MaterialPackagingTab.vue'
import { useSetupMaterialStore } from '@/stores/setupMaterial'
import { useToastStore } from '@/stores/toast'

const store           = useSetupMaterialStore()
const toast           = useToastStore()
const activeTab       = ref('wip')
const showModal       = ref(false)
const editRow         = ref(null)
const submitting      = ref(false)
const packagingTabRef = ref(null)

const materialColumns = [
  { key: 'code',             label: 'Kode' },
  { key: 'code_noneudr',     label: 'Non-EUDR' },
  { key: 'description',      label: 'Deskripsi' },
  { key: 'type',             label: 'Tipe' },
  { key: 'yield',            label: 'Yield' },
  { key: 'status_packaging', label: 'Packaging' },
  { key: 'status',           label: 'Status' },
]

onMounted(() => store.fetchMaterials())

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
