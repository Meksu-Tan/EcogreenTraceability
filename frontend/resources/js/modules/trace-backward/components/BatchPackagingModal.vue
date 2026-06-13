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
          <VIcon icon="ri-archive-line" color="primary" size="24" />
          <span class="text-h6 font-weight-bold">Batch Packaging Detail — Batch: {{ batchNo }}</span>
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
          <div class="text-caption">Retrieving packaging execution and SAP data...</div>
        </div>

        <template v-else>
          <!-- Batch Info Section -->
          <VRow>
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100">
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Product Date:</span>
                    <span class="font-weight-bold">{{ displayData.entry_date || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Production Order:</span>
                    <span class="font-weight-bold font-mono">{{ displayData.production_order || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Product Desc:</span>
                    <span class="font-weight-bold">{{ displayData.product || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Customer:</span>
                    <span class="font-weight-bold" style="font-size: 11px;">{{ displayData.customer || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100">
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Spec:</span>
                    <span class="font-weight-bold">{{ displayData.spec || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Batch Qty:</span>
                    <span class="font-weight-bold">{{ displayData.qty || '-' }} {{ displayData.uom || 'KG' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Process:</span>
                    <span class="font-weight-bold" style="font-size: 11px;">{{ displayData.process || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Packing Material:</span>
                    <span class="font-weight-bold" style="font-size: 11px;">{{ displayData.packing || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-3 h-100">
                <div class="d-flex flex-column gap-2 text-body-2">
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Tank Source:</span>
                    <span class="font-weight-bold font-mono">{{ displayData.tf_number || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Pallet:</span>
                    <span class="font-weight-bold">{{ displayData.pallet || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Catalog Label:</span>
                    <span class="font-weight-bold" style="font-size: 11px;">{{ displayData.label || '-' }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span class="text-medium-emphasis">Special Label:</span>
                    <span class="font-weight-bold" style="font-size: 11px;">{{ displayData.splabel || '-' }}</span>
                  </div>
                </div>
              </VCard>
            </VCol>
          </VRow>

          <!-- Preparation Records -->
          <VCard variant="outlined" class="mt-4 pa-4">
            <div class="d-flex align-center gap-2 mb-3">
              <VIcon icon="ri-todo-line" color="medium-emphasis" size="small" />
              <span class="text-subtitle-2 font-weight-bold">Preparation & Execution Record Log</span>
            </div>

            <div v-if="prepRecords.length === 0" class="text-caption text-medium-emphasis px-2">
              No log records available.
            </div>
            <div v-else>
              <VCard variant="flat" border>
                <div class="overflow-x-auto">
                  <VTable density="compact" class="text-caption bg-surface">
                    <thead>
                      <tr class="bg-background">
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:100px">Type</th>
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Description</th>
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:100px">Status</th>
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:150px">Created By</th>
                        <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:150px">Timestamp</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(rec, ri) in paginatedPrepRecords" :key="ri">
                        <td class="text-center text-medium-emphasis">{{ getGlobalIndex(ri) }}</td>
                        <td class="font-weight-bold text-uppercase">{{ rec.type || '-' }}</td>
                        <td>{{ rec.description || '-' }}</td>
                        <td class="text-center">
                          <VChip
                            size="x-small"
                            :color="rec.status == 1 ? 'success' : 'error'"
                            variant="tonal"
                            class="font-weight-bold"
                          >
                            {{ rec.status == 1 ? 'ACTIVE' : 'INACTIVE' }}
                          </VChip>
                        </td>
                        <td class="text-medium-emphasis">{{ rec.created_by || '-' }}</td>
                        <td class="text-medium-emphasis font-mono" style="font-size: 10px;">{{ rec.created_at || '-' }}</td>
                      </tr>
                    </tbody>
                  </VTable>
                </div>
              </VCard>

              <div v-if="prepRecords.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer rounded border gap-2 mt-2">
                <span class="text-caption text-medium-emphasis">
                  Showing {{ (page - 1) * itemsPerPage + 1 }} - {{ Math.min(page * itemsPerPage, prepRecords.length) }} of {{ prepRecords.length }} records
                </span>
                <VPagination
                  v-if="totalPages > 1"
                  v-model="page"
                  :length="totalPages"
                  :total-visible="5"
                  density="compact"
                  size="small"
                  show-first-last-page
                />
              </div>
            </div>
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

          <!-- Labels & Images -->
          <VRow class="mt-2">
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-4 d-flex flex-column align-center">
                <div class="text-caption font-weight-bold mb-2">Printed Packaging Label</div>
                <div class="w-100 border bg-surface rounded d-flex align-center justify-center overflow-hidden" style="aspect-ratio: 16/9;">
                  <v-img v-if="displayData.p_label_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayData.p_label_link)}`" contain />
                  <span v-else class="text-caption text-medium-emphasis">No label image printed</span>
                </div>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-4 d-flex flex-column align-center">
                <div class="text-caption font-weight-bold mb-2">Special Label</div>
                <div class="w-100 border bg-surface rounded d-flex align-center justify-center overflow-hidden" style="aspect-ratio: 16/9;">
                  <v-img v-if="displayData.p_splabel_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayData.p_splabel_link)}`" contain />
                  <span v-else class="text-caption text-medium-emphasis">No special label image</span>
                </div>
              </VCard>
            </VCol>
            <VCol cols="12" md="4">
              <VCard variant="outlined" class="pa-4 d-flex flex-column align-center">
                <div class="text-caption font-weight-bold mb-2">Customer Mark</div>
                <div class="w-100 border bg-surface rounded d-flex align-center justify-center overflow-hidden" style="aspect-ratio: 16/9;">
                  <v-img v-if="displayData.p_csmark_link" :src="`${labelBaseUrl}/${encodeURIComponent(displayData.p_csmark_link)}`" contain />
                  <span v-else class="text-caption text-medium-emphasis">No customer mark image</span>
                </div>
              </VCard>
            </VCol>
          </VRow>

          <VDivider class="my-4" />
          
          <VRow class="text-caption text-medium-emphasis">
            <VCol cols="6" md="3">Approved By: <span class="font-weight-medium">{{ displayData.approved_by || '-' }}</span></VCol>
            <VCol cols="6" md="3">Approved At: <span class="font-weight-medium">{{ displayData.approved_at || '-' }}</span></VCol>
            <VCol cols="6" md="3">Started By: <span class="font-weight-medium">{{ displayData.started_by || '-' }}</span></VCol>
            <VCol cols="6" md="3">Started At: <span class="font-weight-medium">{{ displayData.started_at || '-' }}</span></VCol>
          </VRow>
        </template>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const labelBaseUrl = import.meta.env.VITE_LABEL_BASE_URL || ''

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  batchNo: String,
  data: { type: Object, default: null },
  prepRecords: { type: Array, default: () => [] },
  sapAllocs: { type: Array, default: () => [] },
  loading: Boolean
})

const emit = defineEmits(['update:modelValue'])

const displayData = computed(() => props.data || {})

const close = () => {
  emit('update:modelValue', false)
}

// Pagination Logic for Prep Records
const page = ref(1)
const itemsPerPage = ref(5)

watch(() => props.modelValue, (val) => {
  if (val) page.value = 1
})

const totalPages = computed(() => Math.ceil(props.prepRecords.length / itemsPerPage.value))

const paginatedPrepRecords = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return props.prepRecords.slice(start, end)
})

function getGlobalIndex(localIndex) {
  return (page.value - 1) * itemsPerPage.value + localIndex + 1
}
</script>