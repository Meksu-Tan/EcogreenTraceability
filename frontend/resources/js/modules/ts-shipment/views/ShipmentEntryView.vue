<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-4">
      <VCol cols="auto">
        <VRow align="center" no-gutters>
          <VCol cols="auto">
            <h1 class="text-h5 font-weight-bold">Shipment Entry</h1>
            <div class="d-flex align-center gap-2 mt-1">
              <span class="text-body-2 text-medium-emphasis">Location:</span>
              <VChip
                size="small"
                color="primary"
                variant="tonal"
                prepend-icon="ri-factory-line"
              >
                {{ plantSelectionStore.selectedPlantName || 'All Plants' }}
              </VChip>
            </div>
          </VCol>
          <VCol cols="auto" class="ml-6 pl-6 border-s">
            <PlantSelector @change="fetchData" />
          </VCol>
        </VRow>
      </VCol>
      <VCol cols="auto">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openAddModal"
        >
          New Shipment Entry
        </VBtn>
      </VCol>
    </VRow>

    <VAlert
      type="error"
      variant="tonal"
      density="comfortable"
      class="mb-4"
      icon="ri-error-warning-line"
    >
      QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
    </VAlert>

    <VCard v-if="store.loading">
      <VCardText class="pa-0">
        <VSkeletonLoader type="table-thead, table-tbody@5" :loading="true" />
      </VCardText>
    </VCard>

    <VAlert
      v-else-if="store.error"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      <div class="d-flex flex-column">
        <strong class="text-body-2 font-weight-medium">Error loading data</strong>
        <span class="text-body-2 mt-1">{{ store.error }}</span>
        <VBtn color="error" variant="tonal" size="small" class="mt-3 align-self-start" @click="fetchData">
          Try again
        </VBtn>
      </div>
    </VAlert>

    <VCard v-else>
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date')">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'fromto_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('fromto_trace_no')">Trace No (From >>> To)<VIcon v-if="sortKey==='fromto_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'plant_name' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('plant_name')">Plant<VIcon v-if="sortKey==='plant_name'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'so_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('so_no')">SO No<VIcon v-if="sortKey==='so_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'batch_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('batch_no')">Packaging Batch No<VIcon v-if="sortKey==='batch_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material')">FG Product<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('qty')">Qty (MT)<VIcon v-if="sortKey==='qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier')">Supplier Qty (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="min-width:200px">Supplier / Batch SAP / Qty</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:120px">Action</th>
              </tr>
            </thead>
            <tbody v-if="sortedList.length > 0">
              <tr v-for="(row, index) in sortedList" :key="row.id_ship_head">
                <td class="text-caption text-medium-emphasis text-center">{{ (page - 1) * perPage + index + 1 }}</td>
                <td class="text-center">{{ row.entry_date }}</td>
                <td class="text-center font-weight-medium font-mono text-caption">{{ row.fromto_trace_no }}</td>
                <td class="text-center"><VChip size="x-small" color="primary" variant="tonal">{{ row.plant_name || '-' }}</VChip></td>
                <td class="text-center font-weight-medium">{{ row.so_no || '-' }}</td>
                <td class="text-center">
                  <a href="#" @click.prevent="openBatchDetailModal(row.batch_no)" class="text-medium-emphasis text-decoration-underline text-caption">
                    {{ row.batch_no || '-' }}
                  </a>
                </td>
                <td class="font-weight-medium text-truncate" style="max-width:160px" :title="row.material">{{ row.material || '-' }}</td>
                <td class="text-right font-monospace text-caption" :class="row.qty === row.balance_supplier ? 'text-success' : 'text-error'">{{ row.qty }}</td>
                <td class="text-right font-monospace text-caption" :class="row.qty === row.balance_supplier ? 'text-success' : 'text-error'">{{ row.balance_supplier }}</td>
                <td class="text-caption" style="min-width:200px">
                  <template v-if="row.supplier">
                    <VChip
                      v-for="(sup, si) in (typeof row.supplier === 'string' ? row.supplier.split('|') : [row.supplier])"
                      :key="si"
                      size="x-small"
                      color="primary"
                      variant="flat"
                      class="mr-1 mb-1"
                    >
                      {{ sup.trim() }}
                    </VChip>
                  </template>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-center gap-1">
                    <VBtn icon="ri-article-line" size="x-small" color="primary" variant="tonal" @click="openSoModal(row)" title="Edit SO No" />
                    <VBtn icon="ri-database-2-line" size="x-small" color="success" variant="tonal" @click="openSapModal(row)" title="Query SAP Shipment" />
                    <VBtn icon="ri-delete-bin-7-line" size="x-small" color="error" variant="tonal" @click="onCancel(row)" title="Cancel Shipment" />
                  </div>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="11" class="text-center pa-8">
                  <VIcon icon="ri-inbox-2-line" size="40" class="text-disabled mb-2" />
                  <p class="text-body-2 text-medium-emphasis">No shipment data yet</p>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      </VCardText>
    </VCard>

    <!-- Modals -->
    <ShipmentEntryModal v-model="showAddModal" @saved="fetchData" />
    <ShipmentEntrySoModal v-model="showSoModal" :row="selectedRow" @saved="fetchData" />
    <ShipmentBatchPackagingModal v-model="showBatchDetailModal" :batchNo="selectedBatchNo" />
    <ShipmentDataShipmentModal v-model="showSapModal" :row="selectedRow" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'
import { useConfirmStore } from '@/stores/confirm.js'
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

const page = ref(1)
const perPage = ref(10)
const sortKey = ref(null)
const sortDir = ref(null)

function detectColumnType(key) {
  const rows = store.entries
  if (!rows || rows.length === 0) return 'text'
  for (const row of rows) {
    const val = row[key]
    if (val !== null && val !== undefined && val !== '') {
      return !isNaN(parseFloat(val)) && isFinite(val) ? 'number' : 'text'
    }
  }
  return 'text'
}

function toggleSort(key) {
  if (sortKey.value === key) {
    if (sortDir.value === 'asc') {
      sortDir.value = 'desc'
    } else if (sortDir.value === 'desc') {
      sortKey.value = null
      sortDir.value = null
    }
  } else {
    sortKey.value = key
    sortDir.value = detectColumnType(key) === 'text' ? 'asc' : 'desc'
  }
}

const sortedList = computed(() => {
  if (!sortKey.value || !sortDir.value) return store.entries
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...store.entries]
  const type = detectColumnType(key)
  return rows.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    if (type === 'number') return dir === 'asc' ? va - vb : vb - va
    return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va))
  })
})

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

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>