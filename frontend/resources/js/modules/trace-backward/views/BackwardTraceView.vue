<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-4">
      <VCol cols="12">
        <h1 class="text-h5 font-weight-bold">EUDR-TS BACKWARD TRACING</h1>
      </VCol>
    </VRow>

    <VCard class="mb-6">
      <VCardText>
        <VRow align="end">
          <VCol cols="12" md="3">
            <VSelect
              v-model="filters.id_plant"
              :items="[{ title: 'All Plants', value: '' }]"
              label="Plant"
              density="compact"
              variant="outlined"
              hide-details
            />
          </VCol>
          <VCol cols="12" md="4">
            <VTextField
              v-model="filters.search"
              label="Search trace no / SO no / material / batch"
              density="compact"
              variant="outlined"
              hide-details
              clearable
              @keyup.enter="loadData"
              @click:clear="onClearSearch"
            />
          </VCol>
          <VCol cols="12" md="auto" class="d-flex gap-2">
            <VBtn
              color="primary"
              prepend-icon="ri-search-line"
              @click="loadData"
            >
              Search
            </VBtn>
            <VBtn
              variant="outlined"
              @click="resetFilters"
            >
              Reset
            </VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard>
      <VCardTitle class="bg-neutral-50 text-uppercase text-body-2 font-weight-bold py-3 d-flex justify-space-between align-center">
        <span>Backward Trace List — Shipments</span>
        <VChip size="small">Total: {{ meta.total }}</VChip>
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-0">
        <div v-if="loading" class="pa-8 text-center text-medium-emphasis">
          <VProgressCircular indeterminate color="primary" class="mb-2" />
          <div class="text-caption">Loading data...</div>
        </div>

        <div v-else-if="error" class="pa-8 text-center text-error">
          <VIcon icon="ri-error-warning-line" size="40" class="mb-2" />
          <div class="text-body-2">{{ error }}</div>
        </div>

        <div v-else-if="list.length === 0" class="pa-8 text-center text-medium-emphasis">
          <VIcon icon="ri-arrow-left-double-line" size="40" class="mb-2" />
          <div class="text-caption">No backward trace records found.</div>
        </div>

        <div v-else class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date')">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('trace_no')">Trace No<VIcon v-if="sortKey==='trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'so_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('so_no')">SO No<VIcon v-if="sortKey==='so_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'batch_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('batch_no')">Batch No<VIcon v-if="sortKey==='batch_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="white-space:nowrap">Sloc</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material')">Product Desc<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" style="white-space:nowrap">Qty (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap;max-width:240px" @click="toggleSort('supplier')">Supplier / Batch SAP / Qty (MT)<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="white-space:nowrap;max-width:240px">Source Trace No / PO</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="white-space:nowrap">Created At</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="white-space:nowrap">Created By</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:120px">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in list" :key="row.id_shipment_head">
                <td class="text-center text-caption text-medium-emphasis">{{ (meta.page - 1) * meta.perPage + i + 1 }}</td>
                <td class="text-caption text-medium-emphasis">{{ row.entry_date }}</td>
                <td class="font-weight-medium font-mono text-caption">{{ row.trace_no }}</td>
                <td class="font-weight-medium font-mono text-caption">{{ row.so_no || '-' }}</td>
                <td class="font-weight-medium font-mono text-caption">{{ row.batch_no || '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ row.sloc || '-' }}</td>
                <td class="font-weight-medium text-caption" style="max-width:240px" :title="row.material">
                  <div class="text-truncate">{{ row.material }}</div>
                </td>
                <td class="text-right font-weight-bold font-mono text-caption">{{ row.qty }}</td>
                <td style="max-width:320px; white-space: normal;" class="text-caption">
                  <div v-if="row.supplier">
                    <div v-for="(item, idx) in formatSupplier(row.supplier)" :key="idx" class="mb-1 pa-1.5 rounded border bg-grey-lighten-4">
                      <div class="font-weight-bold text-primary" style="font-size: 11px;">{{ item.supplier }}</div>
                      <div class="d-flex justify-space-between align-center mt-1" style="font-size: 11px;">
                        <span>Batch SAP: <span class="font-weight-bold font-mono">{{ item.batch || '-' }}</span></span>
                        <span>Qty: <span class="font-weight-bold text-success">{{ item.qty || '-' }}</span></span>
                      </div>
                    </div>
                  </div>
                  <span v-else>-</span>
                </td>
                <td style="max-width:320px; white-space: normal;" class="text-caption text-medium-emphasis">
                  <div v-if="row.source || row.po_so">
                    <div v-for="(item, idx) in formatSource(row.source || row.po_so)" :key="idx" class="mb-1 pa-1.5 rounded border bg-grey-lighten-4">
                      <div v-if="item.poso" class="font-weight-bold text-info" style="font-size: 11px;">PO/SO: {{ item.poso }}</div>
                      <div class="d-flex flex-column gap-0.5 mt-1" style="font-size: 11px;">
                        <span v-if="item.batch">Batch SAP: <span class="font-weight-bold font-mono">{{ item.batch }}</span></span>
                        <span v-if="item.trace">Trace No: <span class="font-weight-bold font-mono">{{ item.trace }}</span></span>
                      </div>
                    </div>
                  </div>
                  <span v-else>-</span>
                </td>
                <td class="text-caption text-medium-emphasis">{{ row.created_at || '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ row.created_by || '-' }}</td>
                <td class="text-center">
                  <div class="d-flex justify-center gap-1">
                    <VBtn
                      icon="ri-eye-line"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      title="View Trace"
                      @click="openTraceModal(row)"
                    />
                    <VBtn
                      icon="ri-ship-line"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      title="View Shipment Detail"
                      @click="openShipmentDetailModal(row)"
                    />
                    <VBtn
                      icon="ri-archive-line"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                      title="View Batch Packaging"
                      @click="openBatchPackagingModal(row)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>

        <div v-if="meta.total > 0 && !loading" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (meta.page - 1) * perPage + 1 }} - {{ Math.min(meta.page * perPage, meta.total) }} of {{ meta.total }} records
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
            v-if="meta.lastPage > 1"
            v-model="meta.page"
            :length="meta.lastPage"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="changePage"
          />
        </div>
      </VCardText>
    </VCard>

    <TraceDetailModal
      v-model="showModal"
      mode="backward"
      :trace-no="modalTraceNo"
      :items="detail"
      :loading="loadingDetail"
    />

    <ShipmentDetailModal
      v-model="showShipmentModal"
      :so-no="selectedRow?.so_no || ''"
      :data="shipmentData"
      :loading="loadingShipment"
    />

    <BatchPackagingModal
      v-model="showBatchModal"
      :batch-no="selectedRow?.batch_no || ''"
      :data="batchData"
      :prep-records="preparationRecords"
      :sap-allocs="sapAllocations"
      :loading="loadingBatch"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useTraceBackwardStore } from '../stores/traceBackwardStore'
import { parseSoNo } from '../utils/parseSoNo'
import TraceDetailModal from '@/modules/shared/components/TraceDetailModal.vue'
import ShipmentDetailModal from '../components/ShipmentDetailModal.vue'
import BatchPackagingModal from '../components/BatchPackagingModal.vue'

const store = useTraceBackwardStore()
const { listMeta: meta, list, detail, shipmentData, batchData, preparationRecords, sapAllocations, loading, loadingDetail, loadingShipment, loadingBatch, error } = storeToRefs(store)

const filters = reactive({ id_plant: '', search: '' })
const showModal = ref(false)
const modalTraceNo = ref('')
const showShipmentModal = ref(false)
const showBatchModal = ref(false)
const selectedRow = ref(null)

const sortKey = ref('entry_date')
const sortDir = ref('desc')
const perPage = ref(meta.value.perPage)

// ponytail: server-sort only — columns not in backend whitelist are non-sortable
const SORTABLE = new Set(['entry_date', 'trace_no', 'so_no', 'material', 'batch_no', 'supplier'])

onMounted(() => loadData())

function formatSupplier(val) {
  if (!val) return []
  return val.split(' | ').map(item => {
    const parts = item.split(' / ')
    return {
      supplier: parts[0] || '',
      batch: parts[1] || '',
      qty: (parts[2] || '').replace('Qty : ', '').replace('Qty: ', ''),
    }
  })
}

function formatSource(val) {
  if (!val) return []
  return val.split(' | ').map(item => {
    const parts = item.split(' / ')
    const subParts = (parts[0] || '').split(' :: ')
    return {
      batch: subParts[0] || '',
      trace: subParts[1] || '',
      poso: parts[1] || '',
    }
  })
}

function toggleSort(key) {
  if (!SORTABLE.has(key)) return
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
  meta.value.page = 1
  loadData()
}

watch(perPage, newVal => {
  meta.value.perPage = newVal
  meta.value.page = 1
  loadData()
})

async function loadData() {
  const params = {
    page: meta.value.page,
    per_page: meta.value.perPage,
    sort_by: sortKey.value,
    sort_dir: sortDir.value,
  }
  if (filters.id_plant) params.id_plant = filters.id_plant
  if (filters.search) params.search = filters.search
  await store.fetchList(params)
}

function onClearSearch() {
  filters.search = ''
  meta.value.page = 1
  loadData()
}

async function changePage(p) {
  if (p < 1 || p > meta.value.lastPage) return
  store.setPage(p)
  await loadData()
}

function resetFilters() {
  filters.id_plant = ''
  filters.search = ''
  sortKey.value = 'entry_date'
  sortDir.value = 'desc'
  meta.value.page = 1
  loadData()
}

async function openTraceModal(row) {
  modalTraceNo.value = row.trace_no
  showModal.value = true
  await store.fetchDetail({ trace_no: row.trace_no, id_material: row.id_material })
}

async function openShipmentDetailModal(row) {
  selectedRow.value = row
  showShipmentModal.value = true
  const { soNo, soItem, batchNo } = parseSoNo(row.so_no, row.batch_no)
  await store.fetchShipmentDetail({ batchNo, soNo, soItem })
}

async function openBatchPackagingModal(row) {
  selectedRow.value = row
  showBatchModal.value = true
  await store.fetchBatchPackaging({ batchNo: row.batch_no || '' })
}
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>
