<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="800px" scrollable>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">SAP SHIPMENT & SO ALLOCATION</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div v-if="loading" class="d-flex justify-center align-center pa-8">
          <VProgressCircular indeterminate color="primary" />
          <span class="ms-3 text-caption text-medium-emphasis">Querying SAP General Module...</span>
        </div>

        <div v-else>
          <!-- SAP Shipment Info Card (ZFM_EUDR_SHIPMENT) -->
          <VCard variant="flat" border class="mb-5 pa-4">
            <VCardTitle class="px-0 pt-0 text-subtitle-1 font-weight-bold text-primary">
              <VIcon icon="ri-truck-line" class="me-2" /> SAP Shipment Detail (ZFM_EUDR_SHIPMENT)
            </VCardTitle>
            <VDivider class="mb-4" />
            <VRow class="text-body-2">
              <VCol cols="12" sm="6">
                <div class="mb-2"><strong>SO Number:</strong> {{ soNo || '-' }}</div>
                <div class="mb-2"><strong>Batch:</strong> {{ batchNo || '-' }}</div>
                <div class="mb-2"><strong>Customer Code:</strong> {{ sapShipmentData?.CUSTOMER_CODE || '-' }}</div>
                <div class="mb-2"><strong>Customer Name:</strong> {{ sapShipmentData?.CUSTOMER_NAME || '-' }}</div>
                <div class="mb-2"><strong>PO Number:</strong> {{ sapShipmentData?.PO_NUM || '-' }}</div>
                <div class="mb-2"><strong>Proforma Invoice:</strong> {{ sapShipmentData?.PRO_INVOICE || '-' }}</div>
              </VCol>
              <VCol cols="12" sm="6">
                <div class="mb-2"><strong>Net Weight (MT):</strong> {{ sapShipmentData?.NET_WEIGHT || '0' }}</div>
                <div class="mb-2"><strong>Container:</strong> {{ sapShipmentData?.CONTAINER_NUMBER || '-' }}</div>
                <div class="mb-2"><strong>Vessel:</strong> {{ sapShipmentData?.VESSEL || '-' }}</div>
                <div class="mb-2"><strong>Port Discharge:</strong> {{ sapShipmentData?.PORT_DISCHARGE || '-' }}</div>
                <div class="mb-2"><strong>Date Depart:</strong> {{ sapShipmentData?.DATE_DEPART || '-' }}</div>
                <div class="mb-2"><strong>Ship to Loc:</strong> {{ sapShipmentData?.SHIP_TO_LOC || '-' }}</div>
              </VCol>
            </VRow>
          </VCard>

          <!-- SAP SO Allocation List Card -->
          <VCard variant="flat" border class="pa-4">
            <VCardTitle class="px-0 pt-0 text-subtitle-1 font-weight-bold text-primary">
              <VIcon icon="ri-organization-chart" class="me-2" /> SO Allocation Breakdown (SAP ZFM_AD001)
            </VCardTitle>
            <VDivider class="mb-4" />
            <VTable density="compact">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sales Doc</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Item</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Batch</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Allocated Qty</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">UoM</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Plant</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="allocationList.length === 0">
                  <td colspan="6" class="text-center text-caption py-4 text-medium-emphasis">
                    No SO allocation list returned by SAP.
                  </td>
                </tr>
                <tr v-else v-for="(alloc, idx) in allocationList" :key="idx">
                  <td class="text-body-2 font-weight-semibold">{{ alloc.VBELN || '-' }}</td>
                  <td class="text-caption">{{ alloc.POSNR || '-' }}</td>
                  <td>
                    <VChip size="x-small" variant="flat" color="neutral-100">
                      {{ alloc.CHARG || alloc.charg || '-' }}
                    </VChip>
                  </td>
                  <td class="text-body-2 font-weight-semibold text-right">{{ alloc.LFIMG || '0' }}</td>
                  <td class="text-caption">{{ alloc.MEINS || '-' }}</td>
                  <td class="text-caption">{{ alloc.WERKS || alloc.werks || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </div>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="close">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useShipmentEntryStore } from '../stores/useShipmentEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  row: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue'])

const store = useShipmentEntryStore()
const loading = ref(false)

const soNo = computed(() => props.row?.so_no || '')
const batchNo = computed(() => props.row?.batch_no || '')
const batchSap = computed(() => props.row?.batch_sap || '')

function parseSoNumber(rawSo) {
  if (!rawSo || !rawSo.includes('-')) {
    return { soNo: rawSo, soItem: '' }
  }
  const parts = rawSo.split(/[-,]/)
  const soNum = parts[0]
  let soItem = parts[1] || ''
  if (soItem) {
    if (!soItem.endsWith('0')) {
      soItem = soItem.padStart(5, '0') + '0'
    } else {
      soItem = soItem.padStart(5, '0')
    }
  }
  return { soNo: soNum, soItem }
}

// SAP payload data extraction
const sapShipmentData = computed(() => {
  if (store.sapShipment) {
    if (Array.isArray(store.sapShipment)) {
      return store.sapShipment[0] || null
    }
    return store.sapShipment
  }
  return null
})

const allocationList = computed(() => {
  if (store.sapSoAllocation) {
    if (Array.isArray(store.sapSoAllocation)) {
      return store.sapSoAllocation
    }
    return [store.sapSoAllocation]
  }
  return []
})

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.row) {
    loading.value = true
    try {
      const parsed = parseSoNumber(soNo.value)
      await Promise.allSettled([
        store.fetchSapShipment(batchNo.value, parsed.soNo, parsed.soItem),
        store.fetchSapSoAllocation(batchSap.value || batchNo.value)
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
