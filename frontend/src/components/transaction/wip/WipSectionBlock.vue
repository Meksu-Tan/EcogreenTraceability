<!-- section block -->
<template>
  <div class="overflow-hidden rounded-xl bg-black p-4 shadow-lg space-y-3">
    <h2 class="text-center text-lg font-bold text-white">START OF {{ section.title }}</h2>

    <div v-if="section.modeOptions" class="flex flex-wrap items-center gap-3">
      <select
        :value="sectionMode"
        class="max-w-xs rounded border border-slate-600 bg-slate-800 px-3 py-2 text-sm font-semibold text-white"
        @change="$emit('update:sectionMode', $event.target.value)"
      >
        <option v-for="opt in section.modeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
      <p v-if="section.id === 'section106' || section.id === 'section112'" class="text-xs text-white/90">
        WARNING: DO NOT ENTRY SEVERAL MODES AT THE SAME TIME!
      </p>
    </div>

    <div class="space-y-3">
      <WipPanel
        v-for="panel in feedPanels"
        :key="panel.id"
        :ref="(el) => setPanelRef(panel.id, el)"
        :panel="panel"
        :plant-id="plantId"
        :show-plant-column="showPlantColumn"
        :refresh-key="refreshKey"
        v-on="panelListeners"
      />
    </div>

    <div v-if="hasProcessDivider" class="flex flex-col items-center gap-2 py-2 text-white">
      <i class="fas fa-arrow-down text-2xl"></i>
      <span class="text-lg font-bold">PROCESS OF {{ section.title }}</span>
      <i class="fas fa-arrow-down text-2xl"></i>
    </div>

    <div v-if="rundownPanels.length" class="space-y-3 rounded-lg p-3" style="background-color: #324031">
      <WipPanel
        v-for="panel in rundownPanels"
        :key="panel.id"
        :ref="(el) => setPanelRef(panel.id, el)"
        :panel="panel"
        :plant-id="plantId"
        :show-plant-column="showPlantColumn"
        :refresh-key="refreshKey"
        v-on="panelListeners"
      />
    </div>

    <h2 class="text-center text-lg font-bold text-white pt-2">END OF {{ section.title }}</h2>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import WipPanel from './WipPanel.vue'
import {
  sectionFeedPanels,
  sectionRundownPanels,
  sectionHasProcessDivider,
} from './wipConfig'

const props = defineProps({
  section: { type: Object, required: true },
  sectionMode: { type: String, default: null },
  plantId: { type: [String, Number], default: null },
  showPlantColumn: { type: Boolean, default: false },
  refreshKey: { type: Number, default: 0 },
  panelListeners: { type: Object, default: () => ({}) },
  setPanelRef: { type: Function, required: true },
})

defineEmits(['update:sectionMode'])

const feedPanels = computed(() => sectionFeedPanels(props.section, props.sectionMode))
const rundownPanels = computed(() => sectionRundownPanels(props.section, props.sectionMode))
const hasProcessDivider = computed(() => sectionHasProcessDivider(props.section, props.sectionMode))
</script>
