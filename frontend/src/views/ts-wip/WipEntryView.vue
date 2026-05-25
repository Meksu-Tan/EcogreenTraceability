<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-5">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">WIP Transaction</h1>
          <div class="mt-1 text-sm text-gray-500">
            Lokasi:
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
              {{ plantSelectionStore.selectedPlantName || 'All Plants' }}
            </span>
          </div>
        </div>
        <PlantSelector @change="reloadAll" />
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <select v-model="selectedSection" class="px-3 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg">
          <option value="allSection">- All Section -</option>
          <option v-for="section in wipSections" :key="section.key" :value="section.key">- {{ section.title }} -</option>
        </select>
        <button @click="reloadAll" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
          <i class="fas fa-sync" :class="{ 'animate-spin': loading }"></i> Sync Data
        </button>
        <div class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold">
          QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-24">
      <div class="flex flex-col items-center gap-3">
        <i class="fas fa-circle-notch animate-spin text-3xl text-green-600"></i>
        <span class="text-xs font-medium text-gray-500">Loading WIP entry sections...</span>
      </div>
    </div>

    <div v-else class="space-y-6">
      <section v-for="section in visibleSections" :key="section.key" class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h2 class="text-lg font-bold text-slate-800">{{ section.title }}</h2>
        </div>
        <div class="p-4 space-y-4">
          <template v-for="(step, index) in section.steps" :key="step.key">
            <div v-if="step.type === 'label'" class="flex items-center justify-center gap-4 rounded-md border border-slate-200 bg-slate-700 px-4 py-3 text-white">
              <i class="fas" :class="step.icon"></i>
              <span class="text-sm font-bold tracking-wide">{{ step.label }}</span>
              <i class="fas" :class="step.icon"></i>
            </div>
            <div v-else class="border border-gray-100 rounded-lg overflow-hidden">
              <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 text-center">
                <h3 class="text-lg font-bold text-gray-800">{{ step.title }}</h3>
              </div>
              <div class="p-6">
                <div class="flex flex-wrap justify-end gap-2 mb-4">
                  <button v-if="step.type === 'feed'" @click="openFeedModal(step)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    <i class="fa fa-edit mr-1"></i> {{ step.button }}
                  </button>
                  <button v-else @click="openRundownModal(step)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    <i class="fa fa-edit mr-1"></i> {{ step.button }}
                  </button>
                  <button @click="openBalanceModal(step)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    <i class="fa fa-bars mr-1"></i> View Balance Per Batches
                  </button>
                  <button v-if="step.type === 'feed'" @click="openFeedLogModal(step)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    <i class="fa fa-bars mr-1"></i> View Feed Logs
                  </button>
                  <button v-else @click="openRundownLogModal(step)" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    <i class="fa fa-bars mr-1"></i> View Rundown Logs
                  </button>
                </div>
                <div class="mb-2 text-sm font-medium text-gray-700">LATEST LOG OF {{ step.title }}</div>
                <WipMiniTable :columns="step.type === 'feed' ? feedColumns : rundownColumns" :data="stepRows(step)" />
              </div>
            </div>
            <div v-if="index < section.steps.length - 1 && step.type !== 'label'" class="flex items-center justify-center text-slate-500">
              <i class="fas fa-arrow-down text-xl"></i>
            </div>
          </template>
        </div>
      </section>
    </div>

    <BaseModal v-model="feedModalOpen" :title="feedModalTitle" :loading="storeLoading" submit-label="Save Feed" max-width="640px" @submit="submitFeed">
      <EntryForm mode="feed" :form="feedForm" :tanks="feedTanks" :specific-tanks="feedSpecificTanks" :dcs-status="feedDcsStatus" @tank-change="onFeedTankChange" @fetch-dcs="fetchFeedDcs" />
    </BaseModal>

    <BaseModal v-model="rundownModalOpen" :title="rundownModalTitle" :loading="storeLoading" submit-label="Save Rundown" max-width="640px" @submit="submitRundown">
      <EntryForm mode="rundown" :form="rundownForm" :tanks="rundownTanks" :specific-tanks="rundownSpecificTanks" :dcs-status="rundownDcsStatus" @tank-change="onRundownTankChange" @fetch-dcs="fetchRundownDcs" />
    </BaseModal>

    <BaseModal v-model="balanceModalOpen" :title="balanceTitle" max-width="1000px">
      <WipMiniTable :columns="balanceColumns" :data="balanceData" />
    </BaseModal>
    <BaseModal v-model="feedLogModalOpen" :title="feedLogTitle" max-width="1000px">
      <WipMiniTable :columns="feedColumns" :data="feedLogData" />
    </BaseModal>
    <BaseModal v-model="rundownLogModalOpen" :title="rundownLogTitle" max-width="1000px">
      <WipMiniTable :columns="rundownColumns" :data="rundownLogData" />
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant'
import { useTsWipEntryStore } from '@/modules/ts-wip/stores/wip'
import { useToastStore } from '@/stores/toast'
import wipApi from '@/modules/ts-wip/api/wip'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import WipMiniTable from './WipMiniTable.vue'

const EntryForm = defineComponent({
  props: { mode: String, form: Object, tanks: Array, specificTanks: Array, dcsStatus: String },
  emits: ['tank-change', 'fetch-dcs'],
  setup(props, { emit }) {
    const uniqueTanks = computed(() => {
      if (!Array.isArray(props.tanks)) return []
      const map = new Map()
      for (const t of props.tanks) {
        const name = t.tank || t.description || t.id_tank
        const existing = map.get(name)
        if (!existing || (t.details_count && Number(t.details_count) > (existing.details_count || 0))) {
          map.set(name, t)
        }
      }
      return Array.from(map.values())
    })

    return () => h('div', { class: 'space-y-4' }, [
      h('div', { class: 'rounded-md bg-amber-50 border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-800' }, `${props.mode === 'feed' ? 'Feed' : 'Rundown'} entry MUST be entered on the same day as the related process entry.`),
      h('div', { class: 'grid grid-cols-2 gap-4' }, [
        field('Trace No', h('input', { value: props.form.batchNo, readonly: true, class: inputClass(true) })),
        field('Entry Date', h('input', { type: 'date', value: props.form.entryDate, onInput: e => props.form.entryDate = e.target.value, class: inputClass() })),
      ]),
      field('Sloc', h('select', { value: props.form.tank || '', onChange: e => { props.form.tank = e.target.value ? Number(e.target.value) : null; emit('tank-change') }, class: inputClass() }, [
        h('option', { value: '' }, uniqueTanks.value?.length ? '-- Select Sloc --' : '-- No Sloc found for selected plant --'),
        ...(uniqueTanks.value || []).map(t => h('option', { value: t.id_sloc || t.id_tank }, t.tank || t.description || t.id_tank)),
      ])),
      field('Specific Sloc', h('select', { multiple: true, value: props.form.tankNo || [], onChange: e => props.form.tankNo = Array.from(e.target.selectedOptions).map(o => Number(o.value)), class: inputClass() }, (props.specificTanks?.length ? props.specificTanks : [{ id_sloc_tail: '', tankNo: props.form.tank ? '-- No Specific Sloc found --' : '-- Select Sloc first --' }]).map(t => h('option', { value: t.id_sloc_tail || t.id_tank_tail || '', disabled: !t.id_sloc_tail && !t.id_tank_tail }, t.tankNo)))),
      h('div', { class: 'grid grid-cols-[1fr_auto] gap-3 items-end' }, [
        field(`Current ${props.mode === 'feed' ? 'Feed' : 'Rundown'} (MT)`, h('input', { type: 'number', step: '0.001', value: props.form.currQtf, onInput: e => props.form.currQtf = e.target.value, class: inputClass() })),
        h('button', { type: 'button', onClick: () => emit('fetch-dcs'), class: 'px-4 py-2 text-xs font-bold rounded-md bg-blue-600 text-white hover:bg-blue-700' }, 'Fetch DCS Data'),
      ]),
      h('div', { class: 'text-xs font-semibold text-gray-500' }, props.dcsStatus || 'DCS data has not been fetched.'),
    ])
  },
})

function field(label, child) {
  return h('label', { class: 'block' }, [h('span', { class: 'block text-xs font-bold text-gray-600 mb-1' }, label), child])
}
function inputClass(readonly = false) {
  return `w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-1 focus:ring-green-500 focus:border-green-500 ${readonly ? 'bg-gray-50 text-gray-500' : ''}`
}

const wipSections = [
  section('section101', 'Section 101/102', [
    label('START OF SECTION 101/102'), feed('CPKO FEEDS (101 FT0113)', '001', '101_FT0113'), label('PROCESS OF SECTION 101/102'),
    rundown('DA-OIL RUNDOWNS (102 FT0109)', '011', '102_FT0109'), rundown('PKFAD RUNDOWNS (102 FT0129)', '021', '102_FT0129'), label('END OF SECTION 101/102'),
  ]),
  section('section103', 'Section 103', [
    label('START OF SECTION 103'), feed('DA-OIL FEEDS (103 FT0101)', '002', '103_FT0101'), label('PROCESS OF SECTION 103'),
    rundown('CRUDE-ME RUNDOWNS (103 FT0329)', '012', '103_FT0329'), rundown('TREATED-GLY RUNDOWNS (103 FT0266)', '022', '103_FT0266'), label('END OF SECTION 103'),
  ]),
  section('section104', 'Section 104', [
    label('START OF SECTION 104'), feed('CRUDE-ME FEEDS (104 F0110)', '003', '104_F0110'), label('PROCESS OF SECTION 104'),
    rundown('BDME RUNDOWNS', '023'), rundown('UME RUNDOWNS (104 F0110)', '033', '104_F0110'), rundown('ME28 RUNDOWNS (104 F0332)', '043', '104_F0332'),
    rundown('ECONOATE 665 RUNDOWNS', '053'), rundown('ME80 RUNDOWNS', '063'), label('END OF SECTION 104'),
  ]),
  section('section105', 'Section 105', [
    label('START OF SECTION 105'), feed('ME80 FEEDS', '006-02'), label('PROCESS OF SECTION 105'),
    rundown('CFA80 RUNDOWNS', '026'), label('END OF SECTION 105'),
  ]),
  section('section106', 'Section 106/114', [
    label('START OF SECTION 106/114'), rundown('FA12/99 RUNDOWNS', '078'), rundown('FA14/99 RUNDOWNS', '088'), label('END OF SECTION 106/114'),
  ]),
  section('section110', 'Section 110', [
    label('START OF SECTION 110'), feed('TREATED-GLY FEEDS (110 F0107)', '004', '110_F0107'), label('PROCESS OF SECTION 110'),
    rundown('CRUDE-GLY RUNDOWNS (110 F0108)', '014', '110_F0108'), label('END OF SECTION 110'),
  ]),
  section('section111', 'Section 111/116', [
    label('START OF SECTION 111/116'), feed('CRUDE-GLY FEEDS (111 F0118 + 116 FC01)', '007', '111_F0118_116_FC01'), label('PROCESS OF SECTION 111/116'),
    rundown('GLYCERINE RUNDOWNS', '017'), label('END OF SECTION 111/116'),
  ]),
  section('section112', 'Section 112/114', [
    label('START OF SECTION 112/114'), feed('MODE FA24 FEEDS (112 F0109)', '009-01', '112_F0109'), label('PROCESS OF SECTION 112/114'),
    feed('MODE FA14LRR FEEDS (112 F0109)', '009-02', '112_F0109'), feed('MODE FA18LRR FEEDS (112 F0109)', '009-03', '112_F0109'), feed('MODE ECOROL WAX FEEDS (112 F0109)', '009-04', '112_F0109'),
    rundown('CFA28 RUNDOWNS (112 F0139)', '069', '112_F0139'), rundown('FA12/99 RUNDOWNS (112 F0235)', '039', '112_F0235'), rundown('FA14LRR RUNDOWNS (112 F0224)', '079', '112_F0224'),
    rundown('FA14/99 RUNDOWNS (112 F0224)', '059', '112_F0224'), rundown('FA18/99 RUNDOWNS (112 F0235)', '029', '112_F0235'), rundown('FA18LRR RUNDOWNS (112 F0235)', '049', '112_F0235'),
    rundown('ECOROL WAX RUNDOWNS (112 F0224)', '019', '112_F0224'), label('END OF SECTION 112/114'),
  ]),
  section('section302', 'Section 302', [
    label('START OF SECTION 302'), rundown('WME RUNDOWNS', '015'), label('PROCESS OF SECTION 302'), rundown('ME28-302 RUNDOWNS (302V04)', '025'), label('END OF SECTION 302'),
  ]),
]

function section(key, title, steps) { return { key, title, steps } }
function label(label) { return { type: 'label', key: label, label, icon: label.startsWith('END') ? 'fa-flag-checkered' : 'fa-arrow-down' } }
function feed(title, id, tag = null) { return { type: 'feed', key: `feed-${id}-${title}`, id, title, button: title.replace(/S$/, ''), tag } }
function rundown(title, id, tag = null) { return { type: 'rundown', key: `rundown-${id}-${title}`, id, title, button: title.replace(/S$/, ''), tag } }

const plantSelectionStore = usePlantSelectionStore()
const plantStore = useSetupPlantStore()
const store = useTsWipEntryStore()
const toastStore = useToastStore()
const { feedLogs, rundownLogs } = storeToRefs(store)
const loading = ref(false)
const storeLoading = ref(false)
const selectedSection = ref('allSection')

const visibleSections = computed(() => selectedSection.value === 'allSection' ? wipSections : wipSections.filter(s => s.key === selectedSection.value))
const runnableSteps = computed(() => wipSections.flatMap(s => s.steps).filter(s => s.type === 'feed' || s.type === 'rundown'))

const feedColumns = [
  { key: 'trace_nos_display', label: 'Trace No (From >>> To)' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'out_qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Supplier / Batch SAP / Qty' },
]
const rundownColumns = [
  { key: 'rundown_trace_no', label: 'WIP Trace No' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'in_qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Feed Trace No / Supplier / Batch SAP / In Qty' },
]
const balanceColumns = [
  { key: 'trace_no', label: 'Trace No' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Supplier / Batch' },
]

function stepRows(step) { return step.type === 'feed' ? feedLogs.value[step.id] || [] : normalizeRundownRows(rundownLogs.value[step.id] || []) }
function normalizeRundownRows(rows) { return rows.map(row => ({ ...row, rundown_trace_no: row.rundown_trace_no || row.to_trace_no })) }

const feedModalOpen = ref(false)
const feedModalTitle = ref('')
const feedTanks = ref([])
const feedSpecificTanks = ref([])
const feedDcsStatus = ref('')
const feedForm = reactive(blankForm())
let activeFeedStep = null

const rundownModalOpen = ref(false)
const rundownModalTitle = ref('')
const rundownTanks = ref([])
const rundownSpecificTanks = ref([])
const rundownDcsStatus = ref('')
const rundownForm = reactive(blankForm())
let activeRundownStep = null

function blankForm() { return { id: '', feature: '', batchNo: '', lastQtf: 0, currQtf: 0, entryDate: today(), tank: null, tankNo: [] } }
function today() { return new Date().toISOString().slice(0, 10) }
function resetForm(target, step) { Object.assign(target, blankForm(), { id: step.id, feature: step.title }) }

async function openFeedModal(step) {
  activeFeedStep = step
  resetForm(feedForm, step)
  feedModalTitle.value = step.title
  feedDcsStatus.value = 'Loading Feed Sloc...'
  feedForm.batchNo = 'Generating...'
  feedModalOpen.value = true
  feedTanks.value = await fetchTanks('feed', step.id)
  autoSelectTank(feedForm, feedTanks.value, 'feed')
  await onFeedTankChange()
  feedDcsStatus.value = `${feedTanks.value.length} Feed Sloc found for plant ${resolvePlantCode()}. ${step.tag ? `DCS tag available: ${step.tag}` : 'No DCS tag configured for this entry.'}`
  feedForm.batchNo = normalizeTraceNo(await store.generateNewFeedNumber(step.id)) || buildTraceNo('3', step.id)
  const last = await store.fetchFeedLastBatch(step.id)
  applyLast(feedForm, last)
}

async function openRundownModal(step) {
  activeRundownStep = step
  resetForm(rundownForm, step)
  rundownModalTitle.value = step.title
  rundownDcsStatus.value = 'Loading WIP Sloc...'
  rundownForm.batchNo = 'Generating...'
  rundownModalOpen.value = true
  rundownTanks.value = await fetchTanks('rundown', step.id)
  autoSelectTank(rundownForm, rundownTanks.value, 'rundown')
  await onRundownTankChange()
  rundownDcsStatus.value = `${rundownTanks.value.length} WIP Sloc found for plant ${resolvePlantCode()}. ${step.tag ? `DCS tag available: ${step.tag}` : 'No DCS tag configured for this entry.'}`
  rundownForm.batchNo = normalizeTraceNo(await store.generateNewRundownNumber(step.id)) || buildTraceNo('2', step.id)
  const last = await store.fetchRundownLastBatch(step.id)
  applyLast(rundownForm, last)
}

function applyLast(form, rows) {
  const row = Array.isArray(rows) ? rows[0] : null
  form.lastQtf = Number.parseFloat(row?.curr_qtf || 0)
}

function normalizeTraceNo(value) {
  const row = Array.isArray(value) ? value[0] : value
  if (row && typeof row === 'object') {
    return row.feed_number || row.rundown_number || row.trace_no || row.to_trace_no || row.data || ''
  }
  return value ? String(value) : ''
}

function buildTraceNo(prefix, id) {
  const date = new Date()
  const yy = String(date.getFullYear()).slice(-2)
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  const section = String(id || '000').replace(/\D/g, '').slice(0, 3).padStart(3, '0')
  const plant = resolvePlantCode().slice(-2).padStart(2, '0')
  return `${prefix}${yy}${mm}${dd}${section}${plant}01`
}

function resolvePlantCode() {
  const id = plantSelectionStore.selectedPlantId
  if (id && plantStore.plants?.length) {
    const plant = plantStore.plants.find(p => p.id_plant === id)
    if (plant && plant.code_3) return String(plant.code_3)
  }
  const name = String(plantSelectionStore.selectedPlantName || '').toUpperCase()
  if (name.includes('/')) {
    return name.split('/').pop().trim()
  }
  const map = { EOMB: '1001', EOB1: '1002', EOB2: '1003', EOB3: '1007' }
  const cleanName = name.replace(/[\s-]/g, '')
  if (map[cleanName]) return map[cleanName]
  return id ? String(id) : '0000'
}

function findAdaptiveTank(tanks, mode) {
  if (!Array.isArray(tanks) || tanks.length === 0) return null
  
  const plantDesc = String(plantSelectionStore.selectedPlantName || '').toUpperCase()
  const keywords = []
  const match = plantDesc.match(/(EOB|EOMB)[-\s]*([1-3])?/i)
  if (match) {
    const type = match[1].toUpperCase()
    const num = match[2] || ''
    keywords.push(`${type} ${num}`.trim())
    keywords.push(`${type}-${num}`.trim())
    keywords.push(`${type}${num}`.trim())
  }

  const targetType = mode === 'feed' ? 'FEED' : 'WIP'
  let bestTank = null
  let bestScore = -1

  for (const t of tanks) {
    const tankDesc = String(t.tank || t.description || '').toUpperCase()
    let score = 0

    // Match plant keywords
    const matchesPlant = keywords.some(kw => tankDesc.includes(kw))
    if (matchesPlant) score += 10

    // Match type keywords
    if (targetType === 'FEED' && tankDesc.includes('FEED')) score += 5
    if (targetType === 'WIP' && (tankDesc.includes('WIP') || tankDesc.includes('RUNDOWN'))) score += 5

    // Prioritize tanks with details count
    if (t.details_count && Number(t.details_count) > 0) {
      score += 5
    }

    if (score > bestScore) {
      bestScore = score
      bestTank = t
    }
  }

  return bestTank || tanks[0]
}

function autoSelectTank(form, tanks, mode) {
  if (form.tank || !Array.isArray(tanks) || tanks.length === 0) return
  const selected = findAdaptiveTank(tanks, mode)
  form.tank = selected ? Number(selected.id_sloc || selected.id_tank) : null
}

async function onFeedTankChange() {
  feedForm.tankNo = []
  feedSpecificTanks.value = feedForm.tank ? await fetchSpecificTanks(feedForm.tank) : []
  if (feedSpecificTanks.value.length) {
    feedForm.tankNo = feedSpecificTanks.value.map(t => Number(t.id_sloc_tail || t.id_tank_tail))
  }
}
async function onRundownTankChange() {
  rundownForm.tankNo = []
  rundownSpecificTanks.value = rundownForm.tank ? await fetchSpecificTanks(rundownForm.tank) : []
  if (rundownSpecificTanks.value.length) {
    rundownForm.tankNo = rundownSpecificTanks.value.map(t => Number(t.id_sloc_tail || t.id_tank_tail))
  }
}

async function fetchTanks(type, id) {
  const params = { id_plant: resolvePlantCode() }
  try {
    const response = type === 'feed'
      ? await wipApi.getActiveTanksFeed(id, params)
      : await wipApi.getActiveTanksRundown(id, params)
    if (Array.isArray(response) && response.length) return response
  } catch (error) {
    console.error('Direct WIP sloc fetch failed:', error)
  }

  const primary = type === 'feed'
    ? await store.fetchActiveTanksFeed(id)
    : await store.fetchActiveTanksRundown(id)
  return primary || []
}

async function fetchSpecificTanks(sloc) {
  const primary = await store.fetchActiveSpecificTanks(sloc)
  if (primary?.length) return primary
  const response = await wipApi.getActiveSpecificTanks(sloc)
  return Array.isArray(response) ? response : []
}

async function fetchFeedDcs() { await fetchDcs(activeFeedStep, feedForm, feedDcsStatus) }
async function fetchRundownDcs() { await fetchDcs(activeRundownStep, rundownForm, rundownDcsStatus) }
async function fetchDcs(step, form, statusRef) {
  if (!step?.tag) { statusRef.value = 'No DCS tag configured for this entry.'; return }
  statusRef.value = 'Fetching DCS data...'
  try {
    const data = await store.fetchQuantifierData(form.entryDate, step.tag)
    const row = Array.isArray(data) ? data[0] : null
    if (row && row.value !== undefined && row.value !== '0' && row.value !== 0) {
      form.currQtf = Number.parseFloat(String(row.value || 0).replace(/,/g, ''))
      statusRef.value = `DCS data at ${row.timestamp}`
    } else {
      statusRef.value = 'please connect db'
      toastStore.error('please connect db')
    }
  } catch (error) {
    console.error('DCS fetch failed:', error)
    statusRef.value = 'please connect db'
    toastStore.error('please connect db')
  }
}

async function submitFeed() {
  const res = await store.saveFeed({ feed_id: feedForm.id, tank: feedForm.tank, tankNo: feedForm.tankNo, curr_feed: feedForm.currQtf, last_feed: feedForm.lastQtf, curr_entryDate: feedForm.entryDate, batch_no: feedForm.batchNo, feature: feedForm.feature })
  if (res?.status === 1) { feedModalOpen.value = false; await reloadAll() }
}
async function submitRundown() {
  const res = await store.saveRundown({ rundown_id: rundownForm.id, tank: rundownForm.tank, tankNo: rundownForm.tankNo, curr_rundown: rundownForm.currQtf, last_rundown: rundownForm.lastQtf, curr_entryDate: rundownForm.entryDate, batch_no: rundownForm.batchNo, feature: rundownForm.feature })
  if (res?.status === 1) { rundownModalOpen.value = false; await reloadAll() }
}

const balanceModalOpen = ref(false)
const balanceData = ref([])
const balanceTitle = ref('Balance Detail')
async function openBalanceModal(step) {
  balanceTitle.value = `Balance Detail - ${step.title}`
  const res = await store.fetchBalance(step.id)
  balanceData.value = res?.data || []
  balanceModalOpen.value = true
}

const feedLogModalOpen = ref(false)
const feedLogTitle = ref('')
const feedLogData = ref([])
function openFeedLogModal(step) {
  feedLogTitle.value = `Feed Log - ${step.title}`
  feedLogData.value = feedLogs.value[step.id] || []
  feedLogModalOpen.value = true
}

const rundownLogModalOpen = ref(false)
const rundownLogTitle = ref('')
const rundownLogData = ref([])
function openRundownLogModal(step) {
  rundownLogTitle.value = `Rundown Log - ${step.title}`
  rundownLogData.value = normalizeRundownRows(rundownLogs.value[step.id] || [])
  rundownLogModalOpen.value = true
}

async function reloadAll() {
  loading.value = true
  try {
    await Promise.all(runnableSteps.value.map(step => step.type === 'feed' ? store.fetchFeed(step.id, 'LOG') : store.fetchRundown(step.id, 'LOG')))
  } finally {
    loading.value = false
  }
}

onMounted(reloadAll)
</script>
