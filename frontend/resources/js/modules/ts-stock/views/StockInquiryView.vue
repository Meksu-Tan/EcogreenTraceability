<template>
  <div class="pa-6">
    <VCard class="mb-4">
      <VCardTitle class="pa-5 pb-2">
        <h1 class="text-h6 font-weight-bold">Stock On-Hand (WIP / Warehouse)</h1>
      </VCardTitle>
      <VCardText>
        <VRow dense class="mb-2">
          <VCol cols="12" md="6">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">On-Hand Report Type</label>
            <VSelect
              v-model="reportType"
              :items="[
                { value: 'detail', title: '- Detail Per Material -' },
                { value: 'summary', title: '- Summary All Material -' }
              ]"
              density="compact"
              variant="outlined"
              class="mt-1"
              @update:model-value="onReportTypeChange"
            />
          </VCol>
        </VRow>

        <template v-if="reportType === 'detail'">
          <VRow dense>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Start Date</label>
              <VTextField v-model="detailFilters.startDate" type="date" density="compact" variant="outlined" class="mt-1" />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">End Date</label>
              <VTextField v-model="detailFilters.endDate" type="date" density="compact" variant="outlined" class="mt-1" />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Type</label>
                <VSelect
                  v-model="detailFilters.stockType"
                  :items="[{ value: 'WIP', title: 'WIP' }, { value: 'WH', title: 'WH' }]"
                  density="compact"
                  variant="outlined"
                  class="mt-1"
                  @update:model-value="onStockTypeChange"
                />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Material</label>

              <VSelect
                v-model="detailFilters.materialId"
                :items="materialOptions"
                item-title="material"
                item-value="id_material"
                density="compact"
                variant="outlined"
                class="mt-1"
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Sloc</label>
              <VSelect
                v-model="detailFilters.sloc"
                :items="slocOptions"
                item-title="description"
                item-value="id_plant"
                density="compact"
                variant="outlined"
                class="mt-1"
              />
            </VCol>
            <VCol cols="12" sm="6" md="2" class="d-flex align-end">
              <VBtn color="primary" prepend-icon="ri-globe-line" block @click="onView">View</VBtn>
            </VCol>
          </VRow>
        </template>

        <template v-else>
          <VRow dense>
            <VCol cols="12" sm="6" md="3">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Start Date</label>
              <VTextField v-model="summaryFilters.startDate" type="date" density="compact" variant="outlined" class="mt-1" />
            </VCol>
            <VCol cols="12" sm="6" md="3">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">End Date</label>
              <VTextField v-model="summaryFilters.endDate" type="date" density="compact" variant="outlined" class="mt-1" />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Sloc</label>
              <VSelect
                v-model="summaryFilters.sloc"
                :items="slocOptions"
                item-title="description"
                item-value="id_plant"
                density="compact"
                variant="outlined"
                class="mt-1"
              />
            </VCol>
            <VCol cols="12" sm="6" md="2">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Sloc Type</label>
              <VSelect
                v-model="summaryFilters.slocType"
                :items="[{ value: 'SUMMARY_WIP', title: 'WIP' }, { value: 'SUMMARY_WH', title: 'WAREHOUSE' }]"
                density="compact"
                variant="outlined"
                class="mt-1"
              />
            </VCol>
            <VCol cols="12" sm="6" md="2" class="d-flex align-end">
              <VBtn color="primary" prepend-icon="ri-globe-line" block @click="onView">View</VBtn>
            </VCol>
          </VRow>
        </template>
      </VCardText>
    </VCard>

    <VCard v-if="reportType === 'detail' && !loading && !showRmTable" class="mb-4">
      <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
        <span class="text-body-1 font-weight-bold">Stock Detail</span>
      </VCardTitle>
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('entry_date','detail')">Stock Date <VIcon size="14">{{ sortIcon('entry_date','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('material','detail')">Description <VIcon size="14">{{ sortIcon('material','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('in','detail')">In (MT) <VIcon size="14">{{ sortIcon('in','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('tank','detail')">Sloc <VIcon size="14">{{ sortIcon('tank','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('out','detail')">Out (MT) <VIcon size="14">{{ sortIcon('out','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance','detail')">Balance Material (MT) <VIcon size="14">{{ sortIcon('balance','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance_supplier','detail')">Balance Supplier (MT) <VIcon size="14">{{ sortIcon('balance_supplier','detail') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('supplier','detail')">ID / Supplier / Batch SAP / Balance (MT) / Trace <VIcon size="14">{{ sortIcon('supplier','detail') }}</VIcon></th>
              </tr>
            </thead>
            <tbody v-if="stockData.length === 0">
              <tr><td colspan="9" class="text-center pa-8 text-disabled text-body-2">No data.</td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(row, i) in paginatedDetail" :key="row.id_balance_head">
                <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                <td>{{ row.entry_date }}</td>
                <td class="font-weight-medium text-truncate" style="max-width:250px" :title="row.material">{{ row.material || row.description }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.in) }}</td>
                <td>{{ row.tank || row.sloc || '-' }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.out) }}</td>
                <td class="text-right font-monospace font-weight-bold whitespace-nowrap" :class="bc(row)">{{ formatQty(row.balance || row.current_qty || row.qty) }}</td>
                <td class="text-right font-monospace whitespace-nowrap" :class="bc(row)">{{ row.balance_supplier || '0.000' }}</td>
                <td class="text-caption" style="min-width:400px">
                  <div class="d-flex flex-wrap ga-1" v-if="(row.supplier || row.supplier_details)">
                    <VChip size="small" density="comfortable" variant="flat" color="grey-lighten-3" class="text-black" v-for="item in (row.supplier || row.supplier_details).split('|')" :key="item" v-show="item.trim()">
                      {{ item.trim() }}
                    </VChip>
                  </div>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
      <div class="d-flex align-center justify-end pa-3 ga-4">
        <div class="d-flex align-center ga-2">
          <span class="text-caption text-medium-emphasis">Rows per page:</span>
          <VSelect v-model="perPageDetail" :items="[5, 10, 15, 20]" density="compact" variant="outlined" style="width:80px" hide-details />
        </div>
        <VPagination v-model="pageDetail" :length="Math.ceil(sortedDetail.length / perPageDetail)" size="small" :total-visible="7" />
      </div>
      </VCardText>
    </VCard>

    <VCard v-if="reportType === 'detail' && !loading && showRmTable" class="mb-4">
      <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
        <span class="text-body-1 font-weight-bold">RM Storage Detail</span>
      </VCardTitle>
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('entry_date','rm_storage')">Stock Date <VIcon size="14">{{ sortIcon('entry_date','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('material','rm_storage')">Description <VIcon size="14">{{ sortIcon('material','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('in','rm_storage')">In (MT) <VIcon size="14">{{ sortIcon('in','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('sloc','rm_storage')">Sloc <VIcon size="14">{{ sortIcon('sloc','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('out','rm_storage')">Out (MT) <VIcon size="14">{{ sortIcon('out','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance','rm_storage')">Balance Material (MT) <VIcon size="14">{{ sortIcon('balance','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance_supplier','rm_storage')">Balance Supplier (MT) <VIcon size="14">{{ sortIcon('balance_supplier','rm_storage') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('supplier','rm_storage')">ID / Batch SAP / Balance (MT) / Trace <VIcon size="14">{{ sortIcon('supplier','rm_storage') }}</VIcon></th>
              </tr>
            </thead>
            <tbody v-if="paginatedRmStorage.length === 0">
              <tr><td colspan="9" class="text-center pa-8 text-disabled text-body-2">No RM storage data.</td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(row, i) in paginatedRmStorage" :key="i">
                <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                <td>{{ row.entry_date }}</td>
                <td class="font-weight-medium text-truncate" style="max-width:250px">{{ row.material }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.in) }}</td>
                <td>{{ row.sloc || '-' }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.out) }}</td>
                <td class="text-right font-monospace font-weight-bold whitespace-nowrap" :class="bc(row)">{{ formatQty(row.balance || row.qty) }}</td>
                <td class="text-right font-monospace whitespace-nowrap" :class="bc(row)">{{ row.balance_supplier || '0.000' }}</td>
                <td class="text-caption" style="min-width:400px">
                  <div class="d-flex flex-wrap ga-1" v-if="row.supplier">
                    <VChip size="small" density="comfortable" variant="flat" color="grey-lighten-3" class="text-black" v-for="item in (row.supplier).split('|')" :key="item" v-show="item.trim()">
                      {{ item.trim() }}
                    </VChip>
                  </div>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
        <div class="d-flex align-center justify-end pa-3 ga-4">
          <div class="d-flex align-center ga-2">
            <span class="text-caption text-medium-emphasis">Rows per page:</span>
            <VSelect v-model="perPageRmStorage" :items="[5, 10, 15, 20]" density="compact" variant="outlined" style="width:80px" hide-details />
          </div>
          <VPagination v-model="pageRmStorage" :length="Math.ceil(sortedRmStorage.length / perPageRmStorage)" size="small" :total-visible="7" />
        </div>
      </VCardText>
    </VCard>

    <VCard v-if="reportType === 'detail' && !loading && showRmTable" class="mb-4">
      <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
        <span class="text-body-1 font-weight-bold">RM Feed Detail</span>
      </VCardTitle>
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('entry_date','rm_feed')">Stock Date <VIcon size="14">{{ sortIcon('entry_date','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('material','rm_feed')">Description <VIcon size="14">{{ sortIcon('material','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('in','rm_feed')">In (MT) <VIcon size="14">{{ sortIcon('in','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('sloc','rm_feed')">Sloc <VIcon size="14">{{ sortIcon('sloc','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('out','rm_feed')">Out (MT) <VIcon size="14">{{ sortIcon('out','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance','rm_feed')">Balance Material (MT) <VIcon size="14">{{ sortIcon('balance','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance_supplier','rm_feed')">Balance Supplier (MT) <VIcon size="14">{{ sortIcon('balance_supplier','rm_feed') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('supplier','rm_feed')">ID / Batch SAP / Balance (MT) / Trace <VIcon size="14">{{ sortIcon('supplier','rm_feed') }}</VIcon></th>
              </tr>
            </thead>
            <tbody v-if="paginatedRmFeed.length === 0">
              <tr><td colspan="9" class="text-center pa-8 text-disabled text-body-2">No RM feed data.</td></tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(row, i) in paginatedRmFeed" :key="i">
                <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                <td>{{ row.entry_date }}</td>
                <td class="font-weight-medium text-truncate" style="max-width:250px">{{ row.material }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.in) }}</td>
                <td>{{ row.sloc || '-' }}</td>
                <td class="text-right font-monospace whitespace-nowrap">{{ formatQty(row.out) }}</td>
                <td class="text-right font-monospace font-weight-bold whitespace-nowrap" :class="bc(row)">{{ formatQty(row.balance || row.qty) }}</td>
                <td class="text-right font-monospace whitespace-nowrap" :class="bc(row)">{{ row.balance_supplier || '0.000' }}</td>
                <td class="text-caption" style="min-width:400px">
                  <div class="d-flex flex-wrap ga-1" v-if="row.supplier">
                    <VChip size="small" density="comfortable" variant="flat" color="grey-lighten-3" class="text-black" v-for="item in (row.supplier).split('|')" :key="item" v-show="item.trim()">
                      {{ item.trim() }}
                    </VChip>
                  </div>
                  <span v-else>-</span>
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
        <div class="d-flex align-center justify-end pa-3 ga-4">
          <div class="d-flex align-center ga-2">
            <span class="text-caption text-medium-emphasis">Rows per page:</span>
            <VSelect v-model="perPageRmFeed" :items="[5, 10, 15, 20]" density="compact" variant="outlined" style="width:80px" hide-details />
          </div>
          <VPagination v-model="pageRmFeed" :length="Math.ceil(sortedRmFeed.length / perPageRmFeed)" size="small" :total-visible="7" />
        </div>
      </VCardText>
    </VCard>

    <VCard v-if="loading && reportType === 'detail'" class="d-flex flex-column align-center justify-center pa-8 mb-4">
      <VProgressCircular indeterminate color="primary" size="32" />
      <span class="mt-3 text-body-2 text-medium-emphasis">Loading...</span>
    </VCard>

    <VCard v-if="reportType === 'summary'">
      <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
        <span class="text-body-1 font-weight-bold">Stock Summary</span>
      </VCardTitle>
      <VCardText class="pa-0">
        <div v-if="loading" class="d-flex flex-column align-center justify-center pa-8">
          <VProgressCircular indeterminate color="primary" size="32" />
          <span class="mt-3 text-body-2 text-medium-emphasis">Loading...</span>
        </div>
        <template v-else>
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('entry_date','summary')">Stock Date <VIcon size="14">{{ sortIcon('entry_date','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('material','summary')">Description <VIcon size="14">{{ sortIcon('material','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('init_balance','summary')">Init Bal (MT) <VIcon size="14">{{ sortIcon('init_balance','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('in','summary')">Total In (MT) <VIcon size="14">{{ sortIcon('in','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('sloc','summary')">Sloc <VIcon size="14">{{ sortIcon('sloc','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('out','summary')">Total Out (MT) <VIcon size="14">{{ sortIcon('out','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('last_balance','summary')">Bal Material (MT) <VIcon size="14">{{ sortIcon('last_balance','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis text-right" @click="toggleSort('balance_supplier','summary')">Bal Supplier (MT) <VIcon size="14">{{ sortIcon('balance_supplier','summary') }}</VIcon></th>
                <th class="sortable-th text-caption font-weight-bold text-uppercase text-medium-emphasis" @click="toggleSort('supplier','summary')">ID / Supplier / Batch SAP / Balance (MT) / Trace <VIcon size="14">{{ sortIcon('supplier','summary') }}</VIcon></th>
              </tr>
            </thead>
            <tbody v-if="paginatedSummary.length === 0">
                <tr><td colspan="10" class="text-center pa-8 text-disabled text-body-2">No data.</td></tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(row, i) in paginatedSummary" :key="i">
                  <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                  <td>{{ row.entry_date }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:250px">{{ row.material }}</td>
                  <td class="text-right font-monospace whitespace-nowrap">{{ row.init_balance || '0.000' }}</td>
                  <td class="text-right font-monospace whitespace-nowrap">{{ row.in || '0.000' }}</td>
                  <td>{{ row.sloc || '-' }}</td>
                  <td class="text-right font-monospace whitespace-nowrap">{{ row.out || '0.000' }}</td>
                  <td class="text-right font-monospace font-weight-bold whitespace-nowrap" :class="sc(row)">{{ row.last_balance || '0.000' }}</td>
                  <td class="text-right font-monospace whitespace-nowrap" :class="sc(row)">{{ row.balance_supplier || '0.000' }}</td>
                  <td class="text-caption" style="min-width:400px">
                    <div class="d-flex flex-wrap ga-1" v-if="row.supplier">
                      <VChip size="small" density="comfortable" variant="flat" color="grey-lighten-3" class="text-black" v-for="item in (row.supplier).split('|')" :key="item" v-show="item.trim()">
                        {{ item.trim() }}
                      </VChip>
                    </div>
                    <span v-else>-</span>
                  </td>
                </tr>
              </tbody>
          </VTable>
        </div>
      <div class="d-flex align-center justify-end pa-3 ga-4">
        <div class="d-flex align-center ga-2">
          <span class="text-caption text-medium-emphasis">Rows per page:</span>
          <VSelect v-model="perPageSummary" :items="[5, 10, 15, 20]" density="compact" variant="outlined" style="width:80px" hide-details />
        </div>
        <VPagination v-model="pageSummary" :length="Math.ceil(sortedSummary.length / perPageSummary)" size="small" :total-visible="7" />
      </div>
        </template>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStockStore } from '../stores/stockStore'
import { useToastStore } from '@/stores/toast.js'
import stockApi from '../services/index.js'

const stockStore = useStockStore()
const toastStore = useToastStore()
const reportType = ref('detail')
const loading = ref(false)
const resultVisible = ref(false)
const showRmTable = ref(false)
const materialOptions = ref([])
const slocOptions = ref([])
const stockData = ref([])
const rmData = ref([])
const summaryData = ref([])

const sortKeyDetail = ref('')
const sortDirDetail = ref('asc')
const sortKeyRm = ref('')
const sortDirRm = ref('asc')
const sortKeyRmStorage = ref('')
const sortDirRmStorage = ref('asc')
const sortKeyRmFeed = ref('')
const sortDirRmFeed = ref('asc')
const sortKeySummary = ref('')
const sortDirSummary = ref('asc')

const perPageDetail = ref(10)
const perPageRm = ref(10)
const perPageRmStorage = ref(10)
const perPageRmFeed = ref(10)
const perPageSummary = ref(10)
const pageDetail = ref(1)
const pageRm = ref(1)
const pageRmStorage = ref(1)
const pageRmFeed = ref(1)
const pageSummary = ref(1)

const detailFilters = reactive({
  startDate: '',
  endDate: '',
  stockType: '',
  materialId: '',
  sloc: ''
})
const summaryFilters = reactive({
  startDate: '',
  endDate: '',
  sloc: '',
  slocType: 'SUMMARY_WIP'
})

onMounted(async () => {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  detailFilters.startDate = `${y}-${m}-01`
  detailFilters.endDate = `${y}-${m}-${day}`
  summaryFilters.startDate = `${y}-${m}-01`
  summaryFilters.endDate = `${y}-${m}-${day}`
  try {
    const r = await stockApi.getActiveSlocs()
    slocOptions.value = r.data?.data || []
  } catch {
    slocOptions.value = []
  }
  
  try {
    await stockStore.fetchActiveMaterials({ search: '', type: '' })
    materialOptions.value = stockStore.activeMaterials || []
  } catch {
    materialOptions.value = []
  }
})

const onReportTypeChange = () => {
  showRmTable.value = false
  stockData.value = []
  rmData.value = []
  summaryData.value = []
  pageRmStorage.value = 1
  pageRmFeed.value = 1
}

const onStockTypeChange = async () => {
  detailFilters.materialId = ''
  try {
    await stockStore.fetchActiveMaterials({ search: '', type: detailFilters.stockType })
    materialOptions.value = stockStore.activeMaterials || []
  } catch {
    materialOptions.value = []
  }
}

const onView = async () => {
  if (reportType.value === 'detail') {
    if (!detailFilters.materialId) {
      toastStore.error('Please select a Material')
      return
    }
    if (!detailFilters.sloc) {
      toastStore.error('Please select a Sloc')
      return
    }
  }
  resultVisible.value = true
  loading.value = true
  pageDetail.value = 1
  pageRm.value = 1
  pageRmStorage.value = 1
  pageRmFeed.value = 1
  pageSummary.value = 1
  try {
    if (reportType.value === 'detail') {
      const p = {}
      if (detailFilters.startDate) p.date_from = detailFilters.startDate
      if (detailFilters.endDate) p.date_to = detailFilters.endDate
      if (detailFilters.materialId) p.material_id = detailFilters.materialId
      if (detailFilters.sloc) p.storage_id = detailFilters.sloc
      await stockStore.fetchStock(p)
      stockData.value = stockStore.stockData || []
      const sel = materialOptions.value.find(m => m.id_material === detailFilters.materialId)
      showRmTable.value = sel && sel.material && (sel.material.includes('/RM)') || sel.material.includes('/ RM)'))
      if (showRmTable.value) {
        rmData.value = stockData.value.filter(r => r.material && (r.material.includes('/RM)') || r.material.includes('/ RM)')))
      }
    } else {
      const p = { report_type: 'summary' }
      if (summaryFilters.startDate) p.date_from = summaryFilters.startDate
      if (summaryFilters.endDate) p.date_to = summaryFilters.endDate
      if (summaryFilters.sloc) p.storage_id = summaryFilters.sloc
      if (summaryFilters.slocType) p.mode = summaryFilters.slocType
      await stockStore.fetchStock(p)
      const raw = stockStore.stockData || []
      summaryData.value = raw.map(r => ({
        entry_date: r.entry_date,
        material: r.material,
        sloc: r.tank || r.sloc,
        init_balance: r.init_balance || r.init_qty || r.qty,
        in: r.in_qty || r.in,
        out: r.out_qty || r.out,
        last_balance: r.last_balance || r.current_qty || r.qty,
        balance_supplier: r.balance_supplier || '0.000',
        supplier: r.supplier || r.supplier_details
      }))
    }
  } catch {
    stockData.value = []
    summaryData.value = []
  } finally {
    loading.value = false
  }
}

const formatQty = (q) => {
  if (q === null || q === undefined || q === '') return '0.000'
  if (typeof q === 'string') return q
  return parseFloat(q).toFixed(3)
}

const parseNum = (v) => parseFloat(String(v || 0).replace(/,/g, '')) || 0

const bc = (row) => {
  const bal = parseNum(row.balance || row.current_qty || row.qty)
  const sup = parseNum(row.balance_supplier)
  return Math.abs(bal - sup) < 0.001 ? 'text-success' : 'text-error'
}

const sc = (row) => {
  const bal = parseFloat(row.last_balance || 0)
  const sup = parseFloat(row.balance_supplier || 0)
  return Math.abs(bal - sup) < 0.001 ? 'text-success' : 'text-error'
}

const detectColumnType = (key) => {
  const numericKeys = ['in_qty', 'out_qty', 'balance', 'current_qty', 'qty', 'init_balance', 'last_balance', 'balance_supplier', 'in', 'out', 'init_qty']
  return numericKeys.includes(key) ? 'number' : 'string'
}

const toggleSort = (key, prefix) => {
  let sortKey, sortDir
  if (prefix === 'detail') { sortKey = sortKeyDetail; sortDir = sortDirDetail }
  else if (prefix === 'rm_storage') { sortKey = sortKeyRmStorage; sortDir = sortDirRmStorage }
  else if (prefix === 'rm_feed') { sortKey = sortKeyRmFeed; sortDir = sortDirRmFeed }
  else if (prefix === 'rm') { sortKey = sortKeyRm; sortDir = sortDirRm }
  else { sortKey = sortKeySummary; sortDir = sortDirSummary }
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

const sortIcon = (key, prefix) => {
  let sortKey, sortDir
  if (prefix === 'detail') { sortKey = sortKeyDetail; sortDir = sortDirDetail }
  else if (prefix === 'rm_storage') { sortKey = sortKeyRmStorage; sortDir = sortDirRmStorage }
  else if (prefix === 'rm_feed') { sortKey = sortKeyRmFeed; sortDir = sortDirRmFeed }
  else if (prefix === 'rm') { sortKey = sortKeyRm; sortDir = sortDirRm }
  else { sortKey = sortKeySummary; sortDir = sortDirSummary }
  if (sortKey.value !== key) return 'ri-arrow-up-down-line'
  return sortDir.value === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'
}

const getRowValue = (row, key) => {
  if (key === 'tank') return (row.tank || row.sloc || '').toLowerCase()
  if (key === 'balance') return parseFloat(row.balance ?? row.current_qty ?? row.qty ?? 0)
  if (key === 'supplier') return (row.supplier || row.supplier_details || '').toLowerCase()
  return row[key]
}

const sortData = (data, sortKey, sortDir) => {
  if (!sortKey.value) return data
  const dir = sortDir.value === 'asc' ? 1 : -1
  const type = detectColumnType(sortKey.value)
  return [...data].sort((a, b) => {
    let va = getRowValue(a, sortKey.value)
    let vb = getRowValue(b, sortKey.value)
    if (type === 'number') {
      va = parseFloat(String(va || 0).replace(/,/g, '')) || 0
      vb = parseFloat(String(vb || 0).replace(/,/g, '')) || 0
    } else {
      va = String(va).toLowerCase()
      vb = String(vb).toLowerCase()
    }
    return va > vb ? dir : va < vb ? -dir : 0
  })
}

const sortedDetail = computed(() => sortData(stockData.value, sortKeyDetail, sortDirDetail))
const sortedRm = computed(() => sortData(rmData.value, sortKeyRm, sortDirRm))
const sortedSummary = computed(() => sortData(summaryData.value, sortKeySummary, sortDirSummary))

const rmStorageData = computed(() => {
  return stockData.value.filter(r => r.sloc && r.sloc.toUpperCase().includes('STORAGE'))
})
const rmFeedData = computed(() => {
  return stockData.value.filter(r => r.sloc && r.sloc.toUpperCase().includes('FEED'))
})

const sortedRmStorage = computed(() => sortData(rmStorageData.value, sortKeyRmStorage, sortDirRmStorage))
const sortedRmFeed = computed(() => sortData(rmFeedData.value, sortKeyRmFeed, sortDirRmFeed))

const paginatedDetail = computed(() => {
  const start = (pageDetail.value - 1) * perPageDetail.value
  return sortedDetail.value.slice(start, start + perPageDetail.value)
})
const paginatedRm = computed(() => {
  const start = (pageRm.value - 1) * perPageRm.value
  return sortedRm.value.slice(start, start + perPageRm.value)
})
const paginatedRmStorage = computed(() => {
  const start = (pageRmStorage.value - 1) * perPageRmStorage.value
  return sortedRmStorage.value.slice(start, start + perPageRmStorage.value)
})
const paginatedRmFeed = computed(() => {
  const start = (pageRmFeed.value - 1) * perPageRmFeed.value
  return sortedRmFeed.value.slice(start, start + perPageRmFeed.value)
})
const paginatedSummary = computed(() => {
  const start = (pageSummary.value - 1) * perPageSummary.value
  return sortedSummary.value.slice(start, start + perPageSummary.value)
})
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>
