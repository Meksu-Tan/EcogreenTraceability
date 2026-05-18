<template>
  <FeatureTableView title="Stock On-Hand" subtitle="Stock On-Hand (WIP / Warehouse)" :tables="visibleTables">
    <template #filters>
      <div class="grid gap-4 md:grid-cols-6">
        <label class="space-y-1 text-sm font-bold text-slate-700 md:col-span-2">
          <span>On-Hand Report Type</span>
          <select v-model="reportType" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
            <option value="detail">- Detail Per Material -</option>
            <option value="summary">- Summary All Material -</option>
          </select>
        </label>
        <label class="space-y-1 text-sm font-bold text-slate-700">
          <span>Start Date</span>
          <input v-model="startDate" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20" />
        </label>
        <label class="space-y-1 text-sm font-bold text-slate-700">
          <span>End Date</span>
          <input v-model="endDate" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20" />
        </label>
        <label class="space-y-1 text-sm font-bold text-slate-700">
          <span>Type</span>
          <select v-model="stockType" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
            <option value="WIP">WIP</option>
            <option value="WH">WH</option>
          </select>
        </label>
        <label class="space-y-1 text-sm font-bold text-slate-700">
          <span>Sloc</span>
          <select v-model="idSloc" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
            <option value="0">ALL</option>
            <option v-for="sloc in slocs" :key="sloc.id_plant" :value="sloc.id_plant">{{ sloc.description }}</option>
          </select>
        </label>
        <label v-if="reportType === 'detail'" class="space-y-1 text-sm font-bold text-slate-700 md:col-span-3">
          <span>Material</span>
          <select v-model="idMaterial" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
            <option value="">- Select material -</option>
            <option v-for="material in materials" :key="material.id_material" :value="material.id_material">{{ material.material }}</option>
          </select>
        </label>
      </div>
      <button class="mt-4 rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" @click="fetchRows">
        <i class="fas fa-globe mr-2"></i> View
      </button>
    </template>
  </FeatureTableView>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import legacyApi from '@/api/legacy'
import FeatureTableView from '@/views/_shared/FeatureTableView.vue'

const today = new Date().toISOString().slice(0, 10)
const reportType = ref('detail')
const startDate = ref(today)
const endDate = ref(today)
const stockType = ref('WIP')
const idSloc = ref('0')
const idMaterial = ref('')
const materials = ref([])
const slocs = ref([])
const rows = ref([])
const loading = ref(false)

const detailColumns = ['No', 'Stock Date', 'Description', 'In (MT)', 'Sloc', 'Out (MT)', 'Balance Material (MT)', 'Balance Supplier (MT)', 'ID / Supplier / Batch SAP / Balance (MT) / Trace']
const visibleTables = computed(() => reportType.value === 'summary'
  ? [{
      columns: ['No', 'Stock Date', 'Description', 'Init Bal (MT)', 'Total In (MT)', 'Sloc', 'Total Out (MT)', 'Bal Material (MT)', 'Bal Supplier (MT)', 'ID / Supplier / Batch SAP / Balance (MT) / Trace'],
      fields: ['__index', 'entry_date', (row) => row.material || row.description, 'init_balance', 'in', 'sloc', 'out', (row) => row.balance || row.balances, 'balance_supplier', 'supplier'],
      rows: rows.value,
      loading: loading.value,
      emptyText: 'No stock summary data found',
    }]
  : [{
      columns: detailColumns,
      fields: ['__index', 'entry_date', 'description', 'in', 'sloc', 'out', (row) => row.balance || row.balances, 'balance_supplier', 'supplier'],
      rows: rows.value,
      loading: loading.value,
      emptyText: 'No stock detail data found',
    }]
)

async function fetchOptions() {
  const [materialResponse, slocResponse] = await Promise.all([
    legacyApi.stockMaterials({ stockType: stockType.value }),
    legacyApi.stockSloc(),
  ])
  materials.value = materialResponse.data || []
  slocs.value = slocResponse.data || []
  if (!idMaterial.value && materials.value.length > 0) {
    idMaterial.value = materials.value[0].id_material
  }
}

async function fetchRows() {
  if (reportType.value === 'detail' && !idMaterial.value) return
  loading.value = true
  try {
    const params = {
      start_date: startDate.value,
      end_date: endDate.value,
      idMaterial: idMaterial.value,
      idSloc: idSloc.value,
      mode: reportType.value === 'summary' ? `SUMMARY_${stockType.value}` : stockType.value === 'WH' ? 'STORAGE' : 'NORMAL',
    }
    const response = reportType.value === 'summary'
      ? await legacyApi.stockSummary(params)
      : await legacyApi.stockDetail(params)
    rows.value = response.data || []
  } finally {
    loading.value = false
  }
}

watch(stockType, async () => {
  idMaterial.value = ''
  await fetchOptions()
})

onMounted(async () => {
  await fetchOptions()
  await fetchRows()
})
</script>
