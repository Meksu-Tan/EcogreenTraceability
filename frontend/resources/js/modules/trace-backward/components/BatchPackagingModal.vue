<template>
  <Teleport to="body">
  <div v-if="modelValue" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @mousedown.self="close">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl max-h-[95vh] flex flex-col overflow-hidden border border-gray-100">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h5 class="text-lg font-bold text-slate-800 tracking-tight flex items-center gap-2">
          <Icon icon="ri:archive-line" class="text-warning" />
          Batch Packaging Detail — Batch: {{ batchNo }}
        </h5>
        <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all" @click="close">
          <Icon icon="ri:close-line" />
        </button>
      </div>
      <div class="p-5 overflow-auto flex-1 space-y-6">
        <div v-if="loading" class="p-8 text-center text-gray-500">
          <svg class="animate-spin h-8 w-8 text-warning mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <p class="mt-2 text-sm text-slate-500">Retrieving packaging execution and SAP data...</p>
        </div>
        <div v-else-if="!data" class="p-8 text-center text-slate-400">No packaging data found for this batch.</div>
        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2 text-sm">
              <div><span class="text-slate-500">Product Date:</span> <span class="font-semibold text-slate-800 ml-1">{{ data.entry_date || '-' }}</span></div>
              <div><span class="text-slate-500">Production Order:</span> <span class="font-semibold text-slate-800 ml-1 font-mono">{{ data.production_order || '-' }}</span></div>
              <div><span class="text-slate-500">Product Desc:</span> <span class="font-semibold text-slate-800 ml-1">{{ data.product || '-' }}</span></div>
              <div><span class="text-slate-500">Customer:</span> <span class="font-semibold text-slate-800 ml-1 text-xs">{{ data.customer || '-' }}</span></div>
            </div>
            <div class="space-y-2 text-sm">
              <div><span class="text-slate-500">Spec:</span> <span class="font-semibold text-slate-800 ml-1">{{ data.spec || '-' }}</span></div>
              <div><span class="text-slate-500">Batch Qty:</span> <span class="font-semibold text-slate-800 ml-1">{{ data.qty || '-' }} {{ data.uom || 'KG' }}</span></div>
              <div><span class="text-slate-500">Process:</span> <span class="font-semibold text-slate-800 ml-1 text-xs">{{ data.process || '-' }}</span></div>
              <div><span class="text-slate-500">Packing Material:</span> <span class="font-semibold text-slate-800 ml-1 text-xs">{{ data.packing || '-' }}</span></div>
            </div>
            <div class="space-y-2 text-sm">
              <div><span class="text-slate-500">Tank Source:</span> <span class="font-semibold text-slate-800 ml-1 font-mono">{{ data.tf_number || '-' }}</span></div>
              <div><span class="text-slate-500">Pallet:</span> <span class="font-semibold text-slate-800 ml-1">{{ data.pallet || '-' }}</span></div>
              <div><span class="text-slate-500">Catalog Label:</span> <span class="font-semibold text-slate-800 ml-1 text-xs">{{ data.label || '-' }}</span></div>
              <div><span class="text-slate-500">Special Label:</span> <span class="font-semibold text-slate-800 ml-1 text-xs">{{ data.splabel || '-' }}</span></div>
            </div>
          </div>
          <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-1.5"><Icon icon="ri:todo-line" class="text-slate-500" />Preparation & Execution Record Log</h3>
            <div v-if="prepRecords.length === 0" class="text-xs text-slate-400 p-2">No log records available.</div>
            <div v-else class="overflow-x-auto border border-slate-200 rounded-md">
              <table class="w-full text-xs text-left bg-white">
                <thead><tr class="bg-slate-100 border-b">
                  <th class="px-2 py-1.5 font-semibold text-slate-600 w-8 text-center">No</th>
                  <th class="px-2 py-1.5 font-semibold text-slate-600 w-16">Type</th>
                  <th class="px-2 py-1.5 font-semibold text-slate-600">Description</th>
                  <th class="px-2 py-1.5 font-semibold text-slate-600 w-16 text-center">Status</th>
                  <th class="px-2 py-1.5 font-semibold text-slate-600 w-24">Created By</th>
                  <th class="px-2 py-1.5 font-semibold text-slate-600 w-32">Timestamp</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(rec, ri) in prepRecords" :key="ri" class="hover:bg-slate-50">
                    <td class="px-2 py-1.5 text-center text-slate-400">{{ ri + 1 }}</td>
                    <td class="px-2 py-1.5 font-semibold uppercase text-neutral-text">{{ rec.type || '-' }}</td>
                    <td class="px-2 py-1.5">{{ rec.description || '-' }}</td>
                    <td class="px-2 py-1.5 text-center"><span :class="rec.status == 1 ? 'bg-success-soft text-success-text' : 'bg-danger-soft text-danger-text'" class="px-1.5 py-0.5 rounded text-[10px] font-bold">{{ rec.status == 1 ? 'ACTIVE' : 'INACTIVE' }}</span></td>
                    <td class="px-2 py-1.5 text-slate-600">{{ rec.created_by || '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-500 font-mono text-[10px]">{{ rec.created_at || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-2">Export / Sales Order Allocation Details (SAP)</h3>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="(alloc, ai) in sapAllocs" :key="ai" class="px-2.5 py-1 text-xs font-semibold bg-primary/10 text-primary rounded-md border border-primary/20">
                SO: {{ alloc.VBELN || '-' }}-{{ parseInt(alloc.POSNR || '0', 10) }} | Alloc Qty: {{ alloc.LFIMG || '0' }} {{ alloc.MEINS || '' }}
              </span>
              <span v-if="sapAllocs.length === 0" class="text-xs text-slate-400">No active SAP allocations found.</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex flex-col items-center">
              <h4 class="text-xs font-bold text-slate-700 mb-2">Printed Packaging Label</h4>
              <div class="w-full aspect-video border bg-white rounded flex items-center justify-center overflow-hidden">
                <img v-if="data.p_label_link" :src="`${labelBaseUrl}/${encodeURIComponent(data.p_label_link)}`" class="max-h-full max-w-full object-contain" alt="Label" />
                <span v-else class="text-xs text-slate-400">No label image printed</span>
              </div>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex flex-col items-center">
              <h4 class="text-xs font-bold text-slate-700 mb-2">Special Label</h4>
              <div class="w-full aspect-video border bg-white rounded flex items-center justify-center overflow-hidden">
                <img v-if="data.p_splabel_link" :src="`${labelBaseUrl}/${encodeURIComponent(data.p_splabel_link)}`" class="max-h-full max-w-full object-contain" alt="Special Label" />
                <span v-else class="text-xs text-slate-400">No special label image</span>
              </div>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 flex flex-col items-center">
              <h4 class="text-xs font-bold text-slate-700 mb-2">Customer Mark</h4>
              <div class="w-full aspect-video border bg-white rounded flex items-center justify-center overflow-hidden">
                <img v-if="data.p_csmark_link" :src="`${labelBaseUrl}/${encodeURIComponent(data.p_csmark_link)}`" class="max-h-full max-w-full object-contain" alt="Customer Mark" />
                <span v-else class="text-xs text-slate-400">No customer mark image</span>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-slate-500 pt-2 border-t border-slate-100">
            <div>Approved By: <span class="font-medium text-slate-800">{{ data.approved_by || '-' }}</span></div>
            <div>Approved At: <span class="font-medium text-slate-800">{{ data.approved_at || '-' }}</span></div>
            <div>Started By: <span class="font-medium text-slate-800">{{ data.started_by || '-' }}</span></div>
            <div>Started At: <span class="font-medium text-slate-800">{{ data.started_at || '-' }}</span></div>
          </div>
        </template>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { Icon } from '@iconify/vue'

const labelBaseUrl = import.meta.env.VITE_LABEL_BASE_URL

defineProps({ modelValue: { type: Boolean, default: false }, batchNo: String, data: { type: Object, default: null }, prepRecords: { type: Array, default: () => [] }, sapAllocs: { type: Array, default: () => [] }, loading: Boolean })
const emit = defineEmits(['update:modelValue'])
const close = () => emit('update:modelValue', false)
</script>