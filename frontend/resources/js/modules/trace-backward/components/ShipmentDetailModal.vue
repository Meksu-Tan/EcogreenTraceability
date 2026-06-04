<template>
  <Teleport to="body">
  <div v-if="modelValue" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @mousedown.self="close">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h5 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
          <Icon icon="ri:ship-line" class="text-info" />
          Shipment Overview — SO: {{ soNo }}
        </h5>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all" @click="close">
          <Icon icon="ri:close-line" />
        </button>
      </div>
      <div class="p-5 overflow-auto flex-1">
        <div v-if="loading" class="p-8 text-center text-gray-500">
          <svg class="animate-spin h-8 w-8 text-info mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <p class="mt-2 text-sm text-slate-500">Retrieving SAP shipment data...</p>
        </div>
        <div v-else-if="!data" class="p-8 text-center text-slate-400">Failed to fetch shipment details or empty response.</div>
        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
              <h3 class="text-sm font-bold text-slate-800 mb-3 border-b pb-1">Customer & PO Information</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Customer Code:</span><span class="font-semibold text-slate-800">{{ data.CUSTOMER_CODE || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Customer Name:</span><span class="font-semibold text-slate-800 text-right">{{ data.CUSTOMER_NAME || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">PO Number:</span><span class="font-semibold text-slate-800 font-mono">{{ data.PO_NUM || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Pro Invoice:</span><span class="font-semibold text-slate-800 font-mono">{{ data.PRO_INVOICE || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Inco Term PTEO:</span><span class="font-semibold text-slate-800">{{ data.INCO_PTEO || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Inco Term EOS:</span><span class="font-semibold text-slate-800">{{ data.INCO_EOS || '-' }}</span></div>
              </div>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
              <h3 class="text-sm font-bold text-slate-800 mb-3 border-b pb-1">Logistic & Quality Information</h3>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">ZBatch (SAP):</span><span class="font-semibold text-slate-800 font-mono">{{ data.ZBATCH || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Net Weight (kg):</span><span class="font-semibold text-slate-800">{{ data.NET_WEIGHT || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Depart Date:</span><span class="font-semibold text-slate-800">{{ data.DATE_DEPART || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Port Discharge:</span><span class="font-semibold text-slate-800 text-right uppercase">{{ data.PORT_DISCHARGE || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Vessel:</span><span class="font-semibold text-slate-800 text-right uppercase">{{ data.VESSEL || '-' }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Ship To Location:</span><span class="font-semibold text-slate-800 text-right uppercase">{{ data.SHIP_TO_LOC || '-' }}</span></div>
              </div>
            </div>
          </div>
          <div class="mt-4 space-y-4">
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
              <h3 class="text-sm font-bold text-slate-800 mb-2">Container Numbers</h3>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="c in (data.CONTAINER_NUMBER ? data.CONTAINER_NUMBER.split(';').map(x=>x.trim()).filter(x=>x) : [])" :key="c" class="px-2.5 py-1 text-xs font-semibold bg-info-soft text-info-text rounded-md border border-info/30">{{ c }}</span>
                <span v-if="!data.CONTAINER_NUMBER" class="text-sm text-slate-400">No container information</span>
              </div>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
              <h3 class="text-sm font-bold text-slate-800 mb-2">Shipment Lot Numbers</h3>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="l in (data.SHIP_LOT ? data.SHIP_LOT.split(';').map(x=>x.trim()).filter(x=>x) : [])" :key="l" class="px-2.5 py-1 text-xs font-semibold bg-success-soft text-success-text rounded-md border border-success/30">{{ l }}</span>
                <span v-if="!data.SHIP_LOT" class="text-sm text-slate-400">No lot information</span>
              </div>
            </div>
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-100">
              <h3 class="text-sm font-bold text-slate-800 mb-2">Batch Allocations</h3>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="ba in (data.BATCH_ALLOC ? data.BATCH_ALLOC.split(';').map(x=>x.trim()).filter(x=>x) : [])" :key="ba" class="px-2.5 py-1 text-xs font-semibold bg-neutral-soft text-neutral-text rounded-md border border-neutral-soft/50">{{ ba }}</span>
                <span v-if="!data.BATCH_ALLOC" class="text-sm text-slate-400">No batch allocation information</span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { Icon } from '@iconify/vue'
defineProps({ modelValue: { type: Boolean, default: false }, soNo: String, data: { type: Object, default: null }, loading: Boolean })
const emit = defineEmits(['update:modelValue'])
const close = () => emit('update:modelValue', false)
</script>