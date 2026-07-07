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
          <!-- Alert if no real OEE packaging record is found in DB -->
          <VAlert
            v-if="dataList.length === 0"
            type="info"
            variant="tonal"
            density="compact"
            class="mb-4 text-caption"
            icon="ri-information-line"
          >
            No packaging execution record found in OEE database. Displaying template outline.
          </VAlert>

          <!-- Batch Info Section — one set per real production batch (dispatch types FB/IS/VS expand to multiple) -->
          <div v-for="(batch, bi) in displayList" :key="bi" class="mb-4">
            <div v-if="isMultiBatch" class="text-caption font-weight-bold text-medium-emphasis mb-1">
              Real Batch: {{ batch.batch_no || '-' }}
            </div>
            <VRow>
              <VCol cols="12" md="4">
                <VCard variant="outlined" class="pa-3 h-100 bg-surface">
                  <div class="text-subtitle-2 font-weight-bold text-primary mb-2">PO & Batch Info</div>
                  <div class="d-flex flex-column gap-2 text-body-2">
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Production Order (PO):</span>
                      <span class="font-weight-bold font-mono">{{ batch.production_order || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Batch No:</span>
                      <span class="font-weight-bold font-mono text-primary">{{ batch.batch_no || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Production Date:</span>
                      <span class="font-weight-bold">{{ batch.entry_date || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Customer:</span>
                      <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="batch.customer">{{ batch.customer || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between flex-column mt-1">
                      <span class="text-medium-emphasis">PO Long Text:</span>
                      <span class="font-weight-medium bg-grey-lighten-4 pa-1.5 rounded text-caption mt-0.5" style="white-space: pre-wrap; font-size: 11px;">{{ batch.long_text || '-' }}</span>
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
                      <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="batch.product">{{ batch.product || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Spec:</span>
                      <span class="font-weight-bold">{{ batch.spec || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Plan Qty Prd:</span>
                      <span class="font-weight-bold">{{ batch.qty || '-' }} {{ batch.uom || 'KG' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Lot Size:</span>
                      <span class="font-weight-bold font-mono">{{ batch.lot_qty || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Process:</span>
                      <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="batch.process">{{ batch.process || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Packing Type:</span>
                      <span class="font-weight-bold text-truncate" style="max-width: 180px;" :title="batch.packing">{{ batch.packing || '-' }}</span>
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
                      <span class="font-weight-bold font-mono">{{ batch.tf_number || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between">
                      <span class="text-medium-emphasis">Pallet Type:</span>
                      <span class="font-weight-bold">{{ batch.pallet || '-' }}</span>
                    </div>
                    <div class="d-flex justify-space-between flex-column mt-1">
                      <span class="text-medium-emphasis">Tank Product | Volume:</span>
                      <span class="font-weight-bold text-caption mt-0.5">{{ batch.tank_data || '-' }}</span>
                    </div>
                  </div>
                </VCard>
              </VCol>
            </VRow>

            <!-- Labels & Images Preview (Side-by-side template and printed views) -->
            <VCard variant="flat" border class="mt-4 pa-4 bg-surface">
              <div class="text-subtitle-2 font-weight-bold text-primary mb-3">
                <VIcon icon="ri-price-tag-3-line" class="me-2" /> Label and Mark Previews
              </div>
              <VRow>
                <VCol cols="12" md="4" v-if="batch.label">
                  <VCard variant="outlined" class="pa-3 h-100 bg-background">
                    <div class="text-caption font-weight-bold mb-2">Main Label: {{ batch.label }}</div>
                    <div class="d-flex gap-2">
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                        <v-img v-if="batch.label_link" :src="`${templateBaseUrl}/${encodeURIComponent(batch.label_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No template</span>
                      </div>
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                        <v-img v-if="batch.p_label_link" :src="`${labelBaseUrl}/${encodeURIComponent(batch.p_label_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No print</span>
                      </div>
                    </div>
                  </VCard>
                </VCol>

                <VCol cols="12" md="4" v-if="batch.csmark">
                  <VCard variant="outlined" class="pa-3 h-100 bg-background">
                    <div class="text-caption font-weight-bold mb-2">SHIPMARK 1: {{ batch.csmark }}</div>
                    <div class="d-flex gap-2">
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                        <v-img v-if="batch.csmark_link" :src="`${templateBaseUrl}/${encodeURIComponent(batch.csmark_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No template</span>
                      </div>
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                        <v-img v-if="batch.p_csmark_link" :src="`${labelBaseUrl}/${encodeURIComponent(batch.p_csmark_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No print</span>
                      </div>
                    </div>
                  </VCard>
                </VCol>

                <VCol cols="12" md="4" v-if="batch.splabel">
                  <VCard variant="outlined" class="pa-3 h-100 bg-background">
                    <div class="text-caption font-weight-bold mb-2">SHIPMARK 2: {{ batch.splabel }}</div>
                    <div class="d-flex gap-2">
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Template</div>
                        <v-img v-if="batch.splabel_link" :src="`${templateBaseUrl}/${encodeURIComponent(batch.splabel_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No template</span>
                      </div>
                      <div class="flex-grow-1 border rounded pa-2 bg-surface text-center">
                        <div class="text-caption text-medium-emphasis mb-1 font-weight-semibold">Printed</div>
                        <v-img v-if="batch.p_splabel_link" :src="`${labelBaseUrl}/${encodeURIComponent(batch.p_splabel_link)}`" style="max-height: 120px;" contain />
                        <span v-else class="text-caption text-medium-emphasis">No print</span>
                      </div>
                    </div>
                  </VCard>
                </VCol>
              </VRow>
            </VCard>

            <!-- Execution Logs -->
            <VCard variant="flat" border class="mt-4 pa-4 bg-surface text-caption">
              <VRow class="text-medium-emphasis">
                <VCol cols="6" md="3">Created By: <span class="font-weight-medium text-high-emphasis">{{ batch.created_by || '-' }}</span></VCol>
                <VCol cols="6" md="3">Created At: <span class="font-weight-medium text-high-emphasis">{{ batch.created_at || '-' }}</span></VCol>
                <VCol cols="6" md="3">Approved By: <span class="font-weight-medium text-high-emphasis">{{ batch.approved_by || '-' }}</span></VCol>
                <VCol cols="6" md="3">Approved At: <span class="font-weight-medium text-high-emphasis">{{ batch.approved_at || '-' }}</span></VCol>
                <VCol cols="6" md="3">Started By: <span class="font-weight-medium text-high-emphasis">{{ batch.started_by || '-' }}</span></VCol>
                <VCol cols="6" md="3">Started At: <span class="font-weight-medium text-high-emphasis">{{ batch.started_at || '-' }}</span></VCol>
                <VCol cols="6" md="3">Finished By: <span class="font-weight-medium text-high-emphasis">{{ batch.finished_by || '-' }}</span></VCol>
                <VCol cols="6" md="3">Finished At: <span class="font-weight-medium text-high-emphasis">{{ batch.finished_at || '-' }}</span></VCol>
              </VRow>
            </VCard>
            <VDivider v-if="bi < dataList.length - 1" class="my-4" />
          </div>

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
  dataList: { type: Array, default: () => [] },
  prepRecords: { type: Array, default: () => [] },
  sapAllocs: { type: Array, default: () => [] },
  loading: Boolean
})

const emit = defineEmits(['update:modelValue'])

const displayList = computed(() => {
  if (props.dataList && props.dataList.length > 0) {
    return props.dataList
  }
  return [{
    batch_no: props.batchNo || '-',
    production_order: '-',
    entry_date: '-',
    customer: '-',
    product: '-',
    spec: '-',
    qty: '-',
    uom: 'KG',
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
  }]
})

const isMultiBatch = computed(() => props.dataList.length > 1)

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