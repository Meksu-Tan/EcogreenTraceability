<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="450px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">PO NUMBER MANAGEMENT</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
          <VTextField
            v-model="form.poNo"
            label="PO No"
            required
            :rules="[v => !!v || 'PO No is required']"
            variant="outlined"
            density="compact"
            @input="form.poNo = form.poNo.toUpperCase()"
          />
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
  poNo: ''
})

watch(() => props.modelValue, (newVal) => {
  if (newVal && props.row) {
    form.id = props.row.id_whx_head
    form.poNo = props.row.po_no || ''
  }
})

function close() {
  emit('update:modelValue', false)
}

async function save() {
  if (!isValid.value) return
  submitting.value = true
  try {
    const res = await store.updatePo(form.id, form.poNo)
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
