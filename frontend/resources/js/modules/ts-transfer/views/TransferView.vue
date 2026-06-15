<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-4">
      <VCol cols="auto">
        <VRow align="center" no-gutters>
          <VCol cols="auto">
            <h1 class="text-h5 font-weight-bold">Transfer List</h1>
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
      <VCol cols="auto" v-if="plantSelectionStore.selectedPlantId !== null">
        <VBtn
          color="primary"
          prepend-icon="ri-add-line"
          @click="openTransferModal"
        >
          New Transfer Entry
        </VBtn>
      </VCol>
    </VRow>

    <VAlert
      type="error"
      variant="tonal"
      density="comfortable"
      class="mb-4"
      icon="ri-alert-line"
    >
      QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
    </VAlert>

    <VCard v-if="transferStore.loading">
      <VCardText class="pa-0">
        <VSkeletonLoader type="table-thead, table-tbody@5" :loading="true" />
      </VCardText>
    </VCard>

    <VAlert
      v-else-if="transferStore.error"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      <div class="d-flex flex-column">
        <strong class="text-body-2 font-weight-medium">Error loading data</strong>
        <span class="text-body-2 mt-1">{{ transferStore.error }}</span>
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
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:48px">No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date')">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'plant_name' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('plant_name')">Plant<VIcon v-if="sortKey==='plant_name'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>

                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material_document' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material_document')">Matl Doc<VIcon v-if="sortKey==='material_document'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('trace_no')">Trace No<VIcon v-if="sortKey==='trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material')">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc')">Sloc (From >>> To)<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'init_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('init_qty')">Init Material (MT)<VIcon v-if="sortKey==='init_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier')">Init Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('qty')">On-Hand Material (MT)<VIcon v-if="sortKey==='qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'qty_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('qty_supplier')">On-Hand Supplier (MT)<VIcon v-if="sortKey==='qty_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="min-width:200px">Supplier / Batch SAP / Init Qty (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:120px">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(trf, index) in sortedList" :key="trf.id_balance_head">
                <td class="text-caption text-medium-emphasis">{{ (page - 1) * perPage + index + 1 }}</td>
                <td class="text-center">{{ formatDate(trf.entry_date) }}</td>
                <td class="text-center font-weight-medium text-caption" style="white-space:nowrap">{{ trf.from_plant_name ? trf.from_plant_name + ' ➔ ' + (trf.plant_name || '-') : (trf.plant_name || '-') }}</td>
                <td class="text-center">
                  <span v-if="trf.material_document" class="text-caption">{{ trf.material_document }}</span>
                  <VBtn
                    v-else
                    size="x-small"
                    color="warning"
                    variant="tonal"
                    @click="openMatlDocModal(trf)"
                  >
                    Add Doc No
                  </VBtn>
                </td>
                <td class="text-center font-weight-medium font-mono text-caption">{{ trf.trace_no }}</td>
                <td class="font-weight-medium text-truncate" style="max-width:160px" :title="trf.material">{{ trf.material }}</td>
                <td class="text-caption text-medium-emphasis">{{ trf.sloc }}</td>
                
                <!-- Init Qty columns style comparison -->
                <td class="text-right font-monospace text-caption" :class="trf.init_qty === trf.balance_supplier ? 'text-success' : 'text-error'">{{ trf.init_qty || '0.000' }}</td>
                <td class="text-right font-monospace text-caption" :class="trf.init_qty === trf.balance_supplier ? 'text-success' : 'text-error'">{{ trf.balance_supplier || '0.000' }}</td>
                
                <!-- On-Hand Qty columns style comparison -->
                <td class="text-right font-monospace font-weight-bold text-caption" :class="trf.qty === trf.qty_supplier ? 'text-success' : 'text-error'">{{ trf.qty }}</td>
                <td class="text-right font-monospace font-weight-bold text-caption" :class="trf.qty === trf.qty_supplier ? 'text-success' : 'text-error'">{{ trf.qty_supplier || '0.000' }}</td>

                <td style="min-width:200px">
                  <VChip
                    v-for="(sup, si) in parseSuppliers(trf.supplier)"
                    :key="si"
                    size="x-small"
                    color="primary"
                    variant="flat"
                    class="mr-1 mb-1"
                  >
                    {{ sup }}
                  </VChip>
                </td>
                <td class="text-center">
                  <VBtn
                    :loading="deactivatingId === trf.id_balance_head"
                    :icon="deactivatingId === trf.id_balance_head ? 'ri-loader-4-line' : 'ri-delete-bin-line'"
                    size="x-small"
                    color="error"
                    variant="tonal"
                    @click="deactivateTransfer(trf)"
                  />
                </td>
              </tr>
              <tr v-if="sortedList.length === 0">
                <td colspan="11" class="text-center pa-8">
                  <VIcon icon="ri-inbox-2-line" size="40" class="text-disabled mb-2" />
                  <p class="text-body-2 text-medium-emphasis">No transfer data yet</p>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>

        <div v-if="transferStore.pagination.total > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, transferStore.pagination.total) }} of {{ transferStore.pagination.total }} records
            </span>
            <VSelect
              v-model="perPage"
              :items="[5, 10, 15, 20]"
              density="compact"
              variant="outlined"
              hide-details
              style="min-width: 80px; max-width: 100px;"
            />
          </div>
          <VPagination
            v-if="lastPage > 1"
            v-model="page"
            :length="lastPage"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="changePage"
          />
        </div>
      </VCardText>
    </VCard>

    <MaterialDocModal
      v-if="selectedTransfer"
      v-model:is-open="isMatlDocModalOpen"
      :mode="matlDocMode"
      :id-trace-head="matlDocIdTraceHead"
      :current-number="matlDocCurrentNumber"
      @success="onMatlDocSuccess"
    />

    <TransferEntryModal
      v-model:is-open="isTransferModalOpen"
      @success="onTransferSuccess"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useTsTransferStore } from '@/modules/ts-transfer/stores'
import { useToastStore } from '@/stores/toast.js'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import MaterialDocModal from '@/modules/ts-transfer/components/MaterialDocModal.vue'
import TransferEntryModal from '@/modules/ts-transfer/components/TransferEntryModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const transferStore = useTsTransferStore()
const toastStore = useToastStore()

const isMatlDocModalOpen = ref(false)
const isTransferModalOpen = ref(false)
const deactivatingId = ref(null)
const selectedTransfer = ref(null)
const matlDocMode = ref('ADD')
const matlDocIdTraceHead = ref(null)
const matlDocCurrentNumber = ref('')

function parseSuppliers(val) {
  if (!val) return []
  if (Array.isArray(val)) return val
  if (typeof val === 'string') return val.split('|').map(s => s.trim()).filter(Boolean)
  return []
}

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

const page = ref(1)
const perPage = ref(5)

const sortKey = ref(null)
const sortDir = ref(null)

function detectColumnType(colKey) {
  const rows = transferStore.transferList
  if (!rows || rows.length === 0) return 'text'
  for (const row of rows) {
    const val = row[colKey]
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
  if (!sortKey.value || !sortDir.value) return transferStore.transferList
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...transferStore.transferList]
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

const lastPage = computed(() => transferStore.pagination.lastPage)

async function fetchData() {
  const plantId = plantSelectionStore.selectedPlantId || 0
  await transferStore.fetchTransferList(plantId, page.value, perPage.value)
}

async function changePage(p) {
  if (p < 1 || p > lastPage.value) return
  transferStore.setPage(p)
  await fetchData()
}

function openTransferModal() {
  isTransferModalOpen.value = true
}

function onTransferSuccess() {
  isTransferModalOpen.value = false
  fetchData()
}

function openMatlDocModal(trf) {
  selectedTransfer.value = trf
  matlDocIdTraceHead.value = trf.id_trace_head
  matlDocMode.value = trf.material_document ? 'UPDATE' : 'ADD'
  matlDocCurrentNumber.value = trf.material_document || ''
  isMatlDocModalOpen.value = true
}

function onMatlDocSuccess() {
  isMatlDocModalOpen.value = false
  selectedTransfer.value = null
  fetchData()
}

async function deactivateTransfer(trf) {
  const compoundId = (trf.id_balance_head || trf.idHead) + '|' + (trf.id_trace_head || trf.idTraceHead)
  deactivatingId.value = trf.id_balance_head || trf.idHead
  try {
    await transferStore.deleteTransfer(compoundId)
    toastStore.success('Transfer deactivated successfully')
    fetchData()
  } catch (error) {
    toastStore.error(error.message || 'Failed to deactivate transfer')
  } finally {
    deactivatingId.value = null
  }
}

watch(() => plantSelectionStore.selectedPlantId, () => {
  page.value = 1
  fetchData()
}, { immediate: true })

watch(perPage, () => {
  page.value = 1
  fetchData()
})
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>

