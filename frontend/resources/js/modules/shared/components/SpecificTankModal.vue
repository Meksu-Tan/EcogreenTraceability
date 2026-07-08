<template>
  <VDialog :model-value="modelValue" max-width="500" @update:model-value="$emit('update:modelValue', $event)">
    <VCard>
      <VCardTitle class="bg-neutral-50 text-uppercase text-body-2 font-weight-bold py-3">
        Specific SLOC / Tanks Selected
      </VCardTitle>
      <VDivider />
      <VCardText class="py-4">
        <div class="mb-4">
          <strong>Storage Location:</strong> {{ mainSloc || 'N/A' }}
        </div>
        <div>
          <strong>Tank Farm Number:</strong>
          <div class="d-flex flex-wrap ga-1 mt-2">
            <VChip
              v-for="(tank, idx) in tankList"
              :key="idx"
              color="primary"
              variant="flat"
              size="small"
            >
              {{ tank }}
            </VChip>
            <span v-if="tankList.length === 0" class="text-caption text-disabled">No specific tank</span>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardActions>
        <VSpacer />
        <VBtn color="secondary" variant="text" @click="close">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  mainSloc: { type: String, default: '' },
  tankNumbers: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const tankList = computed(() => {
  if (!props.tankNumbers) return []
  return props.tankNumbers.split(',').map(t => t.trim()).filter(Boolean)
})

function close() {
  emit('update:modelValue', false)
}
</script>
