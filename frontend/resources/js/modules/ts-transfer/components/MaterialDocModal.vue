<template>
  <VDialog
    :model-value="isOpen"
    max-width="440"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">{{ mode === 'ADD' ? 'Add' : 'Edit' }} Material Document</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="closeModal"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5">
        <form @submit.prevent="handleSave" class="d-flex flex-column gap-4">
          <VTextField
            v-model="docNumber"
            label="Material Document No (SAP)"
            class="text-uppercase"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
            autofocus
          />

          <VAlert
            v-if="errorMsg"
            type="error"
            variant="tonal"
            density="comfortable"
          >
            {{ errorMsg }}
          </VAlert>

          <div class="d-flex justify-end gap-2 mt-2">
            <VBtn
              variant="outlined"
              color="medium-emphasis"
              @click="closeModal"
            >
              Cancel
            </VBtn>
            <VBtn
              type="submit"
              color="primary"
              :loading="loading"
            >
              {{ mode === 'ADD' ? 'Add' : 'Update' }}
            </VBtn>
          </div>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTsTransferStore } from '../stores'

const props = defineProps({
  isOpen: Boolean,
  idTraceHead: { type: Number, default: null },
  currentNumber: { type: String, default: '' },
  mode: { type: String, default: 'ADD' }
})

const emit = defineEmits(['update:isOpen', 'success'])

const transferStore = useTsTransferStore()
const docNumber = ref('')
const errorMsg = ref('')
const loading = ref(false)

async function handleSave() {
  if (!docNumber.value || !props.idTraceHead) return
  loading.value = true
  errorMsg.value = ''

  try {
    const response = await transferStore.submitMatlDocNumber(props.mode, props.idTraceHead, docNumber.value)
    if (response?.status === 1) {
      emit('success')
    } else {
      errorMsg.value = 'Failed to save material document'
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message || e.message || 'Error'
  }
  loading.value = false
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (open) => {
  if (!open && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (open) {
    docNumber.value = props.mode === 'UPDATE' ? props.currentNumber : ''
    errorMsg.value = ''
  }
})
</script>
