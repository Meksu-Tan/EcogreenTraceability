<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-4">
      <VCol cols="auto">
        <VRow align="center" no-gutters>
          <VCol cols="auto">
            <h1 class="text-h5 font-weight-bold">Packaging Entry</h1>
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
          New Packaging Entry
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
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'po_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('po_no')">PO No<VIcon v-if="sortKey==='po_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'batch_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('batch_no')">PPH Batch No<VIcon v-if="sortKey==='batch_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'feed' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('feed')">WIP Product<VIcon v-if="sortKey==='feed'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'fg' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('fg')">FG Product<VIcon v-if="sortKey==='fg'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc')">Sloc<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'whx' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('whx')">FG Sloc<VIcon v-if="sortKey==='whx'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'init_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('init_qty')">Init Material (MT)<VIcon v-if="sortKey==='init_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier')">Init Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance')">Balance (MT)<VIcon v-if="sortKey==='balance'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="min-width:200px">Supplier / Batch SAP / Init / Balance</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created At</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created By</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:120px">Action</th>
              </tr>
            </thead>
            <tbody v-if="sortedList.length > 0">
              <tr v-for="(row, index) in sortedList" :key="row.id_whx_head">
                <td class="text-caption text-medium-emphasis text-center">{{ (page - 1) * perPage + index + 1 }}</td>
                <td class="text-center">{{ row.entry_date }}</td>
                <td class="text-center font-weight-medium font-mono text-caption">{{ row.fromto_trace_no }}</td>
                <td class="text-center font-weight-medium text-caption">{{ row.plant_name || '-' }}</td>
                <td class="text-center">{{ row.po_no || '-' }}</td>
                <td class="text-center"><VChip size="x-small" variant="flat" color="neutral-100">{{ row.batch_no || '-' }}</VChip></td>
                <td class="font-weight-medium text-truncate" style="max-width:160px" :title="row.feed">{{ row.feed || '-' }}</td>
                <td class="font-weight-medium text-truncate" style="max-width:160px" :title="row.fg">{{ row.fg || '-' }}</td>
                <td class="text-center">
                  <a href="#" @click.prevent="openSubTankModal(row)" class="text-medium-emphasis text-decoration-underline text-caption">
                    {{ row.sloc || '-' }}
                  </a>
                </td>
                <td class="text-center">{{ row.whx || '-' }}</td>
                <td class="text-right font-monospace text-caption" :class="row.init_qty === row.balance_supplier ? 'text-success' : 'text-error'">{{ row.init_qty }}</td>
                <td class="text-right font-monospace text-caption" :class="row.init_qty === row.balance_supplier ? 'text-success' : 'text-error'">{{ row.balance_supplier }}</td>
                <td class="text-right font-monospace font-weight-bold text-caption">{{ row.balance }}</td>
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
                <td class="text-caption text-medium-emphasis">{{ row.created_at ? new Date(row.created_at.replace(' ', 'T')).toLocaleString() : '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ row.created_by }}</td>
                <td class="text-center">
                  <div class="d-flex justify-center gap-1">
                    <VBtn icon="ri-article-line" size="x-small" color="primary" variant="tonal" @click="openPoModal(row)" title="Edit PO" />
                    <VBtn icon="ri-edit-box-line" size="x-small" color="warning" variant="tonal" @click="openBatchModal(row)" title="Edit Batch & Warehouse" />
                    <VBtn icon="ri-delete-bin-7-line" size="x-small" color="error" variant="tonal" @click="onCancel(row)" title="Cancel Entry" />
                  </div>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr>
                <td colspan="17" class="text-center pa-8">
                  <VIcon icon="ri-inbox-2-line" size="40" class="text-disabled mb-2" />
                  <p class="text-body-2 text-medium-emphasis">No packaging data yet</p>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>

        <div v-if="store.pagination.total > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, store.pagination.total) }} of {{ store.pagination.total }} records
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

    <!-- Modals -->
    <PackageEntryModal v-model="showAddModal" @saved="fetchData" />
    <PackageEntryPoModal v-model="showPoModal" :row="selectedRow" @saved="fetchData" />
    <PackageEntryBatchModal v-model="showBatchModal" :row="selectedRow" @saved="fetchData" />
    <SelectSubTankModal v-model="showSubTankModal" :row="selectedRow" @saved="fetchData" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { usePackageEntryStore } from '../stores/usePackageEntryStore'
import { useConfirmStore } from '@/stores/confirm.js'
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

const page = ref(1)
const perPage = ref(10)
const sortKey = ref(null)
const sortDir = ref(null)

const lastPage = computed(() => store.pagination.lastPage)

async function changePage(p) {
  page.value = p
  store.setPage(p)
  await fetchData()
}

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
  await store.fetchEntries({ page: page.value, per_page: perPage.value })
}

watch(perPage, async () => {
  page.value = 1
  store.setPage(1)
  await fetchData()
})

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

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>