<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-2">
      <VCol cols="auto">
        <h1 class="text-h5 font-weight-bold">Transfer</h1>
        <div class="d-flex align-center gap-2 mt-1">
          <span class="text-body-2 text-medium-emphasis">Location:</span>
          <VChip
            size="small"
            color="primary"
            variant="tonal"
            prepend-icon="ri-factory-line"
          >
            {{ activeTab === 'pending' ? 'All Plants' : (plantSelectionStore.selectedPlantName || 'All Plants') }}
          </VChip>
          <PlantSelector v-if="activeTab === 'transferred'" @change="fetchData" />
        </div>
      </VCol>
      <VCol cols="auto">
        <VBtn
          v-if="activeTab === 'transferred'"
          color="primary"
          prepend-icon="ri-add-line"
          @click="openTransferModal"
        >
          New Transfer Entry
        </VBtn>
      </VCol>
    </VRow>

    <VTabs v-model="activeTab" color="primary" class="mb-4">
      <VTab value="transferred">Transferred</VTab>
      <VTab value="pending">
        <span class="d-flex align-center">
          Pending
          <VBadge v-if="transferStore.hasPending && activeTab === 'transferred'" dot color="error" class="ml-1 blink-badge" />
        </span>
      </VTab>
    </VTabs>

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
        <VBtn color="error" variant="tonal" size="small" class="mt-3 align-self-start" @click="loadData">
          Try again
        </VBtn>
      </div>
    </VAlert>

    <VWindow v-else v-model="activeTab">
      <!-- Transferred Tab -->
      <VWindowItem value="transferred">
        <VCard :class="{ 'opacity-50 pe-none': transferStore.loading }">
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
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created At</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created By</th>
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
                <td class="text-caption text-medium-emphasis" style="cursor:pointer;text-decoration:underline" @click="openSubTankModal(trf)">{{ trf.sloc }}</td>
                
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
                <td class="text-caption text-medium-emphasis">{{ trf.created_at ? new Date(trf.created_at).toLocaleString() : '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ trf.created_by }}</td>
                <td class="text-center">
                  <VBtn
                    v-if="!trf.next_process"
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
                <td colspan="13" class="text-center pa-8">
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
    </VCard></VWindowItem>

      <!-- Pending Tab -->
      <VWindowItem value="pending">
        <VCard :class="{ 'opacity-50 pe-none': transferStore.loading }">
          <VCardText class="pa-0">
            <div class="overflow-x-auto">
              <VTable density="compact" class="text-body-2">
                <thead>
                  <tr>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:48px">No</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date')">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'plant_name' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('plant_name')">Plant<VIcon v-if="sortKey==='plant_name'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_no')">Entry No<VIcon v-if="sortKey==='entry_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material')">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('qty')">Qty (MT)<VIcon v-if="sortKey==='qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Source Sloc</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Dest Sloc</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Submitted By</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Submitted At</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:100px">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(trf, index) in sortedPendingList" :key="trf.id_approval">
                    <td class="text-caption text-medium-emphasis">{{ (pendingPage - 1) * pendingPerPage + index + 1 }}</td>
                    <td class="text-center">{{ formatDate(trf.entry_date) }}</td>
                    <td class="text-center font-weight-medium text-caption">{{ trf.plant_name || '-' }}</td>
                    <td class="text-center font-weight-medium font-mono text-caption">{{ trf.entry_no }}</td>
                    <td class="font-weight-medium text-truncate" style="max-width:160px" :title="trf.material">{{ trf.material_name || trf.id_material }}</td>
                    <td class="text-right font-monospace font-weight-bold text-caption">{{ trf.qty }}</td>
                    <td class="text-caption text-medium-emphasis">{{ trf.source_sloc }}</td>
                    <td class="text-caption text-medium-emphasis">{{ trf.dest_sloc }}</td>
                    <td class="text-caption text-medium-emphasis">{{ trf.submitted_by }}</td>
                    <td class="text-caption text-medium-emphasis">{{ trf.submitted_at ? new Date(trf.submitted_at).toLocaleString() : '-' }}</td>
                    <td class="text-center">
                      <VBtn
                        icon="ri-check-line"
                        size="x-small"
                        color="success"
                        variant="tonal"
                        @click="approveTransfer(trf)"
                      />
                    </td>
                  </tr>
                  <tr v-if="sortedPendingList.length === 0">
                    <td colspan="12" class="text-center pa-8">
                      <VIcon icon="ri-inbox-2-line" size="40" class="text-disabled mb-2" />
                      <p class="text-body-2 text-medium-emphasis">No pending transfers</p>
                    </td>
                  </tr>
                </tbody>
              </VTable>
            </div>

            <div v-if="transferStore.pendingHistoryPagination.total > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
              <div class="d-flex align-center gap-3">
                <span class="text-caption text-medium-emphasis">
                  Showing {{ (pendingPage - 1) * pendingPerPage + 1 }} - {{ Math.min(pendingPage * pendingPerPage, transferStore.pendingHistoryPagination.total) }} of {{ transferStore.pendingHistoryPagination.total }} records
                </span>
                <VSelect
                  v-model="pendingPerPage"
                  :items="[5, 10, 15, 20]"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="min-width: 80px; max-width: 100px;"
                />
              </div>
              <VPagination
                v-if="pendingLastPage > 1"
                v-model="pendingPage"
                :length="pendingLastPage"
                :total-visible="5"
                density="comfortable"
                size="small"
                show-first-last-page
                @update:model-value="changePendingPage"
              />
            </div>
          </VCardText>
        </VCard>
      </VWindowItem>
    </VWindow>

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
    <TransferFromToSlocModal
      v-model:is-open="isSubTankModalOpen"
      :from-sloc-ids="parseSlocIds(subTankModalData.fromSlocRaw)"
      :to-sloc-ids="parseSlocIds(subTankModalData.toSlocRaw)"
      :from-desc="subTankModalData.fromDesc"
      :to-desc="subTankModalData.toDesc"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useTsTransferStore } from '@/modules/ts-transfer/stores'
import { useToastStore } from '@/stores/toast.js'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import MaterialDocModal from '@/modules/ts-transfer/components/MaterialDocModal.vue'
import TransferEntryModal from '@/modules/ts-transfer/components/TransferEntryModal.vue'
import TransferFromToSlocModal from '@/modules/ts-transfer/components/TransferFromToSlocModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const transferStore = useTsTransferStore()
const toastStore = useToastStore()

const isMatlDocModalOpen = ref(false)
const isTransferModalOpen = ref(false)
const isSubTankModalOpen = ref(false)
const subTankModalData = ref({ fromSlocRaw: '', toSlocRaw: '', fromDesc: '', toDesc: '' })
const deactivatingId = ref(null)
const selectedTransfer = ref(null)
const matlDocMode = ref('ADD')
const matlDocIdTraceHead = ref(null)
const matlDocCurrentNumber = ref('')
let pendingCheckInterval = null

function parseSlocIds(raw) {
  if (!raw) return []
  try {
    const decoded = JSON.parse(raw)
    return Array.isArray(decoded) ? decoded.map(Number) : [Number(raw)]
  } catch {
    return [Number(raw)]
  }
}

// Tab for transferred/pending view
const activeTab = ref('transferred')

// Pending history pagination
const pendingPage = ref(1)
const pendingPerPage = ref(10)

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
const perPage = ref(10)

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

const sortedPendingList = computed(() => {
  if (!sortKey.value || !sortDir.value) return transferStore.pendingHistoryList
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...transferStore.pendingHistoryList]
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
const pendingLastPage = computed(() => transferStore.pendingHistoryPagination.lastPage)

function loadData() {
  if (activeTab.value === 'pending') {
    fetchPendingHistory()
  } else {
    fetchData()
  }
}

async function fetchData() {
  const plantId = plantSelectionStore.selectedPlantId || 0
  await transferStore.fetchTransferList(plantId, page.value, perPage.value)
}

async function fetchPendingHistory() {
  await transferStore.fetchPendingHistory(pendingPage.value, pendingPerPage.value)
}

async function changePage(p) {
  if (p < 1 || p > lastPage.value) return
  page.value = p
  transferStore.setPage(p)
  await fetchData()
}

async function changePendingPage(p) {
  if (p < 1 || p > pendingLastPage.value) return
  pendingPage.value = p
  await fetchPendingHistory()
}

function openTransferModal() {
  isTransferModalOpen.value = true
}

function openSubTankModal(trf) {
  subTankModalData.value = {
    fromSlocRaw: trf.raw_id_sloc_from || '[]',
    toSlocRaw: trf.raw_id_sloc_to || '[]',
    fromDesc: trf.from_desc || trf.from_plant_name || '',
    toDesc: trf.to_desc || trf.plant_name || ''
  }
  isSubTankModalOpen.value = true
}

function onSubTankSuccess() {
  isSubTankModalOpen.value = false
  loadData()
}

function onTransferSuccess() {
  isTransferModalOpen.value = false
  loadData()
  transferStore.checkPendingCount()
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
  loadData()
}

async function deactivateTransfer(trf) {
  const compoundId = (trf.id_balance_head || trf.idHead) + '|' + (trf.id_trace_head || trf.idTraceHead)
  deactivatingId.value = trf.id_balance_head || trf.idHead
  try {
    await transferStore.deleteTransfer(compoundId)
    toastStore.success('Transfer deactivated successfully')
    loadData()
  } catch (error) {
    toastStore.error(error.message || 'Failed to deactivate transfer')
  } finally {
    deactivatingId.value = null
  }
}

async function approveTransfer(trf) {
  try {
    await transferStore.approvePendingTransfer(trf.id_balance_head)
  } catch (error) {
    toastStore.error(error.message || 'Failed to approve transfer')
  }
}

// Watch tab to switch views
watch(activeTab, (val) => {
  page.value = 1
  pendingPage.value = 1
  if (val === 'pending') {
    transferStore.pendingCount = 0
  }
  loadData()
})

watch(() => plantSelectionStore.selectedPlantId, () => {
  page.value = 1
  fetchData()
}, { immediate: true })

watch(perPage, () => {
  page.value = 1
  fetchData()
})

watch(pendingPerPage, () => {
  pendingPage.value = 1
  fetchPendingHistory()
})

onMounted(() => {
  loadData()
  transferStore.checkPendingCount()
  pendingCheckInterval = setInterval(() => {
    transferStore.checkPendingCount()
  }, 30000)
})

onUnmounted(() => {
  if (pendingCheckInterval) {
    clearInterval(pendingCheckInterval)
  }
})
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
.blink-badge {
  animation: blink 1s infinite;
}
@keyframes blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}
</style>