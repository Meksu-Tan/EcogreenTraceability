<template>
  <VDialog
    :model-value="modelValue"
    max-width="1100"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center py-3 bg-surface" border="b">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-ship-line" color="primary" size="24" />
          <span class="text-h6 font-weight-bold">Shipment Overview — SO: {{ soNo }}</span>
        </div>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="close"
        />
      </VCardTitle>

      <VCardText class="pa-4 bg-background">
        <div v-if="loading" class="pa-8 text-center text-medium-emphasis">
          <VProgressCircular indeterminate color="primary" class="mb-2" />
          <div class="text-caption">Retrieving SAP shipment data...</div>
        </div>

        <template v-else>
          <VRow>
            <VCol cols="12" md="6">
              <VCard variant="outlined" class="pa-4 h-100 bg-surface">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-2">Customer & PO Information</div>
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Customer Code:</span>
                    <span class="font-weight-bold">{{ data?.CUSTOMER_CODE || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Customer Name:</span>
                    <span class="font-weight-bold text-right">{{ data?.CUSTOMER_NAME || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">PO Number:</span>
                    <span class="font-weight-bold font-mono">{{ data?.PO_NUM || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Pro Invoice:</span>
                    <span class="font-weight-bold font-mono">{{ data?.PRO_INVOICE || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Inco Term PTEO:</span>
                    <span class="font-weight-bold">{{ data?.INCO_PTEO || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Inco Term EOS:</span>
                    <span class="font-weight-bold">{{ data?.INCO_EOS || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>

            <VCol cols="12" md="6">
              <VCard variant="outlined" class="pa-4 h-100 bg-surface">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-2">Logistic & Quality Information</div>
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">ZBatch (SAP):</span>
                    <span class="font-weight-bold font-mono">{{ data?.ZBATCH || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Net Weight (kg):</span>
                    <span class="font-weight-bold">{{ data?.NET_WEIGHT || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Depart Date:</span>
                    <span class="font-weight-bold">{{ data?.DATE_DEPART || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Port Discharge:</span>
                    <span class="font-weight-bold text-right text-uppercase">{{ data?.PORT_DISCHARGE || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Vessel:</span>
                    <span class="font-weight-bold text-right text-uppercase">{{ data?.VESSEL || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Ship To Location:</span>
                    <span class="font-weight-bold text-right text-uppercase">{{ data?.SHIP_TO_LOC || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>
          </VRow>

          <div class="d-flex flex-column gap-4 mt-4">
            <VCard variant="outlined" class="mt-4 pa-4">
              <div class="text-subtitle-2 font-weight-bold mb-2">Container Numbers</div>
              <div class="d-flex flex-wrap gap-2">
                <template v-if="data?.CONTAINER_NUMBER">
                  <VChip
                    v-for="c in data.CONTAINER_NUMBER.split(';').map(x=>x.trim()).filter(x=>x)"
                    :key="c"
                    color="primary"
                    variant="tonal"
                    size="small"
                    class="font-weight-bold"
                  >
                    {{ c }}
                  </VChip>
                </template>
                <div v-else class="text-caption text-medium-emphasis">No container information</div>
              </div>
            </VCard>

            <VCard variant="outlined" class="mt-4 pa-4">
              <div class="text-subtitle-2 font-weight-bold mb-2">Shipment Lot Numbers</div>
              <div class="d-flex flex-wrap gap-2">
                <template v-if="data?.SHIP_LOT">
                  <VChip
                    v-for="l in data.SHIP_LOT.split(';').map(x=>x.trim()).filter(x=>x)"
                    :key="l"
                    color="success"
                    variant="tonal"
                    size="small"
                    class="font-weight-bold"
                  >
                    {{ l }}
                  </VChip>
                </template>
                <div v-else class="text-caption text-medium-emphasis">No lot information</div>
              </div>
            </VCard>

            <VCard variant="outlined" class="mt-4 pa-4">
              <div class="text-subtitle-2 font-weight-bold mb-2">Batch Allocations</div>
              <div class="d-flex flex-wrap gap-2">
                <template v-if="data?.BATCH_ALLOC">
                  <VChip
                    v-for="ba in data.BATCH_ALLOC.split(';').map(x=>x.trim()).filter(x=>x)"
                    :key="ba"
                    color="default"
                    variant="tonal"
                    size="small"
                    class="font-weight-bold"
                  >
                    {{ ba }}
                  </VChip>
                </template>
                <div v-else class="text-caption text-medium-emphasis">No batch allocation information</div>
              </div>
            </VCard>
          </div>
        </template>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  soNo: { type: String, default: '' },
  data: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const close = () => {
  emit('update:modelValue', false)
}
</script>
