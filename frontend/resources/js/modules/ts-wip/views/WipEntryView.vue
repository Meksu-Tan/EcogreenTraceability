<template>
  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-4">
      <div class="d-flex align-center ga-5">
        <div>
          <h1 class="text-h5 font-weight-bold">WIP Transaction</h1>
          <div class="d-flex align-center gap-2 mt-1 flex-wrap">
            <span class="text-caption text-medium-emphasis">Location:</span>
            <VChip size="x-small" color="primary" variant="tonal" prepend-icon="ri-factory-line">
              {{ plantSelectionStore.selectedPlantName || 'All Plants' }}
            </VChip>
          </div>
        </div>
        <PlantSelector @change="reloadAll" />
      </div>
      <div class="d-flex align-center gap-3 flex-wrap">
        <VSelect
          v-model="selectedSection"
          :items="sectionSelectItems"
          density="compact"
          hide-details
          variant="outlined"
          rounded="md"
          color="primary"
          style="min-width: 200px;"
        />
        <VAlert
          type="error"
          variant="tonal"
          density="compact"
          class="text-caption font-weight-bold"
        >
          QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
        </VAlert>
      </div>
    </div>

    <!-- Sections -->
    <div class="d-flex flex-column ga-6">
      <VCard
        v-for="section in visibleSections"
        :key="section.key"
        rounded="lg"
        elevation="1"
      >
        <VCardTitle class="pa-5 pb-3 bg-neutral-50">
          <div class="d-flex align-center ga-3">
            <h2 class="text-h6 font-weight-bold">{{ section.title }}</h2>

          </div>
        </VCardTitle>
        <VCardText class="pa-4">
          <div class="d-flex flex-column ga-4">
            <template v-for="(step, index) in section.steps" :key="step.key">
              <VSheet
                v-if="step.type === 'label'"
                color="neutral-900"
                rounded="md"
                class="d-flex align-center justify-center ga-4 px-4 py-3"
              >
                <VIcon :icon="step.icon" size="16" color="on-primary" />
                <span class="text-body-2 font-weight-bold text-white">{{ step.label }}</span>
                <VIcon :icon="step.icon" size="16" color="on-primary" />
              </VSheet>

              <div v-else-if="step.type === 'mode_switch'" class="d-flex justify-start px-2 py-2">
                <div class="d-flex align-center ga-3 bg-surface px-4 py-2 rounded-lg border">
                  <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">{{ step.title || 'Mode' }}</span>
                  <VSelect
                    :model-value="modes[step.modeGroup]"
                    :items="step.config.options || []"
                    item-title="label"
                    item-value="value"
                    density="compact"
                    hide-details
                    variant="outlined"
                    rounded="md"
                    color="primary"
                    style="min-width: 240px;"
                    @update:model-value="onModeChange(step.modeGroup, $event)"
                  />
                </div>
              </div>

              <VCard v-else variant="outlined" rounded="md">
                <VCardTitle class="pa-4 pb-3 bg-neutral-50 text-center">
                  <h3 class="text-h6 font-weight-bold">{{ step.title }}</h3>
                </VCardTitle>
                <VCardText class="pa-6">
                  <div class="d-flex flex-wrap justify-end ga-2 mb-4">
                    <VBtn
                      v-if="step.type === 'feed' && plantSelectionStore.selectedPlantId"
                      color="primary"
                      size="small"
                      variant="tonal"
                      prepend-icon="ri-edit-line"
                      @click="openFeedModal(step)"
                    >
                      {{ step.button }}
                    </VBtn>
                    <VBtn
                      v-else-if="step.type !== 'feed' && plantSelectionStore.selectedPlantId"
                      color="primary"
                      size="small"
                      variant="tonal"
                      prepend-icon="ri-edit-line"
                      @click="openRundownModal(step)"
                    >
                      {{ step.button }}
                    </VBtn>
                    <VBtn
                      color="primary"
                      size="small"
                      variant="tonal"
                      prepend-icon="ri-list-check-2"
                      @click="openBalanceModal(step)"
                    >
                      View Balance Per Batches
                    </VBtn>
                    <VBtn
                      v-if="step.type === 'feed'"
                      color="primary"
                      size="small"
                      variant="tonal"
                      prepend-icon="ri-list-check-2"
                      @click="openFeedLogModal(step)"
                    >
                      View Feed Logs
                    </VBtn>
                    <VBtn
                      v-else
                      color="primary"
                      size="small"
                      variant="tonal"
                      prepend-icon="ri-list-check-2"
                      @click="openRundownLogModal(step)"
                    >
                      View Rundown Logs
                    </VBtn>
                  </div>
                  <div class="mb-2 text-body-2 font-weight-medium text-medium-emphasis">LATEST LOG OF {{ step.title }}</div>
                  <WipMiniTable :columns="step.type === 'feed' ? feedColumns : rundownColumns" :data="stepRows(step)" />
                </VCardText>
              </VCard>

              <div
                v-if="index < section.steps.length - 1 && step.type !== 'label' && step.type !== 'mode_switch'"
                class="d-flex align-center justify-center text-medium-emphasis"
              >
                <VIcon icon="ri-arrow-down-line" size="20" />
              </div>
            </template>
          </div>
        </VCardText>
      </VCard>
    </div>

    <BaseModal v-model="feedModalOpen" :title="feedModalTitle" :loading="storeLoading" submit-label="Save Feed" max-width="640px" @submit="submitFeed">
      <EntryForm mode="feed" :form="feedForm" :tanks="feedTanks" :specific-tanks="feedSpecificTanks" :dcs-status="feedDcsStatus" :timer-syncing="autoFetchFeed.syncing.value" :timer-countdown="autoFetchFeed.countdownDisplay.value" :timer-next-sync="autoFetchFeed.nextSyncLabel.value" :timer-last-sync="autoFetchFeed.lastSyncAt.value ? formatLastSync(autoFetchFeed.lastSyncAt.value) : ''" @tank-change="onFeedTankChange" @fetch-dcs="fetchFeedDcs" />
    </BaseModal>

    <BaseModal v-model="rundownModalOpen" :title="rundownModalTitle" :loading="storeLoading" submit-label="Save Rundown" max-width="640px" @submit="submitRundown">
      <EntryForm mode="rundown" :form="rundownForm" :tanks="rundownTanks" :specific-tanks="rundownSpecificTanks" :dcs-status="rundownDcsStatus" :timer-syncing="autoFetchRundown.syncing.value" :timer-countdown="autoFetchRundown.countdownDisplay.value" :timer-next-sync="autoFetchRundown.nextSyncLabel.value" :timer-last-sync="autoFetchRundown.lastSyncAt.value ? formatLastSync(autoFetchRundown.lastSyncAt.value) : ''" @tank-change="onRundownTankChange" @fetch-dcs="fetchRundownDcs" />
    </BaseModal>

    <BaseModal v-model="balanceModalOpen" :title="balanceTitle" max-width="1000px">
      <div class="d-flex flex-column ga-4">
        <div v-if="balanceLoading" class="d-flex justify-center py-6">
          <VProgressCircular indeterminate color="primary" />
        </div>
        <WipMiniTable v-else :columns="balanceColumns" :data="balanceData" />
        <div v-if="balanceTotalFromServer > 0" class="d-flex flex-wrap justify-space-between align-center custom-pagination-footer pa-3 rounded border gap-2">
          <div class="text-caption text-medium-emphasis">
            Showing {{ (currentPageBalance - 1) * itemsPerPageLog + 1 }} - {{ Math.min(currentPageBalance * itemsPerPageLog, balanceTotalFromServer) }} of {{ balanceTotalFromServer }} records
          </div>
          <VPagination
            v-if="balanceTotalPages > 1"
            v-model="currentPageBalance"
            :length="balanceTotalPages"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="goBalancePage"
          />
        </div>
      </div>
    </BaseModal>

    <BaseModal v-model="feedLogModalOpen" :title="feedLogTitle" max-width="1000px">
      <div class="d-flex flex-column ga-4">
        <div v-if="feedLogLoading" class="d-flex justify-center py-6">
          <VProgressCircular indeterminate color="primary" />
        </div>
        <WipMiniTable v-else :columns="feedColumns" :data="feedLogData" />
        <div v-if="feedLogTotalFromServer > 0" class="d-flex flex-wrap justify-space-between align-center custom-pagination-footer pa-3 rounded border gap-2">
          <div class="text-caption text-medium-emphasis">
            Showing {{ (currentPageFeedLog - 1) * itemsPerPageLog + 1 }} - {{ Math.min(currentPageFeedLog * itemsPerPageLog, feedLogTotalFromServer) }} of {{ feedLogTotalFromServer }} records
          </div>
          <VPagination
            v-if="feedLogTotalPages > 1"
            v-model="currentPageFeedLog"
            :length="feedLogTotalPages"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="goFeedLogPage"
          />
        </div>
      </div>
    </BaseModal>

    <BaseModal v-model="rundownLogModalOpen" :title="rundownLogTitle" max-width="1000px">
      <div class="d-flex flex-column ga-4">
        <div v-if="rundownLogLoading" class="d-flex justify-center py-6">
          <VProgressCircular indeterminate color="primary" />
        </div>
        <WipMiniTable v-else :columns="rundownColumns" :data="rundownLogData" />
        <div v-if="rundownLogTotalFromServer > 0" class="d-flex flex-wrap justify-space-between align-center custom-pagination-footer pa-3 rounded border gap-2">
          <div class="text-caption text-medium-emphasis">
            Showing {{ (currentPageRundownLog - 1) * itemsPerPageLog + 1 }} - {{ Math.min(currentPageRundownLog * itemsPerPageLog, rundownLogTotalFromServer) }} of {{ rundownLogTotalFromServer }} records
          </div>
          <VPagination
            v-if="rundownLogTotalPages > 1"
            v-model="currentPageRundownLog"
            :length="rundownLogTotalPages"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="goRundownLogPage"
          />
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref, resolveComponent, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant.js'
import { useTsWipEntryStore } from '@/modules/ts-wip/stores/wip'
import { useToastStore } from '@/stores/toast.js'
import { useAutoFetchQty } from '@/composables/useAutoFetchQty'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'
import WipMiniTable from './WipMiniTable.vue'

const EntryForm = {
  props: { mode: String, form: Object, tanks: Array, specificTanks: Array, dcsStatus: String, timerSyncing: Boolean, timerCountdown: String, timerNextSync: String, timerLastSync: String },
  emits: ['tank-change', 'fetch-dcs'],
  setup(props, { emit }) {
    const VTextField = resolveComponent('VTextField')
    const VSelect = resolveComponent('VSelect')
    const VAlert = resolveComponent('VAlert')
    const VBtn = resolveComponent('VBtn')

    const uniqueTanks = computed(() => {
      if (!Array.isArray(props.tanks)) return []
      const map = new Map()
      for (const t of props.tanks) {
        const name = t.tank || t.description || t.tf_number
        const existing = map.get(name)
        if (!existing || (t.details_count && Number(t.details_count) > (existing.details_count || 0))) {
          map.set(name, t)
        }
      }
      return Array.from(map.values())
    })

    return () => h('div', { class: 'd-flex flex-column ga-4' }, [
      h(VAlert, { type: 'warning', variant: 'tonal', density: 'compact', class: 'text-caption font-weight-semibold' }, () =>
        `${props.mode === 'feed' ? 'Feed' : 'Rundown'} entry MUST be entered on the same day as the related process entry.`
      ),
      h('div', { class: 'd-flex ga-4' }, [
        h('div', { class: 'flex-grow-1' }, [
          h('label', { class: 'd-block text-caption font-weight-bold text-medium-emphasis mb-1' }, 'Trace No'),
          h(VTextField, { modelValue: props.form.batchNo, placeholder: 'AUTO GENERATE', readonly: true, density: 'comfortable', variant: 'outlined', hideDetails: true, loading: props.form.batchNo === 'Generating...' }),
        ]),
        h('div', { class: 'flex-grow-1' }, [
          h('label', { class: 'd-block text-caption font-weight-bold text-medium-emphasis mb-1' }, 'Entry Date'),
          h(VTextField, { modelValue: props.form.entryDate, type: 'date', density: 'comfortable', variant: 'outlined', hideDetails: true, 'onUpdate:modelValue': v => { props.form.entryDate = v } }),
        ]),
      ]),
      h('div', [
        h('label', { class: 'd-block text-caption font-weight-bold text-medium-emphasis mb-1' }, 'Sloc'),
        h(VSelect, {
          modelValue: props.form.tank || null,
          items: uniqueTanks.value.map(t => ({ value: t.id_sloc || t.tf_number, title: t.tank || t.description || t.tf_number })),
          density: 'comfortable', variant: 'outlined', hideDetails: true,
          placeholder: uniqueTanks.value?.length ? '-- Select Sloc --' : '-- No Sloc found for selected plant --',
          loading: !uniqueTanks.value?.length && props.form.batchNo === 'Generating...',
          'onUpdate:modelValue': v => { props.form.tank = v ? Number(v) : null; emit('tank-change') },
        }),
      ]),
      h('div', [
        h('label', { class: 'd-block text-caption font-weight-bold text-medium-emphasis mb-1' }, 'Specific Sloc'),
        h('div', { class: 'rounded border pa-3 bg-neutral-50', style: 'max-height: 144px; overflow-y: auto;' }, [
          !props.form.tank
            ? h('p', { class: 'py-2 text-center text-caption text-disabled' }, 'Select Sloc first')
            : !props.specificTanks?.length
              ? h('p', { class: 'py-2 text-center text-caption text-disabled' }, 'No Specific Sloc found')
              : h('div', { class: 'd-flex flex-wrap ga-2' },
                  props.specificTanks.map(t => {
                    const isChecked = props.form.tankNo?.includes(t.id_sloc_tail || t.id_sloc_tail);
                    return h('label', {
                      class: `d-flex align-center ga-2 px-3 py-1 rounded-pill border text-caption font-weight-medium cursor-pointer transition-all ${
                        isChecked ? 'bg-primary text-white border-primary' : 'bg-white text-medium-emphasis hover:bg-neutral-100'
                      }`,
                      style: 'user-select: none;'
                    }, [
                      h('input', {
                        type: 'checkbox',
                        value: t.id_sloc_tail || t.id_sloc_tail,
                        checked: isChecked,
                        onChange: e => {
                          const val = t.id_sloc_tail || t.id_sloc_tail
                          if (e.target.checked) {
                            if (!props.form.tankNo.includes(val)) {
                              props.form.tankNo.push(val)
                            }
                          } else {
                            props.form.tankNo = props.form.tankNo.filter(v => v !== val)
                          }
                        },
                        style: 'display: none;',
                      }),
                      h('span', t.tankName || t.tankNo || String(t.id_sloc_tail || t.id_sloc_tail || '')),
                    ]);
                  })
                )
        ]),
      ]),
      h('div', { class: 'd-flex ga-3 align-end' }, [
        h('div', { class: 'flex-grow-1' }, [
          h('label', { class: 'd-block text-caption font-weight-bold text-medium-emphasis mb-1' }, `Current ${props.mode === 'feed' ? 'Feed' : 'Rundown'} (MT)`),
          h(VTextField, { modelValue: props.form.currQtf, type: 'number', step: '0.001', density: 'comfortable', variant: 'outlined', hideDetails: true, 'onUpdate:modelValue': v => { props.form.currQtf = v } }),
        ]),
        h('div', { class: 'd-flex align-center gap-2' }, [
          h(VBtn, { color: 'primary', variant: 'outlined', onClick: () => emit('fetch-dcs'), loading: props.timerSyncing }, () => 'Fetch DCS Data'),
          h('div', { class: 'd-flex align-center gap-1 text-caption text-medium-emphasis' }, [
            h('span', { innerHTML: '&#xf017;' }, ''),
            props.timerLastSync ? h('span', `Last: ${props.timerLastSync}`) : null,
            h('span', { class: 'font-weight-bold text-primary', style: 'margin: 0 4px' }, '|'),
            h('span', `Next: ${props.timerCountdown} (${props.timerNextSync})`),
          ]),
        ]),
      ]),
      h('div', { class: 'text-caption font-weight-semibold text-medium-emphasis' }, props.dcsStatus || 'DCS data has not been fetched.'),
    ])
  },
}


const plantSelectionStore = usePlantSelectionStore()
const plantStore = useSetupPlantStore()
const store = useTsWipEntryStore()
const toastStore = useToastStore()
const { feedLatest, rundownLatest, feedLogs, rundownLogs, balanceData, balanceMeta, feedLogMeta, rundownLogMeta, wipTree } = storeToRefs(store)
const loading = ref(false)
const storeLoading = ref(false)
const selectedSection = ref('allSection')
const syncing = ref(false)
const modes = reactive({})

const wipSections = computed(() => (wipTree.value.sections || []).map(section => ({
  ...section,
  key: section.key || `section${section.code}`,
  title: section.title || section.name,
  steps: (section.steps || []).filter(isStepVisible),
})))

const sectionSelectItems = computed(() => [
  { value: 'allSection', title: '- All Section -' },
  ...wipSections.value.map(s => ({ value: s.key, title: `- ${s.title} -` })),
])

const visibleSections = computed(() => selectedSection.value === 'allSection' ? wipSections.value : wipSections.value.filter(s => s.key === selectedSection.value))
const runnableSteps = computed(() => wipSections.value.flatMap(s => s.steps).filter(s => s.type === 'feed' || s.type === 'rundown'))

function isStepVisible(step) {
  const conditions = step.conditions || {}
  return Object.entries(conditions).every(([group, expected]) => {
    const actual = modes[group]
    return Array.isArray(expected) ? expected.map(String).includes(String(actual)) : String(actual) === String(expected)
  })
}

function initializeModes(configs = {}) {
  for (const [group, config] of Object.entries(configs)) {
    if (modes[group] === undefined) {
      modes[group] = String(config.default ?? config.options?.[0]?.value ?? '')
    }
  }
}

watch(() => wipTree.value, (tree) => {
  if (tree?.modeConfigs) {
    initializeModes(tree.modeConfigs)
  }
}, { immediate: true })

function onSectionModeChange(sectionKey, value) {
  modes[sectionKey] = String(value)
  reloadAll()
}

function onModeChange(group, value) {
  modes[group] = String(value)
  reloadAll()
}
function formatLastSync(isoString) {
  if (!isoString) return '-'
  const d = new Date(isoString)
  return d.toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

async function doFetchFeedDcs() {
  if (!activeFeedStep?.tag) return
  await fetchFeedDcs()
}

async function doFetchRundownDcs() {
  if (!activeRundownStep?.tag) return
  await fetchRundownDcs()
}

const autoFetchFeed = useAutoFetchQty(doFetchFeedDcs)
const autoFetchRundown = useAutoFetchQty(doFetchRundownDcs)
const feedColumns = [
  { key: 'plant_name', label: 'Plant' },
  { key: 'to_trace_no', label: 'Feed Trace No' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'out_qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Trace No / Supplier / Batch SAP / Out Qty' },
  { key: 'created_at', label: 'Created At' }, { key: 'created_by', label: 'Created By' },
]
const rundownColumns = [
  { key: 'plant_name', label: 'Plant' },
  { key: 'rundown_trace_no', label: 'WIP Trace No' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'in_qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Feed Trace No / Supplier / Batch SAP / In Qty' },
  { key: 'created_at', label: 'Created At' }, { key: 'created_by', label: 'Created By' },
]
const balanceColumns = [
  { key: 'plant_name', label: 'Plant' },
  { key: 'trace_no', label: 'Trace No' }, { key: 'entry_date', label: 'Entry Date' }, { key: 'material_document', label: 'Matl Doc' },
  { key: 'sloc', label: 'Sloc' }, { key: 'qty', label: 'Total Material (MT)' }, { key: 'balance_supplier', label: 'Total Supplier (MT)' }, { key: 'supplier', label: 'Supplier / Batch' },
  { key: 'created_at', label: 'Created At' }, { key: 'created_by', label: 'Created By' },
]

const itemsPerPageLog = 5
const currentPageFeedLog = ref(1)
const currentPageRundownLog = ref(1)
const currentPageBalance = ref(1)

// Server-side pagination loading states
const balanceLoading = ref(false)
const feedLogLoading = ref(false)
const rundownLogLoading = ref(false)

// Server-side totals from store meta
const balanceTotalFromServer = computed(() => balanceMeta.value?.total ?? 0)
const balanceTotalPages = computed(() => Math.max(1, Math.ceil(balanceTotalFromServer.value / itemsPerPageLog)))

const feedLogTotalFromServer = computed(() => {
  const meta = feedLogMeta.value?.[activeFeedLogStepId.value]
  return meta?.total ?? 0
})
const feedLogTotalPages = computed(() => Math.max(1, Math.ceil(feedLogTotalFromServer.value / itemsPerPageLog)))

const rundownLogTotalFromServer = computed(() => {
  const meta = rundownLogMeta.value?.[activeRundownLogStepId.value]
  return meta?.total ?? 0
})
const rundownLogTotalPages = computed(() => Math.max(1, Math.ceil(rundownLogTotalFromServer.value / itemsPerPageLog)))

// Track which step is currently open in each log modal
const activeFeedLogStepId = ref(null)
const activeRundownLogStepId = ref(null)
const activeBalanceStepId = ref(null)

// Server-side page navigation helpers
async function goBalancePage(page) {
  if (!activeBalanceStepId.value) return
  currentPageBalance.value = page
  balanceLoading.value = true
  try {
    await store.fetchBalance(activeBalanceStepId.value, {}, page)
  } finally {
    balanceLoading.value = false
  }
}

async function goFeedLogPage(page) {
  if (!activeFeedLogStepId.value) return
  currentPageFeedLog.value = page
  feedLogLoading.value = true
  try {
    const res = await store.fetchFeed(activeFeedLogStepId.value, 'LOG', page)
    feedLogData.value = res.data || []
  } finally {
    feedLogLoading.value = false
  }
}

async function goRundownLogPage(page) {
  if (!activeRundownLogStepId.value) return
  currentPageRundownLog.value = page
  rundownLogLoading.value = true
  try {
    const res = await store.fetchRundown(activeRundownLogStepId.value, 'LOG', page)
    rundownLogData.value = normalizeRundownRows(res.data || [])
  } finally {
    rundownLogLoading.value = false
  }
}


function stepRows(step) {
  const rows = step.type === 'feed' ? feedLatest.value[step.id] || [] : normalizeRundownRows(rundownLatest.value[step.id] || [])
  return rows.slice(0, 1)
}
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

function blankForm() { return { id: '', feature: '', batchNo: '', lastQtf: 0, currQtf: 0, entryDate: today(), tank: null, tankNo: [], wipMode: 1 } }
function today() { return new Date().toISOString().slice(0, 10) }
function resolveWipMode(step) {
  for (const group of Object.keys(step.conditions || {})) {
    const value = modes[group]
    if (value === '1' || value === '2') return Number(value)
  }
  return 1
}
function resetForm(target, step) { Object.assign(target, blankForm(), { id: step.id, feature: step.title, wipMode: resolveWipMode(step) }) }

async function openFeedModal(step) {
  activeFeedStep = step
  resetForm(feedForm, step)
  feedModalTitle.value = step.title
  feedDcsStatus.value = 'Loading Feed Sloc...'
  feedForm.batchNo = 'Generating...'
  feedModalOpen.value = true
  feedTanks.value = await fetchTanks('feed', step.id)
  autoSelectTank(feedForm, feedTanks.value, 'feed', step)
  await onFeedTankChange()
  feedDcsStatus.value = `${feedTanks.value.length} Feed Sloc found for plant ${resolvePlantCode()}. ${step.tag ? `DCS tag available: ${step.tag}` : 'No DCS tag configured for this entry.'}`
  const last = await store.fetchFeedLastBatch(step.id)
  applyLast(feedForm, last)
  if (step.tag) await fetchFeedDcs()
  autoFetchFeed.startCountdown()
  autoFetchFeed.startAutoSync()
}

async function openRundownModal(step) {
  activeRundownStep = step
  resetForm(rundownForm, step)
  rundownModalTitle.value = step.title
  rundownDcsStatus.value = 'Loading WIP Sloc...'
  rundownForm.batchNo = 'Generating...'
  rundownModalOpen.value = true
  rundownTanks.value = await fetchTanks('rundown', step.id)
  autoSelectTank(rundownForm, rundownTanks.value, 'rundown', step)
  await onRundownTankChange()
  rundownDcsStatus.value = `${rundownTanks.value.length} WIP Sloc found for plant ${resolvePlantCode()}. ${step.tag ? `DCS tag available: ${step.tag}` : 'No DCS tag configured for this entry.'}`
  const last = await store.fetchRundownLastBatch(step.id)
  applyLast(rundownForm, last)
  if (step.tag) await fetchRundownDcs()
  autoFetchRundown.startCountdown()
  autoFetchRundown.startAutoSync()
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

function buildTraceNo(prefix, id, tank = null) {
  const date = new Date()
  const yy = String(date.getFullYear()).slice(-2)
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  const section = String(id || '000').replace(/\D/g, '').slice(0, 3).padStart(3, '0')
  const plant = resolvePlantCode(tank).slice(-2).padStart(2, '0')
  return `${prefix}${yy}${mm}${dd}${section}${plant}01`
}

function resolvePlantCode(tank = null) {
  let id = plantSelectionStore.selectedPlantId
  let name = String(plantSelectionStore.selectedPlantName || '').toUpperCase()

  if (tank && tank.id_plant) {
    id = tank.id_plant
    const p = plantStore.plants?.find(x => x.id_plant == id || x.code_3 == id)
    if (p) {
      if (p.code_3) return String(p.code_3)
      if (p.description) name = p.description.toUpperCase()
    } else {
      return String(id)
    }
  }

  if (id && plantStore.plants?.length) {
    const plant = plantStore.plants.find(p => p.id_plant === id)
    if (plant && plant.code_3) return String(plant.code_3)
  }
  if (name.includes('/')) {
    return name.split('/').pop().trim()
  }
  // Build map dynamically from plant store
  const plantMap = {}
  if (plantStore?.plants?.length) {
    for (const p of plantStore.plants) {
      if (p.code_3 && p.code) plantMap[p.code_3] = p.code
    }
  } else {
    plantMap['EOMB'] = '1001'; plantMap['EOB1'] = '1002'
    plantMap['EOB2'] = '1003'; plantMap['EOB5'] = '1005'
    plantMap['EOB3'] = '1007'
  }
  const cleanName = name.replace(/[\s-]/g, '')
  if (plantMap[cleanName]) return plantMap[cleanName]
  try {
    const decoded = plantStore?.plants?.find(p => cleanName.includes(p.code_3))
    if (decoded?.code) return String(decoded.code)
  } catch {}
  return id ? String(id) : '0000'
}

function findAdaptiveTank(tanks, mode, stepObj) {
  if (!Array.isArray(tanks) || tanks.length === 0) return null

  const plantDesc = String(plantSelectionStore.selectedPlantName || '').toUpperCase()
  const keywords = []
  const match = plantDesc.match(/(EOB|EOMB)[-\s]*([1-9])?/i)
  if (match) {
    const type = match[1].toUpperCase()
    const num = match[2] || ''
    keywords.push(`${type} ${num}`.trim())
    keywords.push(`${type}-${num}`.trim())
    keywords.push(`${type}${num}`.trim())
  }

  // Tank type keywords from step config, fallback to heuristics
  const tankTypeKey = stepObj?.tank_type || (mode === 'feed' ? 'FEED' : 'WIP')
  const tankKeywords = []
  const stepType = stepObj?.step_type || mode
  if (stepType === 'feed') tankKeywords.push('FEED')
  else tankKeywords.push('WIP', 'RUNDOWN')

  let bestTank = null
  let bestScore = -1

  for (const t of tanks) {
    const tankDesc = String(t.tank || t.description || '').toUpperCase()
    let score = 0

    const matchesPlant = keywords.some(kw => tankDesc.includes(kw))
    if (matchesPlant) score += 10

    for (const kw of tankKeywords) {
      if (tankDesc.includes(kw)) { score += 5; break }
    }

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

function autoSelectTank(form, tanks, mode, stepObj) {
  if (form.tank || !Array.isArray(tanks) || tanks.length === 0) return
  const selected = findAdaptiveTank(tanks, mode, stepObj)
  form.tank = selected ? Number(selected.id_sloc || selected.tf_number) : null
}

async function onFeedTankChange() {
  feedForm.tankNo = []
  feedSpecificTanks.value = feedForm.tank ? await fetchSpecificTanks(feedForm.tank) : []
  if (feedModalOpen.value && activeFeedStep) {
    const selectedTank = feedTanks.value.find(t => (t.id_sloc || t.tf_number) == feedForm.tank)
    feedForm.batchNo = 'Generating...'
    feedForm.batchNo = normalizeTraceNo(await store.generateNewFeedNumber(activeFeedStep.id, selectedTank?.id_plant)) || buildTraceNo('3', activeFeedStep.id, selectedTank)
  }
}
async function onRundownTankChange() {
  rundownForm.tankNo = []
  rundownSpecificTanks.value = rundownForm.tank ? await fetchSpecificTanks(rundownForm.tank) : []
  if (rundownModalOpen.value && activeRundownStep) {
    const selectedTank = rundownTanks.value.find(t => (t.id_sloc || t.tf_number) == rundownForm.tank)
    rundownForm.batchNo = 'Generating...'
    rundownForm.batchNo = normalizeTraceNo(await store.generateNewRundownNumber(activeRundownStep.id, selectedTank?.id_plant)) || buildTraceNo('2', activeRundownStep.id, selectedTank)
  }
}

async function fetchTanks(type, id) {
  const params = { id_plant: resolvePlantCode() }
  try {
    const response = type === 'feed'
      ? await store.fetchActiveTanksFeed(id, params)
      : await store.fetchActiveTanksRundown(id, params)
    if (Array.isArray(response) && response.length) return response
  } catch (error) {
    toastStore.error('Failed to fetch WIP sloc:', error)
  }
  return []
}

async function fetchSpecificTanks(sloc) {
  const data = await store.fetchActiveSpecificTanks(sloc)
  return Array.isArray(data) ? data : []
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
    toastStore.error('DCS fetch failed:', error)
    statusRef.value = 'please connect db'
    toastStore.error('please connect db')
  }
}

async function submitFeed() {
  try {
    const res = await store.saveFeed({ feed_id: feedForm.id, tank: feedForm.tank, tankNo: feedForm.tankNo, curr_feed: feedForm.currQtf, last_feed: feedForm.lastQtf, curr_entryDate: feedForm.entryDate, batch_no: feedForm.batchNo, feature: feedForm.feature, wip_mode: feedForm.wipMode })
    if (res?.status === 1) { feedModalOpen.value = false; await reloadAll() }
  } catch (error) {
    console.error('Feed save failed:', error)
  }
}
async function submitRundown() {
  try {
    const res = await store.saveRundown({ rundown_id: rundownForm.id, tank: rundownForm.tank, tankNo: rundownForm.tankNo, curr_rundown: rundownForm.currQtf, last_rundown: rundownForm.lastQtf, curr_entryDate: rundownForm.entryDate, batch_no: rundownForm.batchNo, feature: rundownForm.feature, wip_mode: rundownForm.wipMode })
    if (res?.status === 1) { rundownModalOpen.value = false; await reloadAll() }
  } catch (error) {
    console.error('Rundown save failed:', error)
  }
}

const balanceModalOpen = ref(false)
const balanceTitle = ref('Balance Detail')
async function openBalanceModal(step) {
  activeBalanceStepId.value = step.id
  currentPageBalance.value = 1
  balanceTitle.value = `Balance Detail - ${step.title}`
  balanceData.value = [] // Reset to empty immediately
  balanceModalOpen.value = true
  balanceLoading.value = true
  try {
    await store.fetchBalance(step.id, {}, 1)
  } finally {
    balanceLoading.value = false
  }
}

const feedLogModalOpen = ref(false)
const feedLogTitle = ref('')
const feedLogData = ref([])
async function openFeedLogModal(step) {
  activeFeedLogStepId.value = step.id
  currentPageFeedLog.value = 1
  feedLogTitle.value = `Feed Log - ${step.title}`
  feedLogData.value = [] // Reset to empty immediately
  feedLogModalOpen.value = true
  feedLogLoading.value = true
  try {
    const res = await store.fetchFeed(step.id, 'LOG', 1)
    feedLogData.value = res.data || []
  } finally {
    feedLogLoading.value = false
  }
}

const rundownLogModalOpen = ref(false)
const rundownLogTitle = ref('')
const rundownLogData = ref([])
async function openRundownLogModal(step) {
  activeRundownLogStepId.value = step.id
  currentPageRundownLog.value = 1
  rundownLogTitle.value = `Rundown Log - ${step.title}`
  rundownLogData.value = [] // Reset to empty immediately
  rundownLogModalOpen.value = true
  rundownLogLoading.value = true
  try {
    const res = await store.fetchRundown(step.id, 'LOG', 1)
    rundownLogData.value = normalizeRundownRows(res.data || [])
  } finally {
    rundownLogLoading.value = false
  }
}

async function reloadAll() {
  store.clearLogs()
  loading.value = true
  try {
    const tree = await store.fetchWipTree()
    initializeModes(tree.modeConfigs || {})
    for (const section of visibleSections.value) {
      const steps = section.steps.filter(s => s.type === 'feed' || s.type === 'rundown')
      for (const step of steps) {
        if (step.type === 'feed') {
          await store.fetchFeed(step.id, 'LATEST')
        } else {
          await store.fetchRundown(step.id, 'LATEST')
        }
      }
    }
  } finally {
    loading.value = false
  }
}

watch(feedModalOpen, (val) => {
  if (!val) {
    autoFetchFeed.stopCountdown()
    autoFetchFeed.stopAutoSync()
  }
})

watch(rundownModalOpen, (val) => {
  if (!val) {
    autoFetchRundown.stopCountdown()
    autoFetchRundown.stopAutoSync()
  }
})

onMounted(() => {
  reloadAll()
})
</script>

