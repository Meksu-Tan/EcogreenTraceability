<template>
  <VDialog
    :model-value="modelValue"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Packaging Entry</span>
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
          <!-- STEP 1: Plant + Date + Trace No -->
          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
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
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
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
                <VCol cols="12" sm="6" md="3">
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
                <VCol cols="12" sm="6" md="3">
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

          <!-- STEP 2: Packaging fields (after plant + date filled) -->
          <VCard v-if="showStep2" variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" md="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Packaging Product</label>
                  <VSelect
                    v-model="form.fgProduct"
                    :items="store.activeFgProducts"
                    item-title="material"
                    item-value="id_materialpck"
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
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">PO No</label>
                  <VTextField
                    v-model="form.poNo"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @input="form.poNo = form.poNo.toUpperCase()"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Source Sloc</label>
                  <VSelect
                    v-model="form.tank"
                    :items="store.activeTanks"
                    item-title="tank"
                    item-value="id_sloc"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    :disabled="!form.fgProduct"
                    :placeholder="form.fgProduct ? 'Select Sloc' : 'Select product first'"
                    @update:model-value="onTankChange"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Specific Source Sloc</label>
                  <VSelect
                    v-model="form.tankNo"
                    :items="store.specificTanks"
                    item-title="tf_number"
                    item-value="id_sloc"
                    multiple
                    chips
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    :disabled="!form.tank"
                  />
                </VCol>

                <VCol cols="12" sm="6" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Destination Sloc</label>
                  <VSelect
                    v-model="form.warehouse"
                    :items="store.activeWarehouses.length > 0 ? store.activeWarehouses : store.allWarehouses"
                    item-title="warehouse"
                    item-value="id_warehouse"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Packaging Batch No</label>
                  <VSelect
                    v-model="form.batchNo"
                    :items="['FB', 'IS', 'VS']"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    placeholder="Select Batch Type"
                  />
                  <div class="mt-1 text-caption text-medium-emphasis">
                    FB = Flexi bag | IS = Isotank | VS = Vessel
                  </div>
                </VCol>

                <VCol cols="12" sm="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Qty (MT)</label>
                  <VTextField
                    v-model.number="form.qty"
                    type="number"
                    step="any"
                    required
                    :rules="[
                      v => !!v || 'Qty is required',
                      v => v > 0 || 'Qty must be greater than 0',
                      v => v <= store.wipBalance || `Qty cannot exceed balance (${store.wipBalance} MT)`
                    ]"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCardActions v-if="showStep2" class="pa-0">
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
import { usePackageEntryStore } from '../stores/usePackageEntryStore'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant.js'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = usePackageEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const setupPlantStore = useSetupPlantStore()
const { newTraceNo, traceNoLoading } = storeToRefs(store)
const submitting = ref(false)
const submitError = ref('')
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

const showStep2 = computed(() => {
  return !!effectivePlantId.value && !!form.entryDate
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
  tank: null,
  tankNo: [],
  poNo: '',
  batchNo: '',
  qty: null,
  warehouse: null
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
    await initModal()
    initLoading.value = false
  }
})

function resetForm() {
  const today = new Date().toLocaleDateString('fr-CA', { timeZone: 'Asia/Jakarta' })
  form.entryDate = today
  form.fgProduct = null
  form.tank = null
  form.tankNo = []
  form.poNo = ''
  form.batchNo = ''
  form.qty = null
  form.warehouse = null
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

async function initModal() {
  await Promise.all([
    store.fetchActiveFgProducts(),
    store.fetchAllWarehouses()
  ])
  if (store.allWarehouses.length > 0) {
    const firstWh = store.allWarehouses[0]
    form.warehouse = firstWh.id_warehouse
  }
}

function close() {
  emit('update:modelValue', false)
}

async function onProductChange(val) {
  if (val) {
    form.tank = null
    form.tankNo = []
    await store.fetchWipMaterials(val, null, effectivePlantId.value)

    if (store.activeTanks.length > 0) {
      form.tank = store.activeTanks[0].id_sloc
      await store.fetchSpecificTanks(form.tank, form.fgProduct)
    }

    const selectedProduct = store.activeFgProducts.find(p => p.id_materialpck === val)
    form.batchNo = ''
    if (selectedProduct?.batch_prefix) {
      await store.fetchActiveWarehouses(selectedProduct.batch_prefix)
      if (store.activeWarehouses.length > 0) {
        form.warehouse = store.activeWarehouses[0].id_warehouse
      }
    }
  }
}

async function onTankChange(val) {
  form.tankNo = []
  if (val && form.fgProduct) {
    await store.fetchSpecificTanks(val, form.fgProduct)
  }
}

watch(() => form.fgProduct, async (newVal) => {
  if (!newVal) {
    store.clearTraceNo()
  }
})

watch(() => [effectivePlantId.value, form.warehouse, form.batchNo], async ([newPlantId, newWarehouse, newBatchNo]) => {
  if (form.fgProduct && newPlantId && (newWarehouse || newBatchNo)) {
    await store.fetchNewTraceNo(form.fgProduct, newPlantId, newWarehouse, newBatchNo)
  } else {
    store.clearTraceNo()
  }
}, { deep: true })

watch(effectivePlantId, async (newPlantId, oldPlantId) => {
  if (newPlantId && newPlantId !== oldPlantId && form.fgProduct) {
    form.tank = null
    form.tankNo = []
    await store.fetchWipMaterials(form.fgProduct, null, newPlantId)
    if (store.activeTanks.length > 0) {
      form.tank = store.activeTanks[0].id_sloc
      await store.fetchSpecificTanks(form.tank, form.fgProduct)
    }
  }
})

async function save() {
  submitting.value = true
  try {
    const res = await store.storeEntry(form)
    if (res?.status == 1) {
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
