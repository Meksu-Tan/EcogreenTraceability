<template>
  <div class="pa-6">
    <!-- Header Toolbar -->
    <VRow justify="space-between" align="center" class="mb-6">
      <VCol cols="12" md="auto" class="d-flex align-center gap-4">
        <div>
          <h1 class="text-h5 font-weight-bold mb-1">Packaging Entry</h1>
          <div class="d-flex align-center gap-2">
            <span class="text-caption text-medium-emphasis">Location:</span>
            <VChip
              color="success"
              variant="tonal"
              size="small"
              prepend-icon="ri-factory-line"
              class="font-weight-bold"
            >
              {{ plantSelectionStore.selectedPlantName }}
            </VChip>
          </div>
        </div>
        <VDivider vertical class="mx-2" style="height: 40px" />
        <PlantSelector @change="fetchData" />
      </VCol>
      <VCol cols="12" md="auto">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          class="font-weight-bold"
          @click="openAddModal"
        >
          New Packaging Entry
        </VBtn>
      </VCol>
    </VRow>

    <!-- Warnings / Info alert -->
    <VAlert
      type="error"
      variant="tonal"
      icon="ri-alert-line"
      class="mb-6 text-body-2 font-weight-semibold"
      density="compact"
    >
      QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
    </VAlert>

    <!-- Main Data Table Card -->
    <VCard rounded="lg" variant="outlined">
      <DataTable
        :columns="columns"
        :data="store.entries"
        :loading="store.loading"
        row-key="id_whx_head"
        :per-page="10"
        :show-search="false"
        :show-top-info="false"
      >
        <!-- Custom action cell -->
        <template #actions="{ row }">
          <div class="d-flex justify-center gap-1">
            <!-- Edit PO -->
            <VTooltip text="Edit PO" location="top">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="x-small"
                  icon="ri-article-line"
                  color="primary"
                  variant="tonal"
                  @click="openPoModal(row)"
                />
              </template>
            </VTooltip>

            <!-- Edit Batch & Warehouse -->
            <VTooltip text="Edit Batch & Warehouse" location="top">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="x-small"
                  icon="ri-edit-box-line"
                  color="warning"
                  variant="tonal"
                  @click="openBatchModal(row)"
                />
              </template>
            </VTooltip>

            <!-- Cancel / Delete -->
            <VTooltip text="Cancel Entry" location="top">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="x-small"
                  icon="ri-delete-bin-7-line"
                  color="error"
                  variant="tonal"
                  @click="onCancel(row)"
                />
              </template>
            </VTooltip>
          </div>
        </template>

        <!-- Custom column cell renderings -->
        <template #cell-fromto_trace_no="{ value }">
          <span class="text-caption text-medium-emphasis">{{ value }}</span>
        </template>

        <template #cell-po_no="{ value }">
          <span class="font-weight-medium">{{ value || '-' }}</span>
        </template>

        <template #cell-batch_no="{ value }">
          <VChip size="small" variant="flat" color="neutral-100" class="font-weight-semibold">
            {{ value }}
          </VChip>
        </template>

        <template #cell-sloc="{ row }">
          <a
            href="#"
            class="text-decoration-none text-primary font-weight-semibold"
            @click.prevent="openSubTankModal(row)"
          >
            {{ row.sloc || 'Sloc' }}
          </a>
        </template>

        <template #cell-init_qty="{ row }">
          <span :class="row.init_qty === row.balance_supplier ? 'text-success' : 'text-error'" class="font-weight-bold">
            {{ row.init_qty }}
          </span>
        </template>

        <template #cell-balance_supplier="{ row }">
          <span :class="row.init_qty === row.balance_supplier ? 'text-success' : 'text-error'" class="font-weight-bold">
            {{ row.balance_supplier }}
          </span>
        </template>

        <template #cell-supplier="{ value }">
          <span class="text-caption d-block text-truncate" style="max-width: 320px;" :title="value">
            {{ value }}
          </span>
        </template>
      </DataTable>
    </VCard>

    <!-- Modals -->
    <PackageEntryModal v-model="showAddModal" @saved="fetchData" />
    <PackageEntryPoModal v-model="showPoModal" :row="selectedRow" @saved="fetchData" />
    <PackageEntryBatchModal v-model="showBatchModal" :row="selectedRow" @saved="fetchData" />
    <SelectSubTankModal v-model="showSubTankModal" :row="selectedRow" @saved="fetchData" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { usePackageEntryStore } from '../stores/usePackageEntryStore'
import { useConfirmStore } from '@/stores/confirm.js'
import DataTable from '@/modules/shared/components/DataTable.vue'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import PackageEntryModal from './PackageEntryModal.vue'
import PackageEntryPoModal from './PackageEntryPoModal.vue'
import PackageEntryBatchModal from './PackageEntryBatchModal.vue'
import SelectSubTankModal from './SelectSubTankModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const store = usePackageEntryStore()
const confirmStore = useConfirmStore()

const showAddModal = ref(false)
const showPoModal = ref(false)
const showBatchModal = ref(false)
const showSubTankModal = ref(false)
const selectedRow = ref(null)

const columns = [
  { key: 'entry_date', label: 'Entry Date' },
  { key: 'fromto_trace_no', label: 'Trace No (From >>> To)' },
  { key: 'po_no', label: 'PO' },
  { key: 'batch_no', label: 'PPH Batch No' },
  { key: 'feed', label: 'WIP Product' },
  { key: 'fg', label: 'FG Product' },
  { key: 'sloc', label: 'Sloc' },
  { key: 'whx', label: 'FG Sloc' },
  { key: 'init_qty', label: 'Init Mat (MT)' },
  { key: 'balance_supplier', label: 'Init Supp (MT)' },
  { key: 'balance', label: 'Balance (MT)' },
  { key: 'supplier', label: 'Supplier / Batch SAP / Init / Balance' }
]

async function fetchData() {
  await store.fetchEntries()
}

onMounted(() => {
  fetchData()
})

function openAddModal() {
  showAddModal.value = true
}

function openPoModal(row) {
  selectedRow.value = row
  showPoModal.value = true
}

function openBatchModal(row) {
  selectedRow.value = row
  showBatchModal.value = true
}

function openSubTankModal(row) {
  selectedRow.value = row
  showSubTankModal.value = true
}

async function onCancel(row) {
  const isConfirmed = await confirmStore.show({
    title: 'Are you sure?',
    message: `Cancel packaging entry with Batch No: ${row.batch_no}?`
  })
  
  if (isConfirmed) {
    await store.cancelEntry(row.id_whx_head, row.trace_no)
    await fetchData()
  }
}
</script>