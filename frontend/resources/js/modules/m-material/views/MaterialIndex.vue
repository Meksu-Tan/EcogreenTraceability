<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Material</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Material</span>
        </div>
      </div>
      <VBtn id="btn-tambah-material" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <div class="d-flex align-center justify-space-between pa-5 pb-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-flask-line" color="primary" size="20" />
          <span class="text-body-1 font-weight-bold">Data Material</span>
        </div>
        <VBtnToggle v-model="activeTab" mandatory rounded="lg" density="compact" color="primary">
          <VBtn value="wip" size="small">Material WIP</VBtn>
          <VBtn value="packaging" size="small">Packaging</VBtn>
        </VBtnToggle>
      </div>
      <VDivider />
      <VCardText class="pa-0">
        <div v-if="activeTab === 'wip'">
          <DataTable
            :columns="materialColumns"
            :data="store.materials"
            :loading="store.loading"
            row-key="id_material"
            :show-top-info="false"
            @edit="onEditMaterial"
            @toggle-status="onToggleMaterial"
          >
            <template #cell-status_packaging="{ value }">
              <VChip
                :color="value == 1 ? 'primary' : 'default'"
                variant="tonal"
                size="x-small"
              >
                {{ value == 1 ? 'Yes' : 'No' }}
              </VChip>
            </template>
          </DataTable>
        </div>
        <div v-if="activeTab === 'packaging'" class="pa-5">
          <MaterialPackagingTab ref="packagingTabRef" />
        </div>
      </VCardText>
    </VCard>

    <MaterialModal v-model="showModal" :edit-data="editRow" :loading="submitting" @submit="onSubmitMaterial" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import MaterialModal from './MaterialModal.vue'
import MaterialPackagingTab from './MaterialPackagingTab.vue'
import { useSetupMaterialStore } from '@/modules/m-material/stores'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store           = useSetupMaterialStore()
const toast           = useToastStore()
const confirmStore = useConfirmStore()
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
  const isConfirmed = await confirmStore.show({ message: `${row.status == 1 ? 'Deactivate' : 'Activate'} material "${row.description}"?` })
  if (!isConfirmed) return
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