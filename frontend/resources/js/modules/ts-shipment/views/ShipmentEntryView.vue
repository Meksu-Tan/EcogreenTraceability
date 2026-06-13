<template>
  <div class="pa-6">
    <!-- Header Toolbar -->
    <VRow justify="space-between" align="center" class="mb-6">
      <VCol cols="12" md="auto" class="d-flex align-center gap-4">
        <div>
          <h1 class="text-h5 font-weight-bold mb-1">Shipment Entry</h1>
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
          New Shipment Entry
        </VBtn>
      </VCol>
    </VRow>
    <!-- Main Data Table Card -->
    <VCard rounded="lg" variant="outlined">
      <DataTable
        :columns="columns"
        :data="store.entries"
        :loading="store.loading"
        row-key="id_ship_head"
        :per-page="10"
        :show-search="false"
        :show-top-info="false"
      >
        <!-- Custom action cell -->
        <template #actions="{ row }">
          <div class="d-flex justify-center gap-1">
            <!-- Edit SO -->
            <VTooltip text="Edit SO No" location="top">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="x-small"
                  icon="ri-article-line"
                  color="primary"
                  variant="tonal"
                  @click="openSoModal(row)"
                />
              </template>
            </VTooltip>

            <!-- Query SAP details -->
            <VTooltip text="Query SAP Shipment" location="top">
              <template #activator="{ props }">
                <VBtn
                  v-bind="props"
                  size="x-small"
                  icon="ri-database-2-line"
                  color="success"
                  variant="tonal"
                  @click="openSapModal(row)"
                />
              </template>
            </VTooltip>

            <!-- Cancel / Delete -->
            <VTooltip text="Cancel Shipment" location="top">
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

        <template #cell-so_no="{ value }">
          <span class="font-weight-medium">{{ value || '-' }}</span>
        </template>

        <template #cell-batch_no="{ value }">
          <a
            href="#"
            class="text-decoration-none text-primary font-weight-semibold"
            @click.prevent="openBatchDetailModal(value)"
          >
            {{ value }}
          </a>
        </template>

        <template #cell-doc_url="{ value }">
          <VBtn
            v-if="value"
            icon="ri-file-pdf-fill"
            size="x-small"
            color="error"
            variant="text"
            :href="`/uploads/shipments/${value}`"
            target="_blank"
          />
          <span v-else class="text-caption text-disabled">—</span>
        </template>

        <template #cell-supplier="{ value }">
          <span class="text-caption d-block text-truncate" style="max-width: 320px;" :title="value">
            {{ value }}
          </span>
        </template>
      </DataTable>
    </VCard>

    <!-- Modals -->
    <ShipmentEntryModal v-model="showAddModal" @saved="fetchData" />
    <ShipmentEntrySoModal v-model="showSoModal" :row="selectedRow" @saved="fetchData" />
    <ShipmentBatchPackagingModal v-model="showBatchDetailModal" :batchNo="selectedBatchNo" />
    <ShipmentDataShipmentModal v-model="showSapModal" :row="selectedRow" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'
import { useConfirmStore } from '@/stores/confirm'
import DataTable from '@/modules/shared/components/DataTable.vue'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import ShipmentEntryModal from './ShipmentEntryModal.vue'
import ShipmentEntrySoModal from './ShipmentEntrySoModal.vue'
import ShipmentBatchPackagingModal from './ShipmentBatchPackagingModal.vue'
import ShipmentDataShipmentModal from './ShipmentDataShipmentModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const store = useShipmentEntryStore()
const confirmStore = useConfirmStore()

const showAddModal = ref(false)
const showSoModal = ref(false)
const showBatchDetailModal = ref(false)
const showSapModal = ref(false)
const selectedRow = ref(null)
const selectedBatchNo = ref('')

const columns = [
  { key: 'entry_date', label: 'Entry Date' },
  { key: 'fromto_trace_no', label: 'Trace No (From >>> To)' },
  { key: 'so_no', label: 'SO' },
  { key: 'batch_no', label: 'Packaging Batch No' },
  { key: 'material', label: 'FG Product' },
  { key: 'qty', label: 'Qty (MT)' },
  { key: 'balance_supplier', label: 'Supplier Qty (MT)' },
  { key: 'supplier', label: 'Supplier / Batch SAP / Qty' },
  { key: 'doc_url', label: 'Doc' }
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

function openSoModal(row) {
  selectedRow.value = row
  showSoModal.value = true
}

function openBatchDetailModal(batchNo) {
  selectedBatchNo.value = batchNo
  showBatchDetailModal.value = true
}

function openSapModal(row) {
  selectedRow.value = row
  showSapModal.value = true
}

async function onCancel(row) {
  const isConfirmed = await confirmStore.show({
    title: 'Are you sure?',
    message: `Cancel shipment entry with Trace No: ${row.trace_no}?`
  })
  
  if (isConfirmed) {
    await store.cancelEntry(row.id_ship_head, row.trace_no)
    await fetchData()
  }
}
</script>