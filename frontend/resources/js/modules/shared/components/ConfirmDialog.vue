<template>
  <VDialog
    v-model="modelValue"
    max-width="450"
    persistent
  >
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <div class="d-flex align-center gap-2">
          <VIcon :icon="icon" :color="color" size="24" />
          <span class="text-h6 font-weight-bold">{{ title }}</span>
        </div>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="onCancel" />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 pt-4 text-body-1">
        {{ message }}
      </VCardText>

      <VDivider />

      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn
          variant="outlined"
          color="medium-emphasis"
          @click="onCancel"
        >
          {{ cancelText }}
        </VBtn>
        <VBtn
          :color="color"
          variant="flat"
          @click="onConfirm"
          :loading="loading"
        >
          {{ confirmText }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Confirmation'
  },
  message: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    default: 'ri-error-warning-line'
  },
  color: {
    type: String,
    default: 'error'
  },
  confirmText: {
    type: String,
    default: 'Yes, Continue'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const modelValue = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

function onConfirm() {
  emit('confirm')
}

function onCancel() {
  modelValue.value = false
  emit('cancel')
}
</script>
