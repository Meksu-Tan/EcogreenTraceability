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
                  <VTextField
                    v-model="form.batch_no"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @input="form.batch_no = form.batch_no.toUpperCase()"
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
import { ref, reactive, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useShipmentEntryStore()
const { newTraceNo, traceNoLoading } = storeToRefs(store)
const submitting = ref(false)
const submitError = ref('')
const uploadedFile = ref(null)

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
      resetForm()
      await store.fetchActiveFgProducts()
      if (store.plantId && form.fgProduct) {
        await store.fetchNewTraceNo(store.plantId, form.fgProduct)
      }
    }
  })

  // Regenerate trace number when plant, entry date, or fgProduct changes
  watch(() => [store.plantId, form.entryDate, form.fgProduct], async ([newPlantId, newEntryDate, newFgProduct]) => {
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
}

function close() {
  emit('update:modelValue', false)
}

async function onProductChange(val) {
  if (val) {
    // Reset batch selection — user must manually select batch
    form.batch_no = ''
    store.resetBatchSelection()

    // Fetch WIP balance and available batches
    await store.fetchWipMaterials(val)
    await store.fetchActiveBatches(val)
  }
}

async function save() {
  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('entryDate', form.entryDate)
    formData.append('fgProduct', form.fgProduct)
    formData.append('batch_no', form.batch_no)
    formData.append('qty', form.qty)
    formData.append('soNo', form.soNo)

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
