<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @mousedown.self="close">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h5 class="text-base font-bold text-slate-800 tracking-tight flex items-center gap-2">
            <Icon :icon="mode === 'forward' ? 'ri:arrow-right-double-line' : 'ri:arrow-left-double-line'"
                  :class="mode === 'forward' ? 'text-primary' : 'text-info'" />
            {{ mode === 'forward' ? 'Forward' : 'Backward' }} Trace Detail — {{ traceNo }}
          </h5>
          <button class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all" @click="close">
            <Icon icon="ri:close-line" />
          </button>
        </div>

        <div class="px-6 py-6 overflow-y-auto flex-1">
          <div v-if="loading" class="p-8 text-center text-slate-500">
            <Icon icon="ri:loader-4-line" class="animate-spin text-3xl text-primary" />
            <p class="mt-2 text-sm">Loading trace detail...</p>
          </div>
          <div v-else-if="items.length === 0" class="p-8 text-center text-slate-400">
            <Icon icon="ri:inbox-line" class="text-4xl text-slate-300" />
            <p class="mt-2">No trace chain data.</p>
          </div>
          <div v-else class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-left">
                  <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase w-12">No</th>
                  <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500 uppercase w-20">Type</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Prev Batch</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Curr Batch</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Batch Date</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Material</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500 uppercase">In Qty</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">SLoc</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500 uppercase">Out Qty</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase max-w-xs">Supplier</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Matl Doc</th>
                  <th class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="(t, idx) in items" :key="idx" class="hover:bg-slate-50">
                  <td class="px-3 py-2 text-center text-slate-400">{{ idx + 1 }}</td>
                  <td class="px-3 py-2 text-center">
                    <span :class="badgeClass(idx)" class="inline-flex px-2 py-0.5 text-xs rounded-full font-semibold">
                      {{ badgeLabel(idx) }}
                    </span>
                  </td>
                  <td class="px-3 py-2 font-mono text-slate-600">{{ t.prev_trace || '-' }}</td>
                  <td class="px-3 py-2 font-mono font-semibold text-slate-800">{{ t.curr_trace || '-' }}</td>
                  <td class="px-3 py-2 text-slate-600">{{ t.batch_date || '-' }}</td>
                  <td class="px-3 py-2 font-medium text-slate-800 max-w-xs truncate" :title="t.material">{{ t.material }}</td>
                  <td class="px-3 py-2 text-right font-mono text-success font-semibold">{{ t.in_qty || '0.000' }}</td>
                  <td class="px-3 py-2 text-slate-600">{{ t.sloc || '-' }}</td>
                  <td class="px-3 py-2 text-right font-mono text-danger font-semibold">{{ t.out_qty || '0.000' }}</td>
                  <td class="px-3 py-2 max-w-xs truncate text-xs" :title="t.supplier">{{ t.supplier || '-' }}</td>
                  <td class="px-3 py-2 font-mono text-slate-600">{{ t.material_document || '-' }}</td>
                  <td class="px-3 py-2 text-xs text-slate-500">{{ t.created_at || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { Icon } from '@iconify/vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  traceNo:    { type: String, default: '' },
  items:      { type: Array,  default: () => [] },
  loading:    { type: Boolean, default: false },
  mode:       { type: String, default: 'backward', validator: v => ['forward', 'backward'].includes(v) },
})
const emit = defineEmits(['update:modelValue'])
const close = () => emit('update:modelValue', false)

function badgeLabel(idx) {
  if (props.mode === 'forward') return idx === 0 ? 'Initial' : 'Forward'
  return idx === 0 ? 'Target' : 'Source'
}
function badgeClass(idx) {
  if (props.mode === 'forward') return idx === 0 ? 'bg-success-soft text-success-text' : 'bg-info-soft text-info-text'
  return idx === 0 ? 'bg-danger-soft text-danger-text' : 'bg-neutral-soft text-neutral-text'
}
</script>
