<template>
  <div class="p-6">
    <div class="bg-gray-900 rounded-lg shadow-sm mb-6 p-4">
      <h1 class="text-white text-2xl font-bold text-center tracking-wide">EUDR-TS BACKWARD TRACING</h1>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Plant</label>
          <select v-model="filters.id_plant" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500">
            <option value="">All Plants</option>
          </select>
        </div>
        <div class="flex items-end gap-2">
          <button @click="loadData" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-darken-1">
            <Icon icon="ri:search-line" class="w-4 h-4 inline mr-1" />Search
          </button>
          <button @click="resetFilters" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">Reset</button>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="p-3 border-b border-gray-200 flex items-center justify-between">
        <span class="text-sm font-semibold text-slate-700">Backward Trace List — Shipments</span>
        <span class="text-xs text-gray-500">Total: {{ meta.total }}</span>
      </div>

      <div v-if="loading" class="p-8 text-center text-gray-500">
        <Icon icon="ri:loader-4-line" class="animate-spin text-3xl text-primary" />
        <p class="mt-2 text-sm">Loading data...</p>
      </div>

      <div v-else-if="error" class="p-8 text-center text-error">
        <Icon icon="ri:error-warning-line" class="text-4xl text-danger-soft" />
        <p class="mt-2">{{ error }}</p>
      </div>

      <div v-else-if="list.length === 0" class="p-8 text-center text-gray-400">
        <Icon icon="ri:arrow-left-double-line" class="text-4xl text-gray-300" />
        <p>No backward trace records found.</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-12">No</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-20">Action</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Entry Date</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Trace No</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">SO No</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Batch No</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sloc</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Product Desc</th>
                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty (MT)</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase max-w-xs">Supplier / Batch SAP / Qty (MT)</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Source Trace</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase max-w-xs">Source (PO/SO & RM Trace)</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created At</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(row, i) in list" :key="row.id_shipment_head" class="hover:bg-gray-50 text-sm">
                <td class="px-3 py-3 text-center text-gray-500">{{ (meta.page - 1) * meta.perPage + i + 1 }}</td>
                <td class="px-3 py-3">
                  <div class="flex items-center justify-center gap-1">
                    <button @click="openTraceModal(row)" class="p-1.5 text-xs bg-info text-white rounded hover:bg-info-text flex items-center justify-center transition-colors duration-150" title="View Trace">
                      <Icon icon="ri:network-line" class="w-4 h-4" />
                    </button>
                    <button @click="openShipmentDetailModal(row)" class="p-1.5 text-xs bg-info text-white rounded hover:bg-info-text flex items-center justify-center transition-colors duration-150" title="View Shipment Detail">
                      <Icon icon="ri:ship-line" class="w-4 h-4" />
                    </button>
                    <button @click="openBatchPackagingModal(row)" class="p-1.5 text-xs bg-warning text-white rounded hover:bg-warning-text flex items-center justify-center transition-colors duration-150" title="View Batch Packaging">
                      <Icon icon="ri:archive-line" class="w-4 h-4" />
                    </button>
                  </div>
                </td>
                <td class="px-3 py-3 text-slate-600">{{ row.entry_date }}</td>
                <td class="px-3 py-3 font-mono text-slate-700">{{ row.trace_no }}</td>
                <td class="px-3 py-3 font-mono text-slate-700">{{ row.so_no || '-' }}</td>
                <td class="px-3 py-3 font-mono text-slate-700">{{ row.batch_no || '-' }}</td>
                <td class="px-3 py-3 text-slate-600">{{ row.sloc || '-' }}</td>
                <td class="px-3 py-3 font-medium text-slate-800 max-w-xs truncate" :title="row.material">{{ row.material }}</td>
                <td class="px-3 py-3 text-right font-mono font-semibold text-slate-800">{{ row.qty }}</td>
                <td class="px-3 py-3 max-w-xs truncate text-xs" :title="row.supplier">{{ row.supplier || '-' }}</td>
                <td class="px-3 py-3 text-xs text-slate-600">{{ row.source_trace || '-' }}</td>
                <td class="px-3 py-3 text-xs text-slate-600 max-w-xs truncate" :title="row.source">{{ row.source || row.po_so || '-' }}</td>
                <td class="px-3 py-3 text-xs text-gray-500">{{ row.created_at || '-' }}</td>
                <td class="px-3 py-3 text-slate-600">{{ row.created_by || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
          <span class="text-xs text-gray-500">Page {{ meta.page }} of {{ meta.lastPage }} ({{ meta.total }} records)</span>
          <div class="flex gap-1">
            <button @click="changePage(meta.page - 1)" :disabled="meta.page <= 1" class="px-3 py-1 text-xs border rounded hover:bg-gray-100 disabled:opacity-40">Prev</button>
            <button @click="changePage(meta.page + 1)" :disabled="meta.page >= meta.lastPage" class="px-3 py-1 text-xs border rounded hover:bg-gray-100 disabled:opacity-40">Next</button>
          </div>
        </div>
      </template>
    </div>

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
import { ref, reactive, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useTraceBackwardStore } from '../stores/traceBackwardStore'
import { parseSoNo } from '../utils/parseSoNo'
import TraceDetailModal from '@/modules/shared/components/TraceDetailModal.vue'
import ShipmentDetailModal from '../components/ShipmentDetailModal.vue'
import BatchPackagingModal from '../components/BatchPackagingModal.vue'

const store = useTraceBackwardStore()
const { listMeta: meta, list, detail, shipmentData, batchData, preparationRecords, sapAllocations, loading, loadingDetail, loadingShipment, loadingBatch, error } = storeToRefs(store)

const filters = reactive({ id_plant: '' })
const showModal = ref(false)
const modalTraceNo = ref('')
const showShipmentModal = ref(false)
const showBatchModal = ref(false)
const selectedRow = ref(null)

onMounted(() => loadData())

async function loadData() {
  const params = { page: meta.value.page, per_page: meta.value.perPage }
  if (filters.id_plant) params.id_plant = filters.id_plant
  await store.fetchList(params)
}

async function changePage(p) {
  if (p < 1 || p > meta.value.lastPage) return
  meta.value.page = p
  await loadData()
}

function resetFilters() {
  filters.id_plant = ''
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
