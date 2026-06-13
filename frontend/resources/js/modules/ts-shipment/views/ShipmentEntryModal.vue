<template>
  <VDialog :model-value="modelValue" @update:modelValue="$emit('update:modelValue', $event)" max-width="600px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">NEW SHIPMENT ENTRY</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
          <VRow>
            <!-- Entry Mode -->
            <VCol cols="12" sm="4">
              <VTextField
                label="Entry Mode"
                model-value="ADD"
                readonly
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
              />
            </VCol>

            <!-- Entry Date -->
            <VCol cols="12" sm="4">
              <VTextField
                v-model="form.entryDate"
                label="Entry Date"
                type="date"
                required
                :rules="[v => !!v || 'Entry date is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
              />
            </VCol>

            <!-- Trace No -->
            <VCol cols="12" sm="4">
              <VTextField
                v-model="newTraceNo"
                label="Trace No"
                readonly
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                placeholder="Auto Generated"
                :loading="traceNoLoading"
                :append-inner-icon="traceNoLoading ? 'ri-loader-4-line ri-spin' : ''"
              />
            </VCol>

            <!-- Product -->
            <VCol cols="12">
              <VSelect
                v-model="form.fgProduct"
                label="Product"
                :items="store.activeFgProducts"
                item-title="material"
                item-value="id_material"
                required
                :rules="[v => !!v || 'Product is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @update:modelValue="onProductChange"
              />
              <div class="mt-1 text-caption text-primary font-weight-semibold">
                {{ store.wipMaterialLabel || 'Product : N/A' }}
              </div>
            </VCol>

            <!-- Batch No -->
            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.batch_no"
                label="Batch No"
                required
                :rules="[v => !!v || 'Batch No is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @input="form.batch_no = form.batch_no.toUpperCase()"
              />
            </VCol>

            <!-- Qty (MT) -->
            <VCol cols="12" sm="6">
              <VTextField
                v-model.number="form.qty"
                label="Qty (MT)"
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
                variant="outlined"
                density="compact"
              />
            </VCol>

            <!-- SO No -->
            <VCol cols="12">
              <VTextField
                v-model="form.soNo"
                label="SO No"
                required
                :rules="[v => !!v || 'SO No is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @input="form.soNo = form.soNo.toUpperCase()"
              />
            </VCol>

            <!-- File Upload -->
            <VCol cols="12">
              <VFileInput
                v-model="uploadedFile"
                label="Upload Shipment Document (PDF)"
                accept="application/pdf"
                prepend-icon="ri-file-pdf-line"
                variant="outlined"
                density="compact"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" :disabled="submitting" @click="close">Cancel</VBtn>
        <VBtn color="primary" prepend-icon="ri-save-line" :disabled="!isValid" :loading="submitting" @click="save">Save</VBtn>
      </VCardActions>
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
const formRef = ref(null)
const isValid = ref(false)
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
      if (store.plantId) {
        await store.fetchNewTraceNo(store.plantId)
      }
    }
  })

  // Regenerate trace number when plant or entry date changes
  watch(() => [store.plantId, form.entryDate], async ([newPlantId, newEntryDate]) => {
    if (newPlantId) {
      await store.fetchNewTraceNo(newPlantId)
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
  if (!isValid.value) return
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
    if (res.response == 1) {
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
