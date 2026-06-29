<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="450px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">SO NUMBER MANAGEMENT</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
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
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  row: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = useShipmentEntryStore()
const formRef = ref(null)
const isValid = ref(false)
const submitting = ref(false)
const submitError = ref('')

const form = reactive({
  id: null,
  soNo: ''
})

watch(() => props.modelValue, (newVal) => {
  if (newVal && props.row) {
    form.id = props.row.id_ship_head
    form.soNo = props.row.so_no || ''
  }
})

function close() {
  emit('update:modelValue', false)
}

async function save() {
  if (!isValid.value) return
  submitting.value = true
  try {
    const res = await store.updateSo(form.id, form.soNo)
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
