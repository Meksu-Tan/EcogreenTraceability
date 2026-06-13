<template>
  <VDialog
    :model-value="isOpen"
    max-width="440"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">{{ props.mode === 'ADD' ? 'Add' : 'Edit' }} Material Document</span>
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
            label="Document Number"
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
            >
              {{ props.mode === 'ADD' ? 'Add' : 'Update' }}
            </VBtn>
          </div>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTsBlendingStore } from '../stores'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  idTraceHead: { type: [String, Number], default: null },
  currentNumber: { type: String, default: '' },
  mode: { type: String, default: 'ADD' }
})

const emit = defineEmits(['update:isOpen', 'success'])

const blendingStore = useTsBlendingStore()
const docNumber = ref('')
const errorMsg = ref('')

function closeModal() {
  emit('update:isOpen', false)
}

async function handleSave() {
  errorMsg.value = ''
  try {
    const response = await blendingStore.updateMaterialDoc({
      id: props.idTraceHead,
      number: docNumber.value,
      mode: props.mode
    })
    if (response?.status === 1) {
      emit('success')
    } else {
      errorMsg.value = response?.message || 'Failed to save document'
    }
  } catch (err) {
    errorMsg.value = err.message
  }
}

watch(() => props.isOpen, (val) => {
  if (val) {
    docNumber.value = props.currentNumber || ''
    errorMsg.value = ''
  }
})
</script>
