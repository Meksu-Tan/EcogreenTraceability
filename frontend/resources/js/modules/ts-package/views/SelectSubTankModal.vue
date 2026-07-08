<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="480px">
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">SPECIFIC SLOC / TANKS SELECTED</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div class="mb-4">
          <strong>Storage Location:</strong> {{ mainSlocLabel || 'N/A' }}
        </div>
        <div>
          <strong>Tank Farm Number:</strong>
          <div v-if="loading" class="d-flex align-center justify-center py-4">
            <VProgressCircular indeterminate color="primary" size="24" />
            <span class="ms-2 text-caption text-medium-emphasis">Loading...</span>
          </div>
          <div v-else class="d-flex flex-wrap ga-1 mt-2">
            <VChip
              v-for="tank in specificTanks"
              :key="tank.id_sloc_tail"
              color="primary"
              variant="flat"
              size="small"
              class="mr-1 mb-1"
            >
              {{ tank.tankName || tank.tf_number || tank.id_sloc_tail }}
            </VChip>
            <span v-if="specificTanks.length === 0" class="text-caption text-disabled">No specific sloc available</span>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end">
        <VBtn color="secondary" variant="text" @click="close">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePackageEntryStore } from '../stores/usePackageEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  row: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue'])

const store = usePackageEntryStore()
const loading = ref(false)
const mainSlocLabel = ref('')
const specificTanks = ref([])

  watch(() => props.modelValue, async (newVal) => {
    if (newVal && props.row) {
      mainSlocLabel.value = props.row.sloc || ''
      const parentSloc = props.row.raw_sloc || props.row.id_sloc
      if (parentSloc) {
        let slocId = null
        if (Array.isArray(parentSloc)) {
          slocId = parentSloc[0]
        } else if (typeof parentSloc === 'string') {
          try {
            const parsed = JSON.parse(parentSloc)
            slocId = Array.isArray(parsed) ? (parsed[0] ?? null) : parentSloc
          } catch {
            slocId = parentSloc
          }
        } else {
          slocId = parentSloc
        }
        loading.value = true
        try {
          await store.fetchSpecificTanks(slocId)
          specificTanks.value = store.specificTanks || []
        } catch {
          specificTanks.value = []
        } finally {
          loading.value = false
        }
      } else {
        specificTanks.value = []
      }
    }
  })

function close() {
  emit('update:modelValue', false)
}
</script>
