<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <div class="flex items-center gap-2">
        <Icon :icon="icon" class="text-xs w-4 h-4" :class="iconColorClass" />
        <h3 class="text-xs font-bold text-gray-700">{{ title }}</h3>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Feed Quick Stats Badge -->
        <div v-if="hasFeed && feedLatest" class="flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
          <span>LATEST:</span>
          <span class="font-mono">{{ feedLatest.out_qty }} MT</span>
          <span class="text-gray-400 font-mono">{{ feedLatest.entry_date }}</span>
        </div>

        <!-- Rundown Quick Stats Badge -->
        <div v-if="hasRundown && rundownLatest" class="flex items-center gap-1 text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200">
          <span>LATEST:</span>
          <span class="font-mono">{{ rundownLatest.in_qty }} MT</span>
          <span class="text-gray-400 font-mono">{{ rundownLatest.entry_date }}</span>
        </div>

        <!-- Feed Action Buttons -->
        <template v-if="hasFeed">
          <button @click="$emit('entry-feed')" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
            <Icon icon="ri:add-line" class="w-3 h-3" /> Entry Feed
          </button>
          <button @click="$emit('view-feed-log')" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
            <Icon icon="ri:history-line" class="w-3 h-3" /> Log Feed
          </button>
        </template>

        <!-- Rundown Action Buttons -->
        <template v-if="hasRundown">
          <button @click="$emit('entry-rundown')" class="px-3 py-1.5 text-xs font-bold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
            <Icon icon="ri:add-line" class="w-3 h-3" /> Entry Rundown
          </button>
          <button @click="$emit('view-balance')" class="px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
            <Icon icon="ri:swap-line" class="w-3 h-3" /> Balance
          </button>
          <button @click="$emit('view-rundown-log')" class="px-3 py-1.5 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-lg transition-all flex items-center gap-1 cursor-pointer">
            <Icon icon="ri:history-line" class="w-3 h-3" /> Log Rundown
          </button>
        </template>
      </div>
    </div>

    <!-- Body -->
    <div class="p-4">
      <div v-if="hasFeed">
        <slot name="feed-table">
          <div class="text-xs text-gray-400 py-3 text-center bg-gray-50 rounded border border-dashed border-gray-200">No feed data available</div>
        </slot>
      </div>

      <div v-if="hasRundown">
        <slot name="rundown-table">
          <div class="text-xs text-gray-400 py-3 text-center bg-gray-50 rounded border border-dashed border-gray-200">No rundown data available</div>
        </slot>
      </div>

      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  title: { type: String, required: true },
  icon: { type: String, default: 'ri:database-line' },
  color: { type: String, default: 'blue' },
  hasFeed: { type: Boolean, default: false },
  hasRundown: { type: Boolean, default: false },
  feedLatest: { type: Object, default: null },
  rundownLatest: { type: Object, default: null },
  feedId: { type: String, default: '' },
  rundownId: { type: String, default: '' },
  modeSelector: { type: Boolean, default: false },
  modes: { type: Array, default: () => [] },
  selectedMode: { type: String, default: '' },
})

defineEmits(['entry-feed', 'entry-rundown', 'view-balance', 'view-feed-log', 'view-rundown-log'])

const colorMap = {
  blue: 'text-blue-600', green: 'text-green-600', amber: 'text-amber-600', purple: 'text-purple-600',
  emerald: 'text-emerald-600', teal: 'text-teal-600', orange: 'text-orange-600', cyan: 'text-cyan-600',
  rose: 'text-rose-500', pink: 'text-pink-500', slate: 'text-slate-600', indigo: 'text-indigo-600',
}

const iconColorClass = computed(() => colorMap[props.color] || colorMap.blue)
</script>