<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="600px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">NEW PACKAGING ENTRY</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
          <VRow>
            <!-- Entry Mode -->
            <VCol cols="12" sm="6">
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
            <VCol cols="12" sm="6">
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

            <!-- Packaging Product -->
            <VCol cols="12">
              <VSelect
                v-model="form.fgProduct"
                label="Packaging Product"
                :items="store.activeFgProducts"
                item-title="material"
                item-value="id_materialpck"
                required
                :rules="[v => !!v || 'Product is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @update:model-value="onProductChange"
              />
              <div class="mt-1 text-caption text-primary font-weight-semibold">
                {{ store.wipMaterialLabel || 'Product : N/A' }}
              </div>
            </VCol>

            <!-- Source Sloc -->
            <VCol cols="12" sm="6">
              <VSelect
                v-model="form.tank"
                label="Source Sloc"
                :items="store.activeTanks"
                item-title="tank"
                item-value="id_sloc"
                required
                :rules="[v => !!v || 'Source Sloc is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                :disabled="!form.fgProduct"
                @update:model-value="onTankChange"
              />
            </VCol>

            <!-- Specific Source Sloc -->
            <VCol cols="12" sm="6">
              <VSelect
                v-model="form.tankNo"
                label="Specific Source Sloc"
                :items="store.specificTanks"
                item-title="tank"
                item-value="id_sloc"
                multiple
                chips
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                :disabled="!form.tank"
              />
            </VCol>

            <!-- PO No -->
            <VCol cols="12">
              <VTextField
                v-model="form.poNo"
                label="PO No"
                required
                :rules="[v => !!v || 'PO No is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @input="form.poNo = form.poNo.toUpperCase()"
              />
            </VCol>

            <!-- Packaging Batch No -->
            <VCol cols="12" sm="6">
              <VTextField
                v-model="form.batchNo"
                label="Packaging Batch No"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
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

            <!-- Destination Sloc -->
            <VCol cols="12" sm="6">
              <VSelect
                v-model="form.warehouse"
                label="Destination Sloc"
                :items="store.activeWarehouses.length > 0 ? store.activeWarehouses : store.allWarehouses"
                item-title="warehouse"
                item-value="id_warehouse"
                required
                :rules="[v => !!v || 'Destination warehouse is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
              />
            </VCol>

            <!-- Trace No -->
            <VCol cols="12" sm="6">
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
import { usePackageEntryStore } from '../stores/usePackageEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = usePackageEntryStore()
const { newTraceNo, traceNoLoading } = storeToRefs(store)
const formRef = ref(null)
const isValid = ref(false)
const submitting = ref(false)
const submitError = ref('')

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

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    resetForm()
    initModal()
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
}

async function initModal() {
  await Promise.all([
    store.fetchActiveFgProducts(),
    store.fetchAllWarehouses()
  ])
  if (store.allWarehouses.length > 0) {
    const firstWh = store.allWarehouses[0]
    form.warehouse = firstWh.id_warehouse
    await store.fetchNewTraceNo(firstWh.id_warehouse, store.plantId)
  }
}

function close() {
  emit('update:modelValue', false)
}

async function onProductChange(val) {
  if (val) {
    form.tank = null
    form.tankNo = []
    await store.fetchWipMaterials(val)

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
  if (val) {
    form.tankNo = []
    // val is already id_sloc (from the function item-value)
    await store.fetchSpecificTanks(val)
    if (form.fgProduct) {
      await store.fetchWipMaterials(form.fgProduct, val)
    }
  }
}

watch(() => form.warehouse, async (newVal) => {
  if (newVal) {
    await store.fetchNewTraceNo(newVal, store.plantId)
  } else {
    store.clearTraceNo()
  }
})

watch(() => [store.plantId, form.entryDate], async ([newPlantId]) => {
  if (form.warehouse && newPlantId) {
    await store.fetchNewTraceNo(form.warehouse, newPlantId)
  }
}, { deep: true })

async function save() {
  if (!isValid.value) return
  submitting.value = true
  try {
    const res = await store.storeEntry(form)
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
