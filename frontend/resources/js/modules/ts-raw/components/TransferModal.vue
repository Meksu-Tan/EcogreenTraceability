<template>
  <VDialog
    :model-value="isOpen"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Transfer to feed tank</span>
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
        <VAlert
          v-if="initError && !initLoading"
          type="error"
          variant="tonal"
          class="mb-4"
          density="comfortable"
        >
          <div class="d-flex flex-wrap align-center justify-space-between ga-2">
            <span>{{ initError }}</span>
            <VBtn color="error" variant="flat" size="small" @click="bootstrap">Try again</VBtn>
          </div>
        </VAlert>

        <form @submit.prevent="handleSubmit" class="d-flex flex-column gap-4">
          <VCard variant="outlined">
            <VCardTitle class="d-flex align-center justify-space-between border-b pa-4">
              <span class="text-body-1 font-weight-bold">Transfer summary</span>
            </VCardTitle>
            <VCardText class="pt-4">
              <p class="text-caption text-medium-emphasis mb-4">From storage to feed tank — fill source &amp; destination tanks, then add material.</p>

              <VAlert
                v-if="isPlantLocked"
                type="success"
                variant="tonal"
                density="compact"
                class="mb-4"
                icon="ri-lock-line"
              >
                Sloc locked to plant: {{ plantSelectionStore.selectedPlantName }}
              </VAlert>

              <VRow dense>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    :model-value="form.entry_no"
                    label="Entry number"
                    placeholder="AUTO GENERATE"
                    :loading="initLoading && !form.entry_no"
                    readonly
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.entry_date"
                    label="Transfer date"
                    type="date"
                    required
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.material_document"
                    label="Material document"
                    density="compact"
                    variant="outlined"
                    class="text-uppercase"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" md="6">
                  <VCard variant="outlined" class="bg-neutral-50">
                    <VCardTitle class="d-flex align-center ga-2 border-b pa-3 py-2">
                      <VAvatar size="24" color="primary">
                        <span class="text-caption font-weight-bold text-on-primary">1</span>
                      </VAvatar>
                      <span class="text-caption font-weight-bold text-uppercase">Source tank (storage)</span>
                    </VCardTitle>
                    <VCardText>
                      <VRow dense>
                        <VCol cols="12">
                          <VSelect
                            v-model="form.source_tank"
                            label="Source Sloc"
                            :items="tankOptions"
                            item-title="label"
                            item-value="value"
                            :loading="initLoading"
                            required
                            density="compact"
                            variant="outlined"
                            @update:model-value="onSourceTankChange"
                          />
                        </VCol>
                        <VCol cols="12">
                          <VSelect
                            v-model="form.source_tank_id"
                            label="Sub-Sloc"
                            :items="sourceTankDetails"
                            item-title="tf_number"
                            item-value="id_sloc"
                            multiple
                            chips
                            closable-chips
                            variant="outlined"
                            density="compact"
                            :disabled="!form.source_tank"
                            placeholder="Select Sloc first"
                          />
                        </VCol>
                      </VRow>
                    </VCardText>
                  </VCard>
                </VCol>

                <VCol cols="12" md="6">
                  <VCard variant="outlined" class="bg-neutral-50">
                    <VCardTitle class="d-flex align-center ga-2 border-b pa-3 py-2">
                      <VAvatar size="24" color="primary">
                        <span class="text-caption font-weight-bold text-on-primary">2</span>
                      </VAvatar>
                      <span class="text-caption font-weight-bold text-uppercase">Destination tank (feed)</span>
                    </VCardTitle>
                    <VCardText>
                      <VRow dense>
                        <VCol cols="12">
                          <VSelect
                            v-model="form.trf_tank"
                            label="Destination Sloc"
                            :items="destTankOptions"
                            item-title="label"
                            item-value="value"
                            :loading="initLoading"
                            required
                            density="compact"
                            variant="outlined"
                            @update:model-value="onTrfTankChange"
                          />
                        </VCol>
                        <VCol cols="12">
                          <VSelect
                            v-model="form.trf_tank_id"
                            label="Sub-Sloc"
                            :items="trfTankDetails"
                            item-title="tf_number"
                            item-value="id_sloc"
                            multiple
                            chips
                            closable-chips
                            variant="outlined"
                            density="compact"
                            :disabled="!form.trf_tank"
                            placeholder="Select Sloc first"
                          />
                        </VCol>
                      </VRow>
                    </VCardText>
                  </VCard>
                </VCol>
              </VRow>

              <VRow class="mt-2" dense>
                <VCol cols="12" class="d-flex flex-wrap align-center justify-space-between ga-3 border-t pt-4">
                  <div class="d-flex flex-wrap ga-2">
                    <VBtn
                      color="secondary"
                      prepend-icon="ri-layers-line"
                      @click="isMaterialModalOpen = true"
                    >
                      Material &amp; Qty
                    </VBtn>
                    <VBtn
                      type="button"
                      color="primary"
                      :disabled="!canSubmit || loading"
                      @click="handleSubmit"
                    >
                      Confirm transfer
                    </VBtn>
                  </div>
                  <div class="d-flex align-center ga-3">
                    <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Total (MT)</span>
                    <VTextField
                      :model-value="totalQty"
                      readonly
                      density="compact"
                      variant="outlined"
                      style="width:144px"
                      class="text-right"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardTitle class="bg-neutral-50 text-caption font-weight-bold text-uppercase pa-3">
              Transferred material
            </VCardTitle>
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr class="bg-neutral-50">
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Manufacturer</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Qty (MT)</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:80px">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="materialList.length === 0">
                  <td colspan="5" class="text-center text-disabled py-6 text-body-2">
                    No material yet — use "Material &amp; Qty".
                  </td>
                </tr>
                <tr v-for="(mat, index) in materialList" :key="mat.id">
                  <td class="text-center text-caption text-medium-emphasis">{{ index + 1 }}</td>
                  <td class="text-caption">{{ mat.material }}</td>
                  <td class="text-caption">{{ mat.manufacturer }}</td>
                  <td class="text-right font-weight-medium text-caption font-mono">{{ mat.qty }}</td>
                  <td class="text-center">
                    <VBtn
                      icon="ri-delete-bin-line"
                      size="x-small"
                      color="error"
                      variant="tonal"
                      @click="removeMaterial(mat.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </form>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="closeModal">Close</VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-save-line"
          :loading="loading"
          @click="handleSubmit"
        >
          {{ loading ? 'Saving...' : 'Confirm transfer' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="isMaterialModalOpen" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Add material &amp; qty</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="isMaterialModalOpen = false" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div class="d-flex flex-column ga-3">
          <VSelect
            v-model="materialForm.id_material"
            label="Material"
            :items="materialOptions"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
          />
          <VCombobox
            v-model="materialForm.id_manufacturer"
            label="Manufacturer"
            :items="manufacturerOptions"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
            placeholder="Select or type manufacturer"
            clearable
            :return-object="false"
          />
          <VTextField
            v-model="materialForm.qty"
            label="Qty (MT)"
            type="number"
            step="0.001"
            placeholder="0.000"
            density="compact"
            variant="outlined"
            class="text-right"
          />
          <div class="d-flex flex-row-reverse ga-2 pt-2">
            <VBtn variant="outlined" color="medium-emphasis" @click="isMaterialModalOpen = false">Cancel</VBtn>
            <VBtn color="primary" :disabled="!canAddMaterial" @click="addMaterial">Add</VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTsRawRmEntryStore } from '@/modules/ts-raw/stores'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const confirmStore = useConfirmStore()

const props = defineProps({
  isOpen: { type: Boolean, required: true }
})

const emit = defineEmits(['close', 'saved'])

const store = useTsRawRmEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const toastStore = useToastStore()

const isMaterialModalOpen = ref(false)
const initLoading = ref(false)
const initError = ref(null)
const sourceTankDetails = ref([])
const trfTankDetails = ref([])

const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  entry_no: '',
  source_tank: '',
  source_tank_id: [],
  trf_tank: '',
  trf_tank_id: [],
  material_document: ''
})

const materialForm = ref({
  id_material: '',
  id_manufacturer: null,
  qty: ''
})

const loading = computed(() => store.loading)
const tanks = computed(() => store.tanks)
const destTanks = computed(() => {
  if (form.value.source_tank) {
    const selectedSourceTank = tanks.value.find(t => t.tank === form.value.source_tank)
    if (selectedSourceTank && selectedSourceTank.id_plant) {
      return store.destTanks.filter(t => t.id_plant === selectedSourceTank.id_plant)
    }
  }
  return store.destTanks
})
const materials = computed(() => store.materials)
const materialList = computed(() => store.supplierList)
const totalQty = computed(() => store.totalQty)

const tankOptions = computed(() => {
  return (tanks.value || []).map(t => ({ value: t.tank, label: t.tank }))
})

const destTankOptions = computed(() => {
  return (destTanks.value || []).map(t => ({ value: t.tank, label: t.tank }))
})

const materialOptions = computed(() => {
  return (materials.value || []).map(m => ({ value: m.id_material, label: m.material }))
})

const selectedMaterialType = computed(() => {
  try {
    if (!materialForm.value.id_material) return null
    const mat = materials.value.find(m => Number(m.id_material) === Number(materialForm.value.id_material))
    if (!mat?.material) return null
    const lastParen = mat.material.lastIndexOf('(')
    if (lastParen < 0) return null
    return mat.material.substring(lastParen + 1).split('/')[0].trim().split('-')[0] || null
  } catch {
    return null
  }
})

const manufacturerOptions = computed(() => {
  try {
    const mfgs = store.manufacturers
    if (!mfgs?.length) return []
    const all = mfgs.map(m => ({ value: m.id_manufacturer, label: m.manufacturer }))
    const type = selectedMaterialType.value
    if (!type) return all
    const t = type.toLowerCase()
    const filtered = mfgs
      .filter(m => m.material_type?.toLowerCase().startsWith(t))
      .map(m => ({ value: m.id_manufacturer, label: m.manufacturer }))
    return filtered.length ? filtered : all
  } catch {
    return []
  }
})

const canAddMaterial = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_no &&
         materialForm.value.id_material &&
         materialForm.value.qty &&
         parseFloat(materialForm.value.qty) > 0
})

const canSubmit = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_date &&
         form.value.entry_no &&
         form.value.source_tank &&
         form.value.source_tank_id.length > 0 &&
         form.value.trf_tank &&
         form.value.trf_tank_id.length > 0 &&
         materialList.value.length > 0
})

const isPlantLocked = computed(() => {
  const id = plantSelectionStore.selectedPlantId
  return id !== null && id !== undefined && id !== '' && Number(id) !== 0
})

const entryNoPlaceholder = computed(() => {
  if (form.value.entry_no) return ''
  if (isPlantLocked.value) return 'Generating number...'
  return 'Select source plant or sloc'
})

async function generateEntryNumber(extra = {}) {
  const params = {
    id_plant: plantSelectionStore.selectedPlantId ?? 0,
    ...extra
  }
  await store.generateTransferNumber(params)
  form.value.entry_no = store.trfNumber || ''
  return form.value.entry_no
}

async function loadTankOptions() {
  const params = { id_plant: plantSelectionStore.selectedPlantId }
  await Promise.all([
    store.fetchTanks(params, true),
    store.fetchDestTanks(params)
  ])
}

async function autoSelectTanksWhenSingle() {
  if (!isPlantLocked.value) return
  if (!form.value.source_tank && tanks.value.length === 1) {
    form.value.source_tank = tanks.value[0].tank
    await onSourceTankChange()
  }
}

async function bootstrap() {
  initLoading.value = true
  initError.value = null
  store.resetForm()

  form.value = {
    entry_date: new Date().toISOString().split('T')[0],
    entry_no: '',
    source_tank: '',
    source_tank_id: [],
    trf_tank: '',
    trf_tank_id: [],
    material_document: ''
  }
  materialForm.value = { id_material: '', id_manufacturer: null, qty: '' }
  sourceTankDetails.value = []
  trfTankDetails.value = []
  isMaterialModalOpen.value = false

  try {
    await Promise.all([
      loadTankOptions(),
      store.fetchMaterials()
    ])

    if (isPlantLocked.value) {
      await generateEntryNumber()
      await autoSelectTanksWhenSingle()
    }
  } catch (error) {
    toastStore.error('Initialization error:', error)
    initError.value =
      error.response?.data?.message ||
      error.message ||
      'Failed to load form data. Ensure Laravel API (Sanctum) and MySQL are accessible from frontend.'
  } finally {
    initLoading.value = false
  }

  if (form.value.entry_no && !initError.value) {
    try {
      await store.fetchSupplierList(form.value.entry_no)
    } catch (error) {
      toastStore.error('Material temp list load:', error)
      initError.value =
        error.response?.data?.message ||
        error.message ||
        'Failed to load temporary material list.'
    }
  }
}

async function onSourceTankChange() {
  form.value.source_tank_id = []
  if (form.value.source_tank) {
    const selectedTank = tanks.value.find(t => t.tank === form.value.source_tank)
    await store.fetchTankDetails(selectedTank?.id_sloc ?? form.value.source_tank, plantSelectionStore.selectedPlantId)
    sourceTankDetails.value = [...store.tankDetails]
    if (sourceTankDetails.value.length === 1) {
      form.value.source_tank_id = [sourceTankDetails.value[0].id_sloc]
    }

    form.value.trf_tank = form.value.source_tank.replace('Storage', 'Feed')
    await onTrfTankChange()

    if (!isPlantLocked.value) {
      await generateEntryNumber({
        id_plant: 0,
        tank_desc: form.value.source_tank
      })
    }
  } else {
    sourceTankDetails.value = []
    form.value.trf_tank = ''
    trfTankDetails.value = []
  }
}

async function onTrfTankChange() {
  form.value.trf_tank_id = []
  if (form.value.trf_tank) {
    const destTank = store.destTanks.find(t => t.tank === form.value.trf_tank)
    await store.fetchTankDetails(destTank?.id_sloc ?? form.value.trf_tank, plantSelectionStore.selectedPlantId)
    trfTankDetails.value = [...store.tankDetails]
    if (trfTankDetails.value.length === 1) {
      form.value.trf_tank_id = [trfTankDetails.value[0].id_sloc]
    }
  } else {
    trfTankDetails.value = []
  }
}

async function addMaterial() {
  if (!canAddMaterial.value) return
  const selectedTankObj = tanks.value.find(t => t.tank === form.value.source_tank)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    await store.addSupplier({
      entry_no: form.value.entry_no,
      id_material: materialForm.value.id_material,
      id_manufacturer: materialForm.value.id_manufacturer,
      qty: parseFloat(materialForm.value.qty),
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId
    })
    materialForm.value = { id_material: '', id_manufacturer: null, qty: '' }
    isMaterialModalOpen.value = false
  } catch (error) {
    toastStore.error('Add material error:', error)
  }
}

async function removeMaterial(id) {
  const isConfirmed = await confirmStore.show({ message: 'Remove this material?' })
  if (isConfirmed) {
    await store.deleteSupplier(id, form.value.entry_no)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return

  if (form.value.source_tank_id.length === 0 || form.value.trf_tank_id.length === 0) {
    toastStore.error('Please select both source and destination tanks')
    return
  }

  const selectedTankObj = tanks.value.find(t => t.tank === form.value.source_tank)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    await store.transferEntry({
      ...form.value,
      source_tank: form.value.source_tank_id,
      tank_no: [],
      trf_tank: form.value.trf_tank_id,
      trf_tank_no: [],
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId
    })
    emit('saved')
    closeModal()
  } catch (error) {
    toastStore.error('Submit error:', error)
    const errorMsg = error.response?.data?.message || error.message || 'Transfer failed'
    toastStore.error(errorMsg)
    if (form.value.entry_no) {
      try {
        await store.clearTempList(form.value.entry_no)
      } catch (e) {
        toastStore.error('Failed to clear temp list on submit error:', e)
      }
    }
    await bootstrap()
  }
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('close')
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      void bootstrap()
    } else {
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur()
      }
      initLoading.value = false
      initError.value = null
      isMaterialModalOpen.value = false
    }
  },
  { flush: 'post' }
)

watch(isMaterialModalOpen, async (open) => {
  if (!open) return
  try {
    await store.fetchManufacturers(true)
  } catch (e) {
    toastStore.error('Failed to load manufacturers')
  }
})

watch(
  () => plantSelectionStore.selectedPlantId,
  async (plantId, prevId) => {
    if (!props.isOpen || plantId === prevId) return

    form.value.source_tank = ''
    form.value.trf_tank = ''
    form.value.tank_no = []
    form.value.trf_tank_no = []
    sourceTankDetails.value = []
    trfTankDetails.value = []
    form.value.entry_no = ''

    try {
      await loadTankOptions()
      if (isPlantLocked.value) {
        await generateEntryNumber()
        await autoSelectTanksWhenSingle()
        if (form.value.entry_no) {
          await store.fetchSupplierList(form.value.entry_no)
        }
      }
    } catch (error) {
      toastStore.error('Plant switch reload:', error)
    }
  }
)
</script>
