<template>
  <VDialog
    :model-value="isOpen"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Blending Entry</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="closeModal"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 bg-neutral-50">
        <div v-if="blendingStore.loading" class="d-flex flex-column align-center justify-center pa-8">
          <VProgressCircular indeterminate color="primary" size="48" />
          <span class="mt-3 text-body-2 text-medium-emphasis">Processing...</span>
        </div>

        <VAlert
          v-if="formError"
          type="error"
          variant="tonal"
          class="mb-4"
          density="comfortable"
        >
          {{ formError }}
        </VAlert>

        <form @submit.prevent="handleSubmit" :class="{ 'opacity-50': blendingStore.loading }" class="d-flex flex-column gap-4">
          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" sm="6" md="3">
                  <VTextField
                    :model-value="mode"
                    label="Entry Mode"
                    readonly
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VTextField
                    :model-value="form.entry_no"
                    label="Entry Number (Auto)"
                    readonly
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VTextField
                    v-model="form.entry_date"
                    label="Date"
                    type="date"
                    required
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <VTextField
                    v-model="form.material_doc"
                    label="Material Document (SAP)"
                    density="compact"
                    variant="outlined"
                    class="text-uppercase"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="form.id_material"
                    label="Blended Material"
                    :items="materialOptions"
                    item-title="label"
                    item-value="value"
                    required
                    density="compact"
                    variant="outlined"
                    @update:model-value="onMaterialChange"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="form.id_tank"
                    label="Storage Location (SLoc)"
                    :items="tankOptions"
                    item-title="label"
                    item-value="value"
                    density="compact"
                    variant="outlined"
                    @update:model-value="onTankChange"
                  />
                </VCol>
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="selectedTankTails"
                    label="Specific Storage Location"
                    :items="specificTankOptions"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    variant="outlined"
                    density="compact"
                    :disabled="!form.id_tank"
                    :placeholder="form.id_tank ? '' : 'Select a tank first'"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardText class="d-flex flex-wrap align-center justify-space-between gap-3">
              <div class="d-flex gap-2">
                <VBtn
                  color="secondary"
                  prepend-icon="ri-add-circle-line"
                  @click="openSourceMaterialModal"
                >
                  Add Blend Source & Qty
                </VBtn>
                <VBtn
                  type="submit"
                  color="primary"
                  prepend-icon="ri-check-line"
                  :loading="blendingStore.loading"
                >
                  Save Entry
                </VBtn>
              </div>
              <div class="d-flex align-center gap-2">
                <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Total Qty (MT)</label>
                <VTextField
                  :model-value="formattedTotalQty"
                  readonly
                  density="compact"
                  variant="outlined"
                  style="width:144px"
                  class="text-right"
                />
              </div>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VTable density="comfortable" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-center text-caption font-weight-medium text-medium-emphasis" style="width:7%">No</th>
                  <th class="text-center text-caption font-weight-medium text-medium-emphasis">Action</th>
                  <th class="text-left text-caption font-weight-medium text-medium-emphasis">Material</th>
                  <th class="text-right text-caption font-weight-medium text-medium-emphasis">Qty (MT)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, idx) in blendingStore.materialList" :key="item.id || idx">
                  <td class="text-center">{{ idx + 1 }}</td>
                  <td class="text-center">
                    <VBtn
                      icon="ri-delete-bin-line"
                      size="x-small"
                      color="error"
                      variant="text"
                      @click="removeMaterial(item)"
                    />
                  </td>
                  <td>{{ item.material }}</td>
                  <td class="text-right">{{ item.qty }}</td>
                </tr>
                <tr v-if="blendingStore.materialList.length === 0">
                  <td colspan="4" class="text-center text-disabled py-4 text-body-2">No source materials added yet</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </form>
      </VCardText>
    </VCard>
  </VDialog>

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
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant.js'
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
    // Lookup using fallback: id_sloc (new) or id_tank (legacy)
    const tank = blendingStore.allTanks.find(t => String(t.id_sloc || t.id_tank) === String(form.id_tank))
    if (tank && tank.id_plant) {
      return tank.id_plant
    }
  }
  const firstTank = blendingStore.allTanks.find(t => t.id_plant)
  return firstTank?.id_plant || setupPlantStore.plants[0]?.code_3 || setupPlantStore.plants[0]?.id || 0
})

const form = reactive({
  entry_no: '',
  entry_date: new Date().toISOString().split('T')[0],
  id_material: '',
  id_tank: '',
  material_doc: '',
  idHead: null
})

const materialOptions = computed(() => {
  return (blendingStore.activeMaterials || []).map(m => ({
    value: m.id_material,
    label: String(m.material_code || m.description || m.material || m.id_material || '')
  })).filter(item => item.value && item.label)
})

const tankOptions = computed(() => {
  return (blendingStore.allTanks || []).map(t => ({
    value: t.id_sloc || t.id_tank,
    label: String(t.tank || t.description || t.id_sloc || t.id_tank || '')
  })).filter(item => item.value && item.label)
})

const formattedTotalQty = computed(() => {
  const qty = blendingStore.totalQty || 0
  return Number(qty).toFixed(3)
})

const specificTankOptions = computed(() =>
  (specificTanks.value || []).map(t => ({
    value: String(t.id_sloc || t.id_tank_tail),
    title: t.tankName || t.tankNo || t.tf_number,
  }))
)

async function bootstrap() {
  formError.value = null

  try {
    if (setupPlantStore.plants.length === 0) {
      await setupPlantStore.fetchPlants()
    }
    await Promise.all([
      blendingStore.fetchActiveMaterials(),
      blendingStore.fetchAllTanks({ id_plant: plantSelectionStore.selectedPlantId || 0 })
    ])

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

async function fetchQty(){
    if(!form.idMaterialSource){errorMsg.value='Select material first';return}
    blendingStore.fetchQty({idMaterial: form.idMaterialSource, idPlant: props.idPlant})
      .then(r=>{if(r?.status===1)form.qty=r.data?.qty||'';else errorMsg.value=r?.message||'Fetch failed'})
      .catch(e=>{errorMsg.value=e.message})
}

async function onMaterialChange() {
  const plantId = activePlantId.value

  if (!form.id_material) return

  try {
    const entryResponse = await blendingStore.fetchNewEntryNo({
      id_plant: plantId,
      id_material: form.id_material
    })
    if (entryResponse?.data?.[0]?.entryNo) {
      form.entry_no = entryResponse.data[0].entryNo
    }

    await blendingStore.fetchActiveTanksRundown({
      idMaterial: form.id_material,
      id_plant: activePlantId.value
    })
    if (blendingStore.activeTanks.length > 0) {
      // Use fallback: id_sloc (new) or id_tank (legacy)
      const matchingTankId = blendingStore.activeTanks[0].id_sloc || blendingStore.activeTanks[0].id_tank
      const found = blendingStore.allTanks.find(t => Number(t.id_sloc || t.id_tank) === Number(matchingTankId))
      if (found) {
        form.id_tank = found.id_sloc || found.id_tank
        await onTankChange()
      }
    }

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
      await blendingStore.fetchActiveSpecificTanksRundown({ sloc: form.id_tank })
      specificTanks.value = blendingStore.activeSpecificTanks
      if (specificTanks.value.length === 1) {
        // Use fallback: id_sloc or id_tank_tail (backend provides both as alias)
        selectedTankTails.value = [String(specificTanks.value[0].id_sloc || specificTanks.value[0].id_tank_tail)]
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
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (val) => {
  if (!val && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (val) {
    bootstrap()
  } else {
    selectedTankTails.value = []
    specificTanks.value = []
  }
})
</script>
