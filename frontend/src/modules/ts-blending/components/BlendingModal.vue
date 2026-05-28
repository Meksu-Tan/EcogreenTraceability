<template>
  <Teleport to="body">
    <div
      v-show="isOpen"
      class="fixed inset-0 z-[105] overflow-y-auto"
      role="dialog"
      aria-modal="true"
      :aria-hidden="!isOpen"
    >
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />

        <div class="relative z-[2] mx-auto flex w-full max-w-5xl max-h-[min(92vh,940px)] flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 to-green-600 px-6 py-4 sm:px-8">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">Blending</p>
              <h3 class="text-lg font-bold text-white sm:text-xl">Blending Entry</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="relative min-h-0 flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-5 py-5 sm:px-8 sm:py-6">
            <div
              v-if="blendingStore.loading"
              class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-xl bg-white/75 backdrop-blur-sm"
            >
              <svg class="h-11 w-11 animate-spin text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              <p class="text-sm font-semibold text-slate-600">Processing...</p>
            </div>

            <p v-if="formError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ formError }}</p>

            <form @submit.prevent="handleSubmit" :class="{ 'pointer-events-none opacity-45': blendingStore.loading }" class="space-y-5">
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
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Date</label>
                    <input v-model="form.entry_date" type="date" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25" />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material Document (SAP)</label>
                    <input v-model="form.material_doc" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25" />
                  </div>
                </div>
              </div>



              <!-- Row 2: Material + Tank -->
              <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 lg:gap-6">
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Blended Material</label>
                    <select
                      v-model="form.id_material"
                      required
                      @change="onMaterialChange"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    >
                      <option value="" disabled>- Select Material -</option>
                      <option v-for="mat in blendingStore.activeMaterials" :key="mat.id_material" :value="mat.id_material">
                        {{ mat.material_code || mat.description || mat.material }}
                      </option>
                    </select>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Storage Location (SLoc)</label>
                    <select
                      v-model="form.id_tank"
                      @change="onTankChange"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    >
                      <option value="">- Select Tank -</option>
                      <option v-for="tank in blendingStore.allTanks" :key="tank.id_tank" :value="tank.id_tank">
                        {{ tank.tank || tank.description }}
                      </option>
                    </select>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Specific Storage Location</label>
                    <div class="max-h-32 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-inner">
                      <div v-if="specificTanks.length === 0" class="px-2 py-2 text-center text-xs text-slate-400 italic">
                        {{ form.id_tank ? 'No specific sloc' : 'Select a tank first' }}
                      </div>
                      <div v-else class="grid grid-cols-2 gap-1.5">
                        <label
                          v-for="tank in specificTanks"
                          :key="tank.id_tank_tail"
                          class="flex cursor-pointer items-center gap-2 rounded-lg border border-transparent bg-white px-2 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-green-200 hover:bg-green-50/50"
                        >
                          <input
                            type="checkbox"
                            :value="String(tank.id_tank_tail)"
                            v-model="selectedTankTails"
                            class="h-3.5 w-3.5 rounded border-slate-300 text-green-600 focus:ring-green-500"
                          />
                          <span>{{ tank.tankNo || tank.tf_number }}</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Row 3: Buttons + Total Qty -->
              <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex items-center gap-3">
                  <button
                    type="button"
                    @click="openSourceMaterialModal"
                    class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-900"
                  >
                    <i class="fas fa-plus mr-1"></i> Add Blend Source & Qty
                  </button>
                  <button
                    type="submit"
                    :disabled="blendingStore.loading"
                    class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:opacity-50"
                  >
                    <i class="fas fa-check mr-1"></i> Save Entry
                  </button>
                </div>
                <div class="flex items-center gap-2">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Total Qty (MT)</label>
                  <input
                    :value="formattedTotalQty"
                    type="text"
                    readonly
                    class="w-36 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-right text-sm font-bold text-slate-900"
                  />
                </div>
              </div>

              <!-- Row 4: Detail DataTable -->
              <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-[7%]">No</th>
                      <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty (MT)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="(item, idx) in blendingStore.materialList" :key="item.id || idx" class="hover:bg-gray-50">
                      <td class="px-4 py-3 text-center text-gray-900">{{ idx + 1 }}</td>
                      <td class="px-4 py-3 text-center">
                        <button
                          type="button"
                          @click="removeMaterial(item)"
                          class="text-red-500 hover:text-red-700"
                          title="Delete Material"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                      <td class="px-4 py-3 text-gray-900">{{ item.material }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ item.qty }}</td>
                    </tr>
                    <tr v-if="blendingStore.materialList.length === 0">
                      <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-400">No source materials added yet</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Source Material Sub-Modal -->
  <BlendingSourceMaterialModal
    v-model:is-open="isSourceModalOpen"
    :entry-no="form.entry_no"
    :mode="mode"
    :id-head="form.idHead"
    :id-tank="form.id_tank"
    :id-plant="activePlantId"
    @success="onSourceMaterialInserted"
  />
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant'
import { useTsBlendingStore } from '../stores'
import BlendingSourceMaterialModal from './BlendingSourceMaterialModal.vue'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['update:isOpen', 'success'])

const plantSelectionStore = usePlantSelectionStore()
const setupPlantStore = useSetupPlantStore()
const blendingStore = useTsBlendingStore()

const mode = 'ADD'
const formError = ref(null)
const isSourceModalOpen = ref(false)
const selectedTankTails = ref([])
const specificTanks = ref([])

const isAllPlant = computed(() => plantSelectionStore.selectedPlantId === null)

const activePlantId = computed(() => {
  if (!isAllPlant.value) {
    return plantSelectionStore.selectedPlantId
  }
  if (form.id_tank) {
    const tank = blendingStore.allTanks.find(t => String(t.id_tank) === String(form.id_tank))
    if (tank && tank.id_plant) {
      return tank.id_plant
    }
  }
  return 0
})

const form = reactive({
  entry_no: '',
  entry_date: new Date().toISOString().split('T')[0],
  id_material: '',
  id_tank: '',
  material_doc: '',
  idHead: null
})

const formattedTotalQty = computed(() => {
  const qty = blendingStore.totalQty || 0
  return Number(qty).toFixed(3)
})

async function bootstrap() {
  formError.value = null
  const plantId = activePlantId.value

  try {
    // Load all needed data
    await Promise.all([
      blendingStore.fetchActiveMaterials(),
      blendingStore.fetchAllTanks({ id_plant: plantSelectionStore.selectedPlantId || 0 })
    ])

    // Clear form for ADD mode
    form.entry_no = ''
    form.id_material = ''
    form.id_tank = ''
    form.material_doc = ''
    form.idHead = null
    form.entry_date = new Date().toISOString().split('T')[0]
    blendingStore.totalQty = 0
    blendingStore.materialList = []
    selectedTankTails.value = []
    specificTanks.value = []
  } catch (err) {
    formError.value = 'Failed to load form data: ' + err.message
  }
}

async function onMaterialChange() {
  const plantId = activePlantId.value

  if (!form.id_material) return

  try {
    // Generate new entry no
    const entryResponse = await blendingStore.fetchNewEntryNo({
      id_plant: plantId,
      id_material: form.id_material
    })
    if (entryResponse?.data?.[0]?.entryNo) {
      form.entry_no = entryResponse.data[0].entryNo
    }

    // Auto-select matching tank from allTanks if available
    await blendingStore.fetchActiveTanksRundown({
      idMaterial: form.id_material,
      id_plant: plantSelectionStore.selectedPlantId || 0
    })
    if (blendingStore.activeTanks.length > 0) {
      const matchingTankId = blendingStore.activeTanks[0].id_tank
      const found = blendingStore.allTanks.find(t => Number(t.id_tank) === Number(matchingTankId))
      if (found) {
        form.id_tank = found.id_tank
        await onTankChange()
      }
    }

    // Load material list for this entry
    await refreshMaterialList()
    await refreshTotalQty()
  } catch (err) {
    formError.value = err.message
  }
}

async function onTankChange() {
  if (form.id_material && form.id_tank) {
    try {
      const entryResponse = await blendingStore.fetchNewEntryNo({
        id_plant: activePlantId.value,
        id_material: form.id_material
      })
      if (entryResponse?.data?.[0]?.entryNo) {
        form.entry_no = entryResponse.data[0].entryNo
      }
    } catch (e) {}
  }

  if (form.id_tank) {
    try {
      const response = await blendingStore.fetchActiveSpecificTanksRundown({ sloc: form.id_tank })
      specificTanks.value = response?.data || []
      if (specificTanks.value.length === 1) {
        selectedTankTails.value = [String(specificTanks.value[0].id_tank_tail)]
      } else {
        selectedTankTails.value = []
      }
    } catch (err) {
      specificTanks.value = []
    }
  } else {
    specificTanks.value = []
    selectedTankTails.value = []
  }
}

async function refreshMaterialList() {
  if (!form.entry_no) return
  const plantId = activePlantId.value
  await blendingStore.fetchMaterialList({
    mode: mode,
    entryNo: form.entry_no,
    id_plant: plantId
  })
}

async function refreshTotalQty() {
  if (!form.entry_no) return
  const plantId = activePlantId.value
  await blendingStore.fetchTotalQtyMaterial({
    mode: mode,
    entryNo: form.entry_no,
    id_plant: plantId
  })
}

function openSourceMaterialModal() {
  if (!form.entry_no) {
    formError.value = 'Select a material first'
    return
  }
  isSourceModalOpen.value = true
}

async function onSourceMaterialInserted() {
  isSourceModalOpen.value = false
  await refreshMaterialList()
  await refreshTotalQty()
}

async function removeMaterial(item) {
  try {
    await blendingStore.deleteBlendingMaterial({ id: item.idTail })
    await refreshMaterialList()
    await refreshTotalQty()
  } catch (err) {
    formError.value = err.message
  }
}

async function handleSubmit() {
  formError.value = null
  const plantId = activePlantId.value

  if (blendingStore.materialList.length === 0) {
    formError.value = 'No source materials added'
    return
  }

  try {
    const response = await blendingStore.executeBlending({
      entry_no: form.entry_no,
      entry_date: form.entry_date,
      id_material: form.id_material,
      material_doc: form.material_doc,
      qty: blendingStore.totalQty || '0',
      tankNo: selectedTankTails.value,
      id_plant: plantId
    })

    if (response?.status === 1) {
      emit('success')
      closeModal()
    } else {
      formError.value = response?.message || 'Blending failed'
    }
  } catch (err) {
    formError.value = err.message
  }
}

function closeModal() {
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (val) => {
  if (val) {
    bootstrap()
  } else {
    selectedTankTails.value = []
    specificTanks.value = []
  }
})
</script>
