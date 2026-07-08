<template>
  <div class="pa-6">
    <h1 class="text-h5 font-weight-bold mb-4">Summary of Raw Material to Product</h1>

    <VCard class="mb-4">
      <VCardTitle class="d-flex align-center ga-2 pa-5">
        <VIcon icon="ri-flask-line" color="primary" size="24" />
        <span class="text-h6 font-weight-bold">Summary of Raw Material to Product</span>
      </VCardTitle>
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="3">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Year</label>
            <VSelect
              v-model="selectedYear"
              :items="years.map(y => ({ value: y, title: String(y) }))"
              density="compact"
              variant="outlined"
              class="mt-1"
            />
          </VCol>
          <VCol cols="12" md="2" class="d-flex align-end">
            <VBtn color="primary" prepend-icon="ri-search-line" block @click="loadData">Search</VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard class="mb-4">
      <VCardText class="pa-0">
        <div v-if="loading" class="d-flex flex-column align-center justify-center pa-8">
          <VProgressCircular indeterminate color="primary" size="32" />
          <span class="mt-3 text-body-2 text-medium-emphasis">Loading report data...</span>
        </div>
        <template v-else>
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('trace_no')">
                    Trace No<span class="sort-icon" :class="{ active: sortKey === 'trace_no', desc: sortKey === 'trace_no' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('entry_date')">
                    Entry Date<span class="sort-icon" :class="{ active: sortKey === 'entry_date', desc: sortKey === 'entry_date' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('material_document')">
                    Matl Doc<span class="sort-icon" :class="{ active: sortKey === 'material_document', desc: sortKey === 'material_document' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('po_so')">
                    PurchO<span class="sort-icon" :class="{ active: sortKey === 'po_so', desc: sortKey === 'po_so' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('material')">
                    Material<span class="sort-icon" :class="{ active: sortKey === 'material', desc: sortKey === 'material' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('manufacturer_name')">
                    Manufacturer<span class="sort-icon" :class="{ active: sortKey === 'manufacturer_name', desc: sortKey === 'manufacturer_name' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('tank')">
                    Sloc<span class="sort-icon" :class="{ active: sortKey === 'tank', desc: sortKey === 'tank' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" @click="toggleSort('init_qty')">
                    Init (MT)<span class="sort-icon" :class="{ active: sortKey === 'init_qty', desc: sortKey === 'init_qty' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" @click="toggleSort('qty')">
                    On-Hand (MT)<span class="sort-icon" :class="{ active: sortKey === 'qty', desc: sortKey === 'qty' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" @click="toggleSort('qty_tank')">
                    On-WIP (MT)<span class="sort-icon" :class="{ active: sortKey === 'qty_tank', desc: sortKey === 'qty_tank' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" @click="toggleSort('qty_warehouse')">
                    On-PRD (MT)<span class="sort-icon" :class="{ active: sortKey === 'qty_warehouse', desc: sortKey === 'qty_warehouse' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" @click="toggleSort('qty_adjustment')">
                    On-ADJOUT (MT)<span class="sort-icon" :class="{ active: sortKey === 'qty_adjustment', desc: sortKey === 'qty_adjustment' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('supplier')">
                    Supplier / Batch SAP / Init Qty (MT)<span class="sort-icon" :class="{ active: sortKey === 'supplier', desc: sortKey === 'supplier' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" @click="toggleSort('id_balance_detail')">
                    Detail IDs<span class="sort-icon" :class="{ active: sortKey === 'id_balance_detail', desc: sortKey === 'id_balance_detail' && sortDir === 'desc' }">▲</span>
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center">
                    Traced
                  </th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:80px">Action</th>
                </tr>
              </thead>
              <tbody v-if="tableData.length === 0">
                <tr>
                  <td colspan="17" class="text-center pa-8">
                    <VIcon icon="ri-flask-line" size="48" class="text-disabled mb-2" />
                    <p>No data for {{ selectedYear }}.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(row, i) in paginatedList" :key="row.id_balance_head || i">
                  <td class="text-center text-caption text-medium-emphasis">{{ (page - 1) * perPage + i + 1 }}</td>
                  <td class="font-monospace text-caption">{{ row.trace_no }}</td>
                  <td class="text-caption">{{ row.entry_date }}</td>
                  <td class="font-monospace text-caption">{{ row.material_document || '-' }}</td>
                  <td class="font-monospace text-caption">{{ row.po_so || '-' }}</td>
                  <td class="font-weight-medium text-truncate text-caption" style="max-width:200px" :title="row.material">{{ row.material }}</td>
                  <td class="text-caption">{{ row.manufacturer_name || '-' }}</td>
                  <td class="text-caption">{{ row.tf_number || '-' }}</td>
                  <td class="text-right font-monospace text-caption">{{ row.init_qty || row.qty }}</td>
                  <td class="text-right font-monospace font-weight-bold text-caption">{{ row.qty }}</td>
                  <td class="text-right font-monospace text-caption">{{ row.qty_tank || '-' }}</td>
                  <td class="text-right font-monospace text-caption">{{ row.qty_warehouse || '-' }}</td>
                  <td class="text-right font-monospace text-caption">{{ row.qty_adjustment || '-' }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="row.supplier">{{ row.supplier || '-' }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="row.id_balance_detail">{{ row.id_balance_detail || '-' }}</td>
                  <td class="text-center">{{ row.traced || '-' }}</td>
                  <td class="text-center">
                    <VBtn size="x-small" color="success" variant="tonal" prepend-icon="ri-eye-line" @click="openDetail(row)">
                      Detail
                    </VBtn>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="totalRecords > 0" class="px-4 py-2 text-caption text-medium-emphasis bg-neutral-50 d-flex justify-space-between align-center">
            <div class="d-flex align-center ga-3">
              <div class="d-flex align-center ga-1">
                <span>Rows:</span>
                <VSelect
                  v-model="perPage"
                  :items="[5, 10, 15, 20]"
                  density="compact"
                  variant="plain"
                  hide-details
                  style="width:70px"
                />
              </div>
              <span>Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, totalRecords) }} of {{ totalRecords }} records</span>
            </div>
            <VPagination
              v-if="lastPage > 1"
              v-model="page"
              :length="lastPage"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
              @update:model-value="loadData"
            />
          </div>
        </template>
      </VCardText>
    </VCard>

    <VDialog v-model="showModal" max-width="960" scrollable>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-4">
          <div class="d-flex align-center ga-2">
            <VIcon icon="ri-flask-line" color="primary" size="20" />
            <span class="text-body-1 font-weight-bold">Detail RM Traceability</span>
            <span class="text-caption text-medium-emphasis ml-2">Batch SAP: {{ detailBatch }} | Qty RM: {{ detailQty }} MT</span>
          </div>
          <VBtn icon="ri-close-line" size="small" variant="text" @click="showModal = false" />
        </VCardTitle>

        <VTabs v-model="detailTab" color="primary" align-tabs="start">
          <VTab v-for="tab in detailTabs" :key="tab.key" :value="tab.key" :prepend-icon="tab.icon">
            {{ tab.label }}
          </VTab>
        </VTabs>

        <VCardText class="pa-0">
          <VWindow v-model="detailTab">
            <VWindowItem value="wip">
              <VTable density="compact" class="text-body-2">
                <thead>
                  <tr>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sloc</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">IN Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">OUT Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Balance</th>
                  </tr>
                </thead>
                <tbody v-if="detailWip.length === 0">
                  <tr><td colspan="6" class="text-center pa-4 text-disabled text-body-2">No WIP data.</td></tr>
                </tbody>
                <tbody v-else>
                  <tr v-for="(d, i) in detailWip" :key="i">
                    <td class="text-center">{{ i + 1 }}</td>
                    <td>{{ d.sloc || '-' }}</td>
                    <td>{{ d.material || '-' }}</td>
                    <td class="text-right font-monospace">{{ d.in_qty || '0.000' }}</td>
                    <td class="text-right font-monospace">{{ d.out_qty || '0.000' }}</td>
                    <td class="text-right font-monospace font-weight-bold">{{ d.balance || '0.000' }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>
            <VWindowItem value="prd">
              <VTable density="compact" class="text-body-2">
                <thead>
                  <tr>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sloc</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">IN Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">OUT Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Balance</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Shipment</th>
                  </tr>
                </thead>
                <tbody v-if="detailPrd.length === 0">
                  <tr><td colspan="7" class="text-center pa-4 text-disabled text-body-2">No PRODUCT data.</td></tr>
                </tbody>
                <tbody v-else>
                  <tr v-for="(d, i) in detailPrd" :key="i">
                    <td class="text-center">{{ i + 1 }}</td>
                    <td>{{ d.sloc || '-' }}</td>
                    <td>{{ d.material || '-' }}</td>
                    <td class="text-right font-monospace">{{ d.in_qty || '0.000' }}</td>
                    <td class="text-right font-monospace">{{ d.out_qty || '0.000' }}</td>
                    <td class="text-right font-monospace font-weight-bold">{{ d.balance || '0.000' }}</td>
                    <td>{{ d.shipment || '-' }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>
            <VWindowItem value="adj">
              <VTable density="compact" class="text-body-2">
                <thead>
                  <tr>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sloc</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">IN Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">OUT Qty</th>
                    <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Balance</th>
                  </tr>
                </thead>
                <tbody v-if="detailAdj.length === 0">
                  <tr><td colspan="6" class="text-center pa-4 text-disabled text-body-2">No ADJUSTMENT data.</td></tr>
                </tbody>
                <tbody v-else>
                  <tr v-for="(d, i) in detailAdj" :key="i">
                    <td class="text-center">{{ i + 1 }}</td>
                    <td>{{ d.sloc || '-' }}</td>
                    <td>{{ d.material || '-' }}</td>
                    <td class="text-right font-monospace">{{ d.in_qty || '0.000' }}</td>
                    <td class="text-right font-monospace">{{ d.out_qty || '0.000' }}</td>
                    <td class="text-right font-monospace font-weight-bold">{{ d.balance || '0.000' }}</td>
                  </tr>
                </tbody>
              </VTable>
            </VWindowItem>
          </VWindow>
        </VCardText>

        <VDivider />

        <VCardActions class="pa-4 justify-end">
          <VBtn variant="outlined" color="medium-emphasis" @click="showModal = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRmReportStore } from '../stores/rmReportStore'

const rmReportStore = useRmReportStore()
const selectedYear = ref(new Date().getFullYear())
const loading = ref(false)
const tableData = ref([])
const page = ref(1)
const perPage = ref(10)
const sortKey = ref('')
const sortDir = ref('asc')

const detectColumnType = (key) => {
  const numericFields = ['init_qty', 'qty', 'qty_tank', 'qty_warehouse', 'qty_adjustment']
  return numericFields.includes(key) ? 'number' : 'string'
}

const toggleSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortedList = computed(() => {
  if (!sortKey.value) return tableData.value
  const list = [...tableData.value]
  const type = detectColumnType(sortKey.value)
  list.sort((a, b) => {
    const valA = a[sortKey.value] ?? ''
    const valB = b[sortKey.value] ?? ''
    let cmp
    if (type === 'number') {
      cmp = parseFloat(valA) - parseFloat(valB)
    } else {
      cmp = String(valA).localeCompare(String(valB))
    }
    return sortDir.value === 'asc' ? cmp : -cmp
  })
  return list
})

const paginatedList = computed(() => {
  const start = (page.value - 1) * perPage.value
  const end = start + perPage.value
  return sortedList.value.slice(start, end)
})

const totalRecords = computed(() => {
  if (Array.isArray(rmReportStore.rmReportSummary)) {
    return rmReportStore.rmReportSummary.length
  }
  return rmReportStore.rmReportSummary.total || 0
})

const lastPage = computed(() => {
  if (Array.isArray(rmReportStore.rmReportSummary)) {
    return Math.ceil(totalRecords.value / perPage.value) || 1
  }
  return rmReportStore.rmReportSummary.last_page || 1
})
const showModal = ref(false)
const detailBatch = ref('')
const detailQty = ref('')
const detailTab = ref('wip')
const detailWip = ref([])
const detailPrd = ref([])
const detailAdj = ref([])

const years = []
for (let i = 0; i < 5; i++) years.push(new Date().getFullYear() - i)

const detailTabs = [
  { key: 'wip', label: 'On-WIP', icon: 'ri-settings-line' },
  { key: 'prd', label: 'On-PRODUCT', icon: 'ri-box-3-line' },
  { key: 'adj', label: 'On-ADJUSTMENT', icon: 'ri-tune-line' }
]

onMounted(() => loadData())

const loadData = async () => {
  loading.value = true
  try {
    await rmReportStore.fetchRmReportSummary({
      year: selectedYear.value,
      selectedYear: selectedYear.value,
      page: page.value,
      per_page: perPage.value
    })
    const responseData = rmReportStore.rmReportSummary
    tableData.value = Array.isArray(responseData) ? responseData : (responseData.data || [])
  } catch {
    tableData.value = []
  } finally {
    loading.value = false
  }
}

watch(perPage, () => {
  page.value = 1
  loadData()
})

const openDetail = async (row) => {
  showModal.value = true
  detailBatch.value = row.batch_sap || row.trace_no || '-'
  detailQty.value = row.qty || '0'
  detailTab.value = 'wip'
  
  detailWip.value = []
  detailPrd.value = []
  detailAdj.value = []

  const batchSap = row.batch_sap || row.trace_no

  if (batchSap) {
    try {
      const [tankData, prdData, adjData] = await Promise.all([
        rmReportStore.fetchDetailOnTank({ batchSap }),
        rmReportStore.fetchDetailOnWarehouse({ batchSap }),
        rmReportStore.fetchDetailOnAdjOut({ batchSap })
      ])
      
      detailWip.value = Array.isArray(tankData) ? tankData : []
      detailPrd.value = Array.isArray(prdData) ? prdData : []
      detailAdj.value = Array.isArray(adjData) ? adjData : []
    } catch (err) {
      console.error('Failed to fetch details:', err)
    }
  } else {
    const baseRow = row.supplier
      ? [{ sloc: row.tf_number || '-', material: row.material, in_qty: row.init_qty || '0', out_qty: '0', balance: row.qty || '0' }]
      : []
    detailWip.value = baseRow
    detailPrd.value = row.supplier
      ? [{ sloc: row.tf_number || '-', material: row.material, in_qty: row.init_qty || '0', out_qty: '0', balance: row.qty || '0', shipment: '-' }]
      : []
    detailAdj.value = baseRow
  }
}
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>
