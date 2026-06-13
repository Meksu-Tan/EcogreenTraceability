<template>
  <VDialog
    :model-value="modelValue"
    :max-width="maxWidth"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard rounded="lg">
      <!-- Header -->
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">{{ title }}</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="$emit('update:modelValue', false)"
        />
      </VCardTitle>

      <VDivider />

      <!-- Body -->
      <VCardText class="pa-5">
        <slot />
      </VCardText>

      <VDivider />

      <!-- Footer -->
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn
          variant="outlined"
          color="medium-emphasis"
          @click="$emit('update:modelValue', false)"
        >
          Cancel
        </VBtn>
        <VBtn
          color="primary"
          :loading="loading"
          prepend-icon="ri-save-line"
          @click="$emit('submit')"
        >
          {{ submitLabel }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
defineProps({
  modelValue:  { type: Boolean, default: false },
  title:       { type: String, default: 'Modal' },
  submitLabel: { type: String, default: 'Save' },
  loading:     { type: Boolean, default: false },
  maxWidth:    { type: [String, Number], default: 520 },
})
defineEmits(['update:modelValue', 'submit'])
</script>