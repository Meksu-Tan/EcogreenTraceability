<template>
  <VDialog
    :model-value="modelValue"
    max-width="1200"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center py-3 bg-surface" border="b">
        <div class="d-flex align-center gap-2">
          <VIcon
            :icon="mode === 'forward' ? 'ri-arrow-right-double-line' : 'ri-arrow-left-double-line'"
            :color="mode === 'forward' ? 'primary' : 'info'"
            size="24"
          />
          <span class="text-h6 font-weight-bold">
            {{ mode === 'forward' ? 'Forward' : 'Backward' }} Trace Detail — {{ traceNo }}
          </span>
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
          <div class="text-caption">Loading trace detail...</div>
        </div>

        <div v-else-if="error" class="pa-8 text-center text-error">
          <VIcon icon="ri-error-warning-line" size="48" class="mb-2" />
          <div class="text-body-2">{{ error }}</div>
        </div>

        <div v-else-if="items.length === 0" class="pa-8 text-center text-medium-emphasis">
          <VIcon icon="ri-inbox-line" size="48" class="mb-2 text-disabled" />
          <div class="text-body-2">No trace chain data.</div>
        </div>

        <div v-else>
          <VCard variant="outlined" class="mb-4">
            <div class="overflow-x-auto">
              <VTable density="comfortable" class="text-caption">
                <thead>
                  <tr class="bg-surface-variant">
                    <th class="text-center font-weight-bold" style="width: 48px;">No</th>
                    <th class="text-center font-weight-bold" style="width: 80px;">Type</th>
                    <th class="text-left font-weight-bold">Prev Batch</th>
                    <th class="text-left font-weight-bold">Curr Batch</th>
                    <th class="text-left font-weight-bold">Batch Date</th>
                    <th class="text-left font-weight-bold" style="max-width: 200px;">Material</th>
                    <th class="text-right font-weight-bold">In Qty</th>
                    <th class="text-left font-weight-bold">SLoc</th>
                    <th class="text-right font-weight-bold">Out Qty</th>
                    <th class="text-left font-weight-bold" style="max-width: 200px;">Supplier</th>
                    <th class="text-left font-weight-bold">Matl Doc</th>
                    <th class="text-left font-weight-bold">Created</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(t, idx) in paginatedItems" :key="idx" class="hover-row">
                    <td class="text-center text-medium-emphasis">{{ getGlobalIndex(idx) }}</td>
                    <td class="text-center">
                      <VChip
                        size="x-small"
                        :color="badgeColor(getGlobalIndex(idx) - 1)"
                        variant="tonal"
                        class="font-weight-bold"
                      >
                        {{ badgeLabel(getGlobalIndex(idx) - 1) }}
                      </VChip>
                    </td>
                    <td class="font-mono text-medium-emphasis">{{ t.prev_trace || '-' }}</td>
                    <td class="font-mono font-weight-bold">{{ t.curr_trace || '-' }}</td>
                    <td class="text-medium-emphasis">{{ t.batch_date || '-' }}</td>
                    <td class="font-weight-medium" style="max-width: 200px;">
                      <div class="text-truncate" :title="t.material">{{ t.material }}</div>
                    </td>
                    <td class="text-right font-mono font-weight-bold text-success">{{ t.in_qty || '0.000' }}</td>
                    <td class="text-medium-emphasis">{{ t.sloc || '-' }}</td>
                    <td class="text-right font-mono font-weight-bold text-error">{{ t.out_qty || '0.000' }}</td>
                    <td style="max-width: 280px; white-space: normal;" class="text-caption text-medium-emphasis">
                      <div v-if="t.supplier">
                        <div v-for="(item, idx) in formatDetailSupplier(t.supplier)" :key="idx" class="mb-1 pa-1 rounded border bg-grey-lighten-4" style="font-size: 11px;">
                          <div class="font-weight-bold text-primary">{{ item.supplier }}</div>
                          <div class="d-flex justify-space-between align-center mt-0.5">
                            <span>Batch: <span class="font-weight-bold font-mono">{{ item.batch || '-' }}</span></span>
                            <span>Qty: <span class="font-weight-bold text-success">{{ item.qty || '-' }}</span></span>
                          </div>
                        </div>
                      </div>
                      <span v-else>-</span>
                    </td>
                    <td class="font-mono text-medium-emphasis">{{ t.material_document || '-' }}</td>
                    <td class="text-medium-emphasis" style="font-size: 11px;">{{ t.created_at || '-' }}</td>
                  </tr>
                </tbody>
              </VTable>
            </div>
          </VCard>

          <div v-if="items.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer rounded border gap-2 mt-2">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (page - 1) * itemsPerPage + 1 }} - {{ Math.min(page * itemsPerPage, items.length) }} of {{ items.length }} records
            </span>
            <VPagination
              v-if="totalPages > 1"
              v-model="page"
              :length="totalPages"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </template>
  
  <script setup>
  import { ref, computed, watch } from 'vue'
  import { formatDetailSupplier } from '@/utils/formatSupplier'
  
  const props = defineProps({
    modelValue: { type: Boolean, default: false },
    traceNo:    { type: String, default: '' },
    items:      { type: Array,  default: () => [] },
    loading:    { type: Boolean, default: false },
    error:      { type: String, default: '' },
    mode:       { type: String, default: 'backward', validator: v => ['forward', 'backward'].includes(v) },
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  const close = () => {
    emit('update:modelValue', false)
  }
  
  // Pagination Logic
  const page = ref(1)
  const itemsPerPage = ref(5)

// Reset page when modal opens
watch(() => props.modelValue, (val) => {
  if (val) page.value = 1
})

const totalPages = computed(() => Math.ceil(props.items.length / itemsPerPage.value))

const paginatedItems = computed(() => {
  const start = (page.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return props.items.slice(start, end)
})

function getGlobalIndex(localIndex) {
  return (page.value - 1) * itemsPerPage.value + localIndex + 1
}

function badgeLabel(idx) {
  if (props.mode === 'forward') return idx === 0 ? 'Initial' : 'Forward'
  return idx === 0 ? 'Target' : 'Source'
}

function badgeColor(idx) {
  if (props.mode === 'forward') return idx === 0 ? 'success' : 'info'
  return idx === 0 ? 'error' : 'default'
}

</script>

<style scoped>
.hover-row:hover {
  background-color: rgba(var(--v-theme-on-surface), 0.04);
}
</style>
