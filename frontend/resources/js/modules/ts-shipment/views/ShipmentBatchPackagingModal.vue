<template>
  <VDialog :model-value="modelValue" @update:modelValue="$emit('update:modelValue', $event)" max-width="800px" scrollable>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">BATCH PACKAGING DETAILS</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div v-if="loading" class="d-flex justify-center align-center pa-8">
          <VProgressCircular indeterminate color="primary" />
          <span class="ms-3 text-caption text-medium-emphasis">Loading details...</span>
        </div>

        <template v-else>
          <!-- Grid of Key-Value Details -->
          <VCard variant="flat" border class="mb-5 pa-4">
            <VCardTitle class="px-0 pt-0 text-subtitle-1 font-weight-bold text-primary">
              <VIcon icon="ri-cpu-line" class="me-2" /> OEE Execution Data
            </VCardTitle>
            <VDivider class="mb-4" />
            <VRow class="text-body-2">
              <VCol cols="12" sm="6">
                <div class="mb-2"><strong>Entry Date:</strong> {{ displayDetails.entry_date || '-' }}</div>
                <div class="mb-2"><strong>TF Number:</strong> {{ displayDetails.tf_number || '-' }}</div>
                <div class="mb-2"><strong>Production Order:</strong> {{ displayDetails.production_order || '-' }}</div>
                <div class="mb-2"><strong>Product:</strong> {{ displayDetails.product || '-' }}</div>
                <div class="mb-2"><strong>Process:</strong> {{ displayDetails.process || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="mb-2"><strong>Packing:</strong> {{ displayDetails.packing || '-' }}</div>
                <div class="mb-2"><strong>Pallet:</strong> {{ displayDetails.pallet || '-' }}</div>
                <div class="mb-2"><strong>Customer:</strong> {{ displayDetails.customer || '-' }}</div>
                <div class="mb-2"><strong>Qty (MT):</strong> {{ displayDetails.qty || '-' }} {{ displayDetails.uom || '' }}</div>
                <div class="mb-2"><strong>Approved By:</strong> {{ displayDetails.approved_by || '-' }}</div>
              </VCol>
              <VCol cols="12">
                <div><strong>Long Text:</strong> {{ displayDetails.long_text || '-' }}</div>
              </VCol>
            </VRow>
          </VCard>

          <!-- Label links previews if available -->
          <VCard variant="flat" border class="mb-5 pa-4">
            <VCardTitle class="px-0 pt-0 text-subtitle-1 font-weight-bold text-primary">
              <VIcon icon="ri-price-tag-3-line" class="me-2" /> Labels & Marks
            </VCardTitle>
            <VDivider class="mb-4" />
            <VRow>
              <VCol cols="12" sm="4" v-if="displayDetails.label">
                <div class="text-caption font-weight-semibold">Main Label:</div>
                <a :href="displayDetails.label_link" target="_blank" class="text-body-2 text-decoration-none text-primary">
                  {{ displayDetails.label }}
                </a>
              </VCol>
              <VCol cols="12" sm="4" v-if="displayDetails.splabel">
                <div class="text-caption font-weight-semibold">Special Label:</div>
                <a :href="displayDetails.splabel_link" target="_blank" class="text-body-2 text-decoration-none text-primary">
                  {{ displayDetails.splabel }}
                </a>
              </VCol>
              <VCol cols="12" sm="4" v-if="displayDetails.csmark">
                <div class="text-caption font-weight-semibold">Customer Mark:</div>
                <a :href="displayDetails.csmark_link" target="_blank" class="text-body-2 text-decoration-none text-primary">
                  {{ displayDetails.csmark }}
                </a>
              </VCol>
              <VCol cols="12" v-if="!displayDetails.label && !displayDetails.splabel && !displayDetails.csmark">
                <span class="text-caption text-medium-emphasis">No label information</span>
              </VCol>
            </VRow>
          </VCard>

          <!-- Preparation Record Table -->
          <VCard variant="flat" border class="pa-4">
            <VCardTitle class="px-0 pt-0 text-subtitle-1 font-weight-bold text-primary">
              <VIcon icon="ri-clipboard-line" class="me-2" /> Preparation Records
            </VCardTitle>
            <VDivider class="mb-4" />
            <VTable density="compact">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Type</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Description</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created By</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="store.preparationRecords.length === 0">
                  <td colspan="5" class="text-center text-caption py-4 text-medium-emphasis">
                    No preparation records available.
                  </td>
                </tr>
                <tr v-else v-for="(rec, idx) in store.preparationRecords" :key="rec.id_prepentry">
                  <td class="text-caption">{{ idx + 1 }}</td>
                  <td>
                    <VChip size="x-small" :color="rec.type === 1 ? 'info' : 'success'" variant="tonal">
                      {{ rec.type === 1 ? 'Before Setup' : 'After Setup' }}
                    </VChip>
                  </td>
                  <td class="text-body-2">{{ rec.description }}</td>
                  <td class="text-caption">{{ rec.created_by }}</td>
                  <td class="text-caption">{{ rec.created_at }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </template>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="close">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  batchNo: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const store = useShipmentEntryStore()
const loading = ref(false)

const displayDetails = computed(() => store.selectedBatchDetails || {})

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.batchNo) {
    loading.value = true
    try {
      await Promise.all([
        store.fetchBatchDetails(props.batchNo),
        store.fetchPreparationRecords(props.batchNo)
      ])
    } finally {
      loading.value = false
    }
  }
})

function close() {
  emit('update:modelValue', false)
}
</script>
