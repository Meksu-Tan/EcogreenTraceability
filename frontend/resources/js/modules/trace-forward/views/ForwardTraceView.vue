<template>
  <div class="p-6">
    <div class="bg-gray-900 rounded-lg shadow-sm mb-6 p-4">
      <h1 class="text-white text-2xl font-bold text-center tracking-wide">EUDR-TS FORWARD TRACING</h1>
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
        <span class="text-sm font-semibold text-slate-700">Forward Trace List</span>
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
        <Icon icon="ri:arrow-right-double-line" class="text-4xl text-gray-300" />
        <p>No forward trace records found.</p>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-12">No</th>
                <th class="px-3 py-3 text-center text-xs font-semibold text-gray-500 uppercase w-20">Action</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">RM Batch No</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Entry Date</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Matl Doc</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Material</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tank</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">TF No</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Batch SAP</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PO/SO</th>
                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Init (MT)</th>
                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase">On-Hand (MT)</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase max-w-xs">Supplier / Batch SAP / Init Qty (MT)</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created At</th>
                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Created By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(row, i) in list" :key="row.id_balance_head" class="hover:bg-gray-50 text-sm">
                <td class="px-3 py-3 text-center text-gray-500">{{ (meta.page - 1) * meta.perPage + i + 1 }}</td>
                <td class="px-3 py-3 text-center">
                  <button @click="openTraceModal(row)" class="px-2 py-1 text-xs bg-primary text-white rounded hover:bg-primary-darken-1 flex items-center gap-1 mx-auto">
                    <Icon icon="ri:eye-line" class="w-3 h-3" /> View
                  </button>
                </td>
                <td class="px-3 py-3 font-mono text-slate-700">{{ row.trace_no }}</td>
                <td class="px-3 py-3 text-slate-600">{{ row.entry_date }}</td>
                <td class="px-3 py-3 font-mono text-slate-700">{{ row.material_document || '-' }}</td>
                <td class="px-3 py-3 font-medium text-slate-800 max-w-xs truncate" :title="row.material">{{ row.material }}</td>
                <td class="px-3 py-3 text-slate-700">{{ row.tank || row.tank_type || '-' }}</td>
                <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ row.tf_number || '-' }}</td>
                <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ row.batch_sap || '-' }}</td>
                <td class="px-3 py-3">
                  <span :class="row.traced === 'TRACED' ? 'bg-success-soft text-success-text' : 'bg-slate-100 text-slate-600'"
                        class="px-2 py-0.5 text-xs rounded-full font-semibold">
                    {{ row.traced || 'N/A' }}
                  </span>
                </td>
                <td class="px-3 py-3 font-mono text-xs text-slate-600">{{ row.po_so || '-' }}</td>
                <td class="px-3 py-3 text-right font-mono text-slate-700">{{ row.init_qty }}</td>
                <td class="px-3 py-3 text-right font-mono font-semibold text-slate-800">{{ row.qty }}</td>
                <td class="px-3 py-3 max-w-xs truncate text-xs" :title="row.supplier">{{ row.supplier || '-' }}</td>
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
      mode="forward"
      :trace-no="modalTraceNo"
      :items="allTraceRows"
      :loading="loadingDetail"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useTraceForwardStore } from '../stores/traceForwardStore'
import TraceDetailModal from '@/modules/shared/components/TraceDetailModal.vue'

const store = useTraceForwardStore()
const { listMeta: meta, list, detail, loading, loadingDetail, error } = storeToRefs(store)

const filters = reactive({ id_plant: '' })
const showModal = ref(false)
const modalTraceNo = ref('')

const allTraceRows = computed(() => [...(detail.value.initial || []), ...(detail.value.chain || [])])

onMounted(() => loadData())

async function loadData() {
  const params = { page: meta.value.page, per_page: meta.value.perPage }
  if (filters.id_plant) params.id_plant = filters.id_plant
  await store.fetchList(params)
}

async function changePage(p) {
  if (p < 1 || p > meta.value.lastPage) return
  store.setPage(p)
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
  await store.fetchDetail({
    id_header: row.id_balance_head,
    trace_no: row.trace_no,
    id_material: row.id_material,
  })
}
</script>
