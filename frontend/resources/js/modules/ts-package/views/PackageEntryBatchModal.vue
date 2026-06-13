<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="480px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">BATCH & DESTINATION SLOC</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
          <VRow>
            <!-- Batch No -->
            <VCol cols="12">
              <VTextField
                v-model="form.batchNo"
                label="Batch No"
                required
                :rules="[v => !!v || 'Batch No is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                @input="onBatchNoInput"
              />
            </VCol>

            <!-- Destination Sloc -->
            <VCol cols="12">
              <VSelect
                v-model="form.warehouse"
                label="Destination Warehouse"
                :items="store.activeWarehouses"
                item-title="warehouse"
                item-value="id_warehouse"
                required
                :rules="[v => !!v || 'Destination warehouse is required']"
                rounded="md"
                color="primary"
                variant="outlined"
                density="compact"
                :disabled="!form.batchNo"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="close">Cancel</VBtn>
        <VBtn color="primary" prepend-icon="ri-save-line" :disabled="!isValid" :loading="submitting" @click="save">Save</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { usePackageEntryStore } from '../stores/usePackageEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  row: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = usePackageEntryStore()
const formRef = ref(null)
const isValid = ref(false)
const submitting = ref(false)
const submitError = ref('')

const form = reactive({
  id: null,
  batchNo: '',
  warehouse: null
})

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.row) {
    form.id = props.row.id_whx_head
    form.batchNo = props.row.batch_no || ''
    form.warehouse = props.row.id_section || null

    // Pre-populate warehouses
    if (form.batchNo) {
      await store.fetchActiveWarehouses(form.batchNo)
    }
  }
})

function close() {
  emit('update:modelValue', false)
}

async function onBatchNoInput() {
  form.batchNo = form.batchNo.toUpperCase()
  if (form.batchNo.length >= 2) {
    await store.fetchActiveWarehouses(form.batchNo)
    // If current selected warehouse is not in the active warehouses list, reset it
    const exists = store.activeWarehouses.some(w => w.id_warehouse === form.warehouse)
    if (!exists && store.activeWarehouses.length > 0) {
      form.warehouse = store.activeWarehouses[0].id_warehouse
    }
  }
}

async function save() {
  if (!isValid.value) return
  submitting.value = true
  try {
    const res = await store.updateBatch(form.id, form.batchNo, form.warehouse)
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
