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
          <!-- Alert if no real OEE packaging record is found in DB -->
          <VAlert
            v-if="!store.selectedBatchDetails"
            type="info"
            variant="tonal"
            density="compact"
            class="mb-4 text-caption"
            icon="ri-information-line"
          >
            No packaging execution record found in OEE database. Displaying template outline.
          </VAlert>

          <!-- Grid of Key-Value Details -->
          <VRow class="mb-4">
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100 bg-surface">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-2">PO & Batch Info</div>
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Production Order (PO):</span>
                    <span class="font-weight-bold font-mono">{{ displayDetails.production_order || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Batch No:</span>
                    <span class="font-weight-bold font-mono text-primary">{{ displayDetails.batch_no || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Production Date:</span>
                    <span class="font-weight-bold">{{ displayDetails.entry_date || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Customer:</span>
                    <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="displayDetails.customer">{{ displayDetails.customer || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between flex-column mt-1">
                    <span class="text-medium-emphasis">PO Long Text:</span>
                    <span class="font-weight-medium bg-grey-lighten-4 pa-1.5 rounded text-caption mt-0.5" style="white-space: pre-wrap; font-size: 11px;">{{ displayDetails.long_text || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100 bg-surface">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-2">Product & Process</div>
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Product:</span>
                    <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="displayDetails.product">{{ displayDetails.product || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Spec:</span>
                    <span class="font-weight-bold">{{ displayDetails.spec || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Plan Qty Prd:</span>
                    <span class="font-weight-bold">{{ displayDetails.qty || '-' }} {{ displayDetails.uom || '' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Lot Size:</span>
                    <span class="font-weight-bold font-mono">{{ displayDetails.lot_qty || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Process:</span>
                    <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="displayDetails.process">{{ displayDetails.process || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Packing Type:</span>
                    <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="displayDetails.packing">{{ displayDetails.packing || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>

            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100 bg-surface">
                <div class="text-subtitle-2 font-weight-bold text-primary mb-2">Storage & Pallet</div>
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Tank No (SLoc):</span>
                    <span class="font-weight-bold font-mono">{{ displayDetails.tf_number || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Pallet Type:</span>
                    <span class="font-weight-bold">{{ displayDetails.pallet || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between flex-column mt-1">
                    <span class="text-medium-emphasis">Tank Product | Volume:</span>
                    <span class="font-weight-bold text-caption mt-0.5">{{ displayDetails.tank_data || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>
          </VRow>

          <!-- Label links previews (template vs printed) -->
          <VCard variant="flat" border class="mb-4 pa-4 bg-surface">
            <div class="text-subtitle-2 font-weight-bold text-primary mb-3">
              <VIcon icon="ri-price-tag-3-line" class="me-2" /> Label and Mark Previews
            </div>
            <VRow>
              <VCol cols="12" md="4" v-if="displayDetails.label">
                <VCard variant="outlined" class="pa-3 h-100 bg-background">
                  <div class="text-caption font-weight-bold mb-2">Main Label: {{ displayDetails.label }}</div>
                  <div class="d-flex gap-2">
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                      <v-img v-if="displayDetails.label_link" :src="`${templateBaseUrl}/${encodeURIComponent(displayDetails.label_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No template</span>
                    </div>
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                      <v-img v-if="displayDetails.p_label_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayDetails.p_label_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No print</span>
                    </div>
                  </div>
                </VCard>
              </VCol>

              <VCol cols="12" md="4" v-if="displayDetails.csmark">
                <VCard variant="outlined" class="pa-3 h-100 bg-background">
                  <div class="text-caption font-weight-bold mb-2">SHIPMARK 1: {{ displayDetails.csmark }}</div>
                  <div class="d-flex gap-2">
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                      <v-img v-if="displayDetails.csmark_link" :src="`${templateBaseUrl}/${encodeURIComponent(displayDetails.csmark_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No template</span>
                    </div>
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                      <v-img v-if="displayDetails.p_csmark_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayDetails.p_csmark_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No print</span>
                    </div>
                  </div>
                </VCard>
              </VCol>

              <VCol cols="12" md="4" v-if="displayDetails.splabel">
                <VCard variant="outlined" class="pa-3 h-100 bg-background">
                  <div class="text-caption font-weight-bold mb-2">SHIPMARK 2: {{ displayDetails.splabel }}</div>
                  <div class="d-flex gap-2">
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                      <v-img v-if="displayDetails.splabel_link" :src="`${templateBaseUrl}/${encodeURIComponent(displayDetails.splabel_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No template</span>
                    </div>
                    <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                      <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                      <v-img v-if="displayDetails.p_splabel_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayDetails.p_splabel_link)}`" style="max-height: 120px;" contain />
                      <span v-else class="text-caption text-medium-emphasis">No print</span>
                    </div>
                  </div>
                </VCard>
              </VCol>
            </VRow>
          </VCard>

          <!-- Execution Logs -->
          <VCard variant="flat" border class="mb-4 pa-4 bg-surface text-caption">
            <VRow class="text-medium-emphasis">
              <VCol cols="6" md="3">Created By: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.created_by || '-' }}</span></VCol>
              <VCol cols="6" md="3">Created At: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.created_at || '-' }}</span></VCol>
              <VCol cols="6" md="3">Approved By: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.approved_by || '-' }}</span></VCol>
              <VCol cols="6" md="3">Approved At: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.approved_at || '-' }}</span></VCol>
              <VCol cols="6" md="3">Started By: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.started_by || '-' }}</span></VCol>
              <VCol cols="6" md="3">Started At: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.started_at || '-' }}</span></VCol>
              <VCol cols="6" md="3">Finished By: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.finished_by || '-' }}</span></VCol>
              <VCol cols="6" md="3">Finished At: <span class="font-weight-medium text-high-emphasis">{{ displayDetails.finished_at || '-' }}</span></VCol>
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
                  <td class="text-caption">{{ rec.created_at ? new Date(rec.created_at).toLocaleString() : '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>

          <!-- SAP Allocations -->
          <VCard variant="outlined" class="mt-4 pa-4">
            <div class="text-subtitle-2 font-weight-bold mb-2">Export / Sales Order Allocation Details (SAP)</div>
            <div class="d-flex flex-wrap gap-2">
              <template v-if="sapAllocs.length > 0">
                <VChip
                  v-for="(alloc, ai) in sapAllocs"
                  :key="ai"
                  color="primary"
                  variant="tonal"
                  size="small"
                  class="font-weight-bold"
                >
                  SO: {{ alloc.VBELN || '-' }}-{{ parseInt(alloc.POSNR || '0', 10) }} | Alloc Qty: {{ alloc.LFIMG || '0' }} {{ alloc.MEINS || '' }}
                </VChip>
              </template>
              <div v-else class="text-caption text-medium-emphasis">No active SAP allocations found.</div>
            </div>
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

const labelBaseUrl = import.meta.env.VITE_LABEL_BASE_URL || ''
const templateBaseUrl = 'https://eoads.ecogreenoleo.com/oee-pph/public/labels'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  batchNo: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const store = useShipmentEntryStore()
const loading = ref(false)

const displayDetails = computed(() => {
  if (store.selectedBatchDetails) {
    return store.selectedBatchDetails
  }
  return {
    batch_no: props.batchNo || '-',
    production_order: '-',
    entry_date: '-',
    customer: '-',
    product: '-',
    spec: '-',
    qty: '-',
    uom: '',
    lot_qty: '-',
    process: '-',
    packing: '-',
    tf_number: '-',
    pallet: '-',
    tank_data: '-',
    label: '',
    csmark: '',
    splabel: '',
    long_text: '-'
  }
})
const sapAllocs = computed(() => {
  const payload = store.sapSoAllocation
  return Array.isArray(payload) ? payload : []
})

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.batchNo) {
    loading.value = true
    try {
      await Promise.all([
        store.fetchBatchDetails(props.batchNo),
        store.fetchPreparationRecords(props.batchNo),
        store.fetchSapSoAllocation(props.batchNo)
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
