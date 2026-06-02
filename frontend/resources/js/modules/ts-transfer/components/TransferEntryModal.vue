<template>
  <Teleport to="body">
    <div v-show="isOpen" class="fixed inset-0 z-[105] overflow-y-auto" role="dialog" aria-modal="true" :aria-hidden="!isOpen">
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />

        <div class="relative z-[2] mx-auto flex w-full max-w-5xl max-h-[min(92vh,940px)] flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 to-green-600 px-6 py-4 sm:px-8">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">Transfer</p>
              <h3 class="text-lg font-bold text-white sm:text-xl">Transfer Inter-Plant Entry</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="relative min-h-0 flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-5 py-5 sm:px-8 sm:py-6">
            <div
              v-if="loading"
              class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-xl bg-white/75 backdrop-blur-sm"
            >
              <svg class="h-11 w-11 animate-spin text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              <p class="text-sm font-semibold text-slate-600">Processing...</p>
            </div>

            <p v-if="formError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ formError }}</p>

            <form @submit.prevent="handleSubmit" :class="{ 'pointer-events-none opacity-45': loading }" class="space-y-5">
              <!-- Row 1: Header Fields -->
              <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Entry Mode</label>
                    <input :value="mode" type="text" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-bold text-slate-700" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Entry Number (Auto)</label>
                    <input :value="form.entry_no" type="text" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-sm font-bold text-slate-900" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Date (Auto Detect)</label>
                    <input v-model="form.entry_date" type="date" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material Document (SAP)</label>
                    <input v-model="form.material_doc" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25" />
                  </div>
                </div>
              </div>

              <!-- Row 2: Material + Trf Type -->
              <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:gap-6">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Transfer Material</label>
                    <select
                      v-model="form.id_material"
                      required
                      @change="onMaterialChange"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    >
                      <option value="">- Select Material -</option>
                      <option v-for="mat in store.activeMaterials" :key="mat.id_material" :value="mat.id_material">
                        {{ mat.material_code || mat.description || mat.material }}
                      </option>
                    </select>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                      Trf Type (<em>Trf ALL</em> only for TRF non-EOB1 to Adjust OUT)
                    </label>
                    <select
                      v-model="form.trf_type"
                      required
                      @change="onTrfTypeChange"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    >
                      <option value="">- Select Trf -</option>
                      <option value="in">{{ trfInLabel }}</option>
                      <option value="out">{{ trfOutLabel }}</option>
                      <option value="all">- Trf ALL -</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Row 3: SLoc + Qty (shown after material + trf type selected) -->
              <div v-if="showSloc" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 lg:gap-6">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                      Source SLoc <span class="text-[10px] font-normal text-slate-400">(change this for TRF IN)</span>
                    </label>
                    <select
                      v-model="form.source_sloc"
                      @change="onSourceChange"
                      :disabled="form.trf_type === 'out'"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
                    >
                      <option value="">- Select Sloc -</option>
                      <option v-for="tank in sourceTanks" :key="tank.id_tank" :value="tank.id_tank">
                        {{ tank.tank || tank.description }}
                      </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">{{ sourceStockLabel }}</p>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
                      Transfer SLoc <span class="text-[10px] font-normal text-slate-400">(change this for TRF OUT)</span>
                    </label>
                    <select
                      v-model="form.trf_sloc"
                      @change="onDestinationChange"
                      :disabled="form.trf_type === 'in'"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
                    >
                      <option value="">- Select Sloc -</option>
                      <option v-for="tank in destTanks" :key="tank.id_tank" :value="tank.id_tank">
                        {{ tank.tank || tank.description }}
                      </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">{{ destStockLabel }}</p>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Trf Qty (MT)</label>
                    <input
                      v-model="form.trf_qty"
                      type="number"
                      step="0.001"
                      min="0"
                      required
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    />
                  </div>
                </div>
              </div>

              <!-- Row 4: Specific SLoc (shown after sloc selected) -->
              <div v-if="showSpecificSlocRow" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:gap-6">
                  <div v-if="showSpecificSourceSloc" class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Specific Source Sloc</label>
                    <div class="max-h-32 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2">
                      <div v-if="specificSourceTanks.length === 0" class="px-2 py-1 text-xs text-slate-400">No specific sloc</div>
                      <label
                        v-for="tank in specificSourceTanks"
                        :key="tank.id_tank_tail"
                        class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer rounded"
                      >
                        <input
                          type="checkbox"
                          :value="String(tank.id_tank_tail)"
                          v-model="selectedSourceTails"
                          class="h-3.5 w-3.5 rounded border-slate-300 text-green-600 focus:ring-green-500"
                        />
                        <span class="text-xs text-slate-700">{{ tank.tankNo || tank.tf_number }}</span>
                      </label>
                    </div>
                  </div>
                  <div v-if="showSpecificDestSloc" class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Specific Transfer Sloc</label>
                    <div class="max-h-32 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2">
                      <div v-if="specificDestTanks.length === 0" class="px-2 py-1 text-xs text-slate-400">No specific sloc</div>
                      <label
                        v-for="tank in specificDestTanks"
                        :key="tank.id_tank_tail"
                        class="flex items-center gap-2 px-2 py-1 hover:bg-slate-50 cursor-pointer rounded"
                      >
                        <input
                          type="checkbox"
                          :value="String(tank.id_tank_tail)"
                          v-model="selectedDestTails"
                          class="h-3.5 w-3.5 rounded border-slate-300 text-green-600 focus:ring-green-500"
                        />
                        <span class="text-xs text-slate-700">{{ tank.tankNo || tank.tf_number }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="showSloc" class="flex items-center gap-3">
                <button
                  type="submit"
                  :disabled="loading"
                  class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                >
                  <Icon icon="ri:check-line" class="w-4 h-4 mr-1" /> Save Entry
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { usePlantSelectionStore } from '@/stores/plant'
import { useTsTransferStore } from '../stores'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['update:isOpen', 'success'])

const plantSelectionStore = usePlantSelectionStore()
const store = useTsTransferStore()

const mode = 'ADD'
const formError = ref(null)
const loading = ref(false)

const sourceTanks = ref([])
const destTanks = ref([])
const specificSourceTanks = ref([])
const specificDestTanks = ref([])
const selectedSourceTails = ref([])
const selectedDestTails = ref([])
const sourceStockLabel = ref('Stock : N/A')
const destStockLabel = ref('Stock : N/A')

const TANK_AUTO_IDS = [5, 6, 24, 25, 28, 29, 32, 33]

const form = reactive({
  entry_no: '',
  entry_date: new Date().toISOString().split('T')[0],
  id_material: '',
  trf_type: '',
  source_sloc: '',
  trf_sloc: '',
  trf_qty: 0,
  material_doc: '',
  idHead: null
})

const plantId = computed(() => plantSelectionStore.selectedPlantId)

const trfInLabel = computed(() => {
  const map = { 1002: 'EOB1', 1003: 'EOB2', 1007: 'EOB3' }
  const name = map[plantId.value] || 'EOMB'
  return `- Trf IN to ${name} -`
})

const trfOutLabel = computed(() => {
  const map = { 1002: 'EOB1', 1003: 'EOB2', 1007: 'EOB3' }
  const name = map[plantId.value] || 'EOMB'
  return `- Trf OUT from ${name} -`
})

const showSloc = computed(() => {
  return form.id_material && form.trf_type
})

const showSpecificSourceSloc = computed(() => {
  return showSloc.value && form.source_sloc && (form.trf_type === 'in' || form.trf_type === 'all')
})

const showSpecificDestSloc = computed(() => {
  return showSloc.value && form.trf_sloc && (form.trf_type === 'out' || form.trf_type === 'all')
})

const showSpecificSlocRow = computed(() => {
  return showSpecificSourceSloc.value || showSpecificDestSloc.value
})

async function bootstrap() {
  formError.value = null
  loading.value = true
  try {
    await store.fetchActiveMaterials()
    resetForm()
  } catch (err) {
    /* error logged */
    formError.value = 'Failed to load form data: ' + err.message
  }
  loading.value = false
}

function resetForm() {
  form.entry_no = ''
  form.entry_date = new Date().toISOString().split('T')[0]
  form.id_material = ''
  form.trf_type = ''
  form.source_sloc = ''
  form.trf_sloc = ''
  form.trf_qty = 0
  form.material_doc = ''
  form.idHead = null
  sourceTanks.value = []
  destTanks.value = []
  specificSourceTanks.value = []
  specificDestTanks.value = []
  selectedSourceTails.value = []
  selectedDestTails.value = []
  sourceStockLabel.value = 'Stock : N/A'
  destStockLabel.value = 'Stock : N/A'
}

async function onMaterialChange() {
  if (!form.id_material) return

  try {
    form.entry_no = '' // Reset entry no until sloc is filled

    if (form.trf_type) {
      await populateTanks()
    }
  } catch (err) {
    /* error logged */
    formError.value = err.message
  }
}

async function onTrfTypeChange() {
  if (!form.trf_type) {
    sourceTanks.value = []
    destTanks.value = []
    specificSourceTanks.value = []
    specificDestTanks.value = []
    form.source_sloc = ''
    form.trf_sloc = ''
    return
  }

  if (!form.id_material) {
    formError.value = 'Select Material first!'
    form.trf_type = ''
    return
  }

  await populateTanks()
}

async function populateTanks() {
  const trfType = form.trf_type
  const idMat = form.id_material
  const currentPlant = plantId.value

  try {
    if (trfType === 'in') {
      // Source = choices of SLoc of each plant (active plant SLocs)
      // Destination = Automatically EOMB (plant 1001) SLocs
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant }),
        store.fetchActiveTanksRundown({ idMaterial: idMat, id_plant: 1001 })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    } else if (trfType === 'out') {
      // Source = Automatically EOMB (plant 1001) SLocs
      // Destination = Choices of SLoc of active plant
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: idMat, id_plant: 1001 }),
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    } else {
      // 'all' - both = all tanks of active plant
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant }),
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    }

    // Auto-select logic
    const autoSelectSource = trfType === 'out' && sourceTanks.value.length > 0
    const autoSelectDest = trfType === 'in' && destTanks.value.length > 0

    if (autoSelectSource) {
      form.source_sloc = sourceTanks.value[0].id_tank
      await onSourceChange()
    } else {
      form.source_sloc = ''
    }

    if (autoSelectDest) {
      form.trf_sloc = destTanks.value[0].id_tank
      await onDestinationChange()
    } else {
      form.trf_sloc = ''
    }

    if (trfType === 'all') {
      if (sourceTanks.value.length > 0) {
        form.source_sloc = sourceTanks.value[0].id_tank
        await onSourceChange()
      }
      if (destTanks.value.length > 0) {
        form.trf_sloc = destTanks.value[0].id_tank
        await onDestinationChange()
      }
    }

    sourceStockLabel.value = 'Stock : N/A'
    destStockLabel.value = 'Stock : N/A'
  } catch (err) {
    /* error logged */
    formError.value = err.message
  }
}

async function onSourceChange() {
  if (!form.source_sloc) {
    specificSourceTanks.value = []
    return
  }
  try {
    const response = await store.fetchActiveSpecificTanksRundown({ sloc: form.source_sloc })
    specificSourceTanks.value = response?.data || []

    await updateStock('source')
    await updateEntryNoFromSloc()
  } catch (err) {
    /* error logged */
    specificSourceTanks.value = []
  }
}

async function onDestinationChange() {
  if (!form.trf_sloc) {
    specificDestTanks.value = []
    return
  }
  try {
    const response = await store.fetchActiveSpecificTanksRundown({ sloc: form.trf_sloc })
    specificDestTanks.value = response?.data || []

    await updateStock('dest')
    await updateEntryNoFromSloc()
  } catch (err) {
    /* error logged */
    specificDestTanks.value = []
  }
}

async function updateEntryNoFromSloc() {
  if (!form.id_material) return
  if (!form.trf_type) return

  if (form.trf_type === 'all' && (!form.source_sloc || !form.trf_sloc)) {
    form.entry_no = ''
    return
  }
  
  if (!form.source_sloc) {
    form.entry_no = ''
    return
  }

  let activePlantId = plantId.value
  const t = sourceTanks.value.find(x => x.id_tank === form.source_sloc)
  if (t && t.id_plant) activePlantId = t.id_plant

  if (activePlantId && activePlantId !== 0) {
    try {
      const entryResponse = await store.fetchNewEntryNo({
        id_plant: activePlantId,
        id_material: form.id_material
      })
      if (entryResponse?.data?.[0]?.entryNo) {
        form.entry_no = entryResponse.data[0].entryNo
      } else {
      }
    } catch (e) {
      /* error logged */
    }
  }
}

async function updateStock(type) {
  const idTank = type === 'source' ? form.source_sloc : form.trf_sloc
  if (!form.id_material || !idTank) return
  try {
    const response = await store.fetchTotalStockMaterial({
      idMaterial: form.id_material,
      idTank: idTank
    })
    const total = store.totalStock || 0
    const label = `Stock (MT): ${total}`

    if (type === 'source') {
      if (form.trf_type === 'in') {
        if (total <= 0 && !TANK_AUTO_IDS.includes(Number(idTank))) {
          sourceStockLabel.value = 'AUTO IN/OUT'
        } else {
          sourceStockLabel.value = label
        }
      } else {
        sourceStockLabel.value = label
      }
    } else {
      if (form.trf_type === 'in') {
        if (total <= 0 && !TANK_AUTO_IDS.includes(Number(idTank))) {
          destStockLabel.value = 'AUTO IN/OUT'
        } else {
          destStockLabel.value = label
        }
      } else {
        destStockLabel.value = label
      }
    }
  } catch (err) {
    /* error logged */
    if (type === 'source') {
      sourceStockLabel.value = 'Stock (MT): N/A'
    } else {
      destStockLabel.value = 'Stock (MT): N/A'
    }
  }
}

async function handleSubmit() {
  formError.value = null

  if (!form.trf_qty || parseFloat(form.trf_qty) <= 0) {
    formError.value = 'Entry Qty must be greater than 0'
    return
  }

  loading.value = true
  try {
    const payload = {
      entry_no: form.entry_no,
      entry_date: form.entry_date,
      id_material: form.id_material,
      trf_type: form.trf_type,
      trf_qty: form.trf_qty,
      source_sloc: form.source_sloc,
      trf_sloc: form.trf_sloc,
      source_sloc_no: selectedSourceTails.value,
      trf_sloc_no: selectedDestTails.value,
      material_doc: form.material_doc,
      id_plant: plantId.value
    }
    const response = await store.submitTransferEntry(payload)

    if (response?.status === 1) {
      emit('success')
      closeModal()
    } else {
      formError.value = response?.message || 'Transfer failed'
    }
  } catch (err) {
    /* error logged */
    formError.value = err.response?.data?.message || err.message || 'Error'
  }
  loading.value = false
}

function closeModal() {
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (val) => {
  if (val) {
    bootstrap()
  }
})
</script>
