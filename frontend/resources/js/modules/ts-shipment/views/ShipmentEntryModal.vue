<template>
  <VDialog
    :model-value="modelValue"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Shipment Entry</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="close"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 bg-neutral-50">
        <VAlert v-if="submitError" type="error" variant="tonal" density="comfortable" class="mb-4">{{ submitError }}</VAlert>

        <form @submit.prevent="save" class="d-flex flex-column ga-4">
          <VCard variant="outlined">
            <VCardText>
              <VRow dense class="mb-2">
                <VCol cols="12" md="3">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Plant</label>
                  <VSelect
                    v-model="selectedPlantForTransfer"
                    :items="plantOptions"
                    item-title="label"
                    item-value="value"
                    :loading="initLoading"
                    :disabled="plantSelectionStore.selectedPlantId !== null"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    :clearable="plantSelectionStore.selectedPlantId === null"
                    @update:model-value="onPlantChange"
                  />
                </VCol>
              </VRow>
              <VRow dense>
                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Entry Mode</label>
                  <VTextField
                    model-value="ADD"
                    readonly
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Entry Date</label>
                  <VTextField
                    v-model="form.entryDate"
                    type="date"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Trace No</label>
                  <VTextField
                    v-model="newTraceNo"
                    readonly
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    placeholder="Auto Generated"
                    :loading="traceNoLoading"
                    :append-inner-icon="traceNoLoading ? 'ri-loader-4-line ri-spin' : ''"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" md="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Product</label>
                  <VSelect
                    v-model="form.fgProduct"
                    :items="store.activeFgProducts"
                    item-title="material"
                    item-value="id_material"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @update:model-value="onProductChange"
                  />
                  <div class="mt-1 text-caption text-primary font-weight-semibold">
                    {{ store.wipMaterialLabel || 'Product : N/A' }}
                  </div>
                </VCol>

                <VCol cols="12" md="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Batch No</label>
                  <VSelect
                    v-model="form.batch_no"
                    :items="batchOptions"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    :disabled="!form.fgProduct"
                    placeholder="Select Batch"
                  />
                  <div class="mt-1 text-caption text-medium-emphasis">
                    FB = Flexi bag | IS = Isotank | VS = Vessel
                  </div>
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Qty (MT)</label>
                  <VTextField
                    v-model.number="form.qty"
                    type="number"
                    step="any"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>

                <VCol cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">SO No</label>
                  <VTextField
                    v-model="form.soNo"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @input="form.soNo = form.soNo.toUpperCase()"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Upload Shipment Document (PDF)</label>
                  <VFileInput
                    v-model="uploadedFile"
                    accept="application/pdf"
                    prepend-icon="ri-file-pdf-line"
                    variant="outlined"
                    density="compact"
                    class="mt-1"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCardActions class="pa-0">
            <VSpacer />
            <VBtn variant="outlined" color="medium-emphasis" @click="close">Cancel</VBtn>
            <VBtn color="primary" :loading="submitting" prepend-icon="ri-save-line" type="submit">Save</VBtn>
          </VCardActions>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant.js'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useShipmentEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const setupPlantStore = useSetupPlantStore()
const { newTraceNo, traceNoLoading } = storeToRefs(store)
const submitting = ref(false)
const submitError = ref('')
const uploadedFile = ref(null)

const batchOptions = ['FB', 'IS', 'VS']

const selectedPlantForTransfer = ref(null)
const selectedPlantForTransferName = ref('')
const selectedPlantForTransferCode = ref('')
const initLoading = ref(false)

const effectivePlantId = computed(() => {
  if (plantSelectionStore.selectedPlantId !== null) {
    return plantSelectionStore.selectedPlantId
  }
  return selectedPlantForTransfer.value
})

const plantOptions = computed(() => {
  return (setupPlantStore.plants || [])
    .filter(p => p.status == 1)
    .map(p => ({
      value: p.id_plant,
      label: p.description || p.name || `Plant ${p.id_plant}`
    }))
})

const form = reactive({
  entryDate: '',
  fgProduct: null,
  batch_no: '',
  qty: null,
  soNo: '',
  filename: ''
})

watch(() => props.modelValue, async (newVal) => {
    if (newVal) {
      initLoading.value = true
      if (setupPlantStore.plants.length === 0) {
        await setupPlantStore.fetchPlants()
      }
      resetForm()
      if (plantSelectionStore.selectedPlantId !== null) {
        selectedPlantForTransfer.value = plantSelectionStore.selectedPlantId
        selectedPlantForTransferName.value = plantSelectionStore.selectedPlantName || ''
        selectedPlantForTransferCode.value = plantSelectionStore.selectedPlantCode || ''
      }
      await store.fetchActiveFgProducts()
      if (effectivePlantId.value && form.fgProduct) {
        await store.fetchNewTraceNo(effectivePlantId.value, form.fgProduct)
      }
      initLoading.value = false
    }
  })

  // Regenerate trace number when plant, entry date, or fgProduct changes
  watch(() => [effectivePlantId.value, form.entryDate, form.fgProduct], async ([newPlantId, newEntryDate, newFgProduct]) => {
    if (newPlantId && newFgProduct) {
      await store.fetchNewTraceNo(newPlantId, newFgProduct)
    } else {
      store.newTraceNo = ''
    }
  }, { deep: true })

function resetForm() {
  const today = new Date().toLocaleDateString('fr-CA', { timeZone: 'Asia/Jakarta' })
  form.entryDate = today
  form.fgProduct = null
  form.batch_no = ''
  form.qty = null
  form.soNo = ''
  form.filename = ''
  uploadedFile.value = null
  store.resetState()
  if (plantSelectionStore.selectedPlantId !== null) {
    selectedPlantForTransfer.value = plantSelectionStore.selectedPlantId
    selectedPlantForTransferName.value = plantSelectionStore.selectedPlantName || ''
    selectedPlantForTransferCode.value = plantSelectionStore.selectedPlantCode || ''
  } else {
    selectedPlantForTransfer.value = null
    selectedPlantForTransferName.value = ''
    selectedPlantForTransferCode.value = ''
  }
}

function onPlantChange(val) {
  if (val) {
    const plant = setupPlantStore.plants.find(p => p.id_plant === val)
    if (plant) {
      selectedPlantForTransferName.value = plant.description || ''
      selectedPlantForTransferCode.value = plant.code_3 || ''
    }
  } else {
    selectedPlantForTransferName.value = ''
    selectedPlantForTransferCode.value = ''
  }
}

function close() {
  emit('update:modelValue', false)
}

async function onProductChange(val) {
  if (val) {
    form.batch_no = ''
    form.qty = null
    store.resetBatchSelection()

    await store.fetchWipMaterials(val, effectivePlantId.value)
  }
}

async function save() {
  if (plantSelectionStore.selectedPlantId === null && !selectedPlantForTransfer.value) {
    submitError.value = 'Please select a Plant'
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('entryDate', form.entryDate)
    formData.append('fgProduct', form.fgProduct)
    formData.append('batch_no', form.batch_no)
    formData.append('qty', form.qty)
    formData.append('soNo', form.soNo)
    if (effectivePlantId.value) {
      formData.append('id_plant', effectivePlantId.value)
    }

    if (uploadedFile.value) {
      formData.append('file', uploadedFile.value)
    }

    const res = await store.storeEntry(formData)
    if (res.success || res.response == 1 || !res.error) {
      emit('saved')
      close()
    }
  } catch (err) {
    submitError.value = err?.response?.data?.message || 'An error occurred'
  } finally {
    submitting.value = false
  }
}
</script>
