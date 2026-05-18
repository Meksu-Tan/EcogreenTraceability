<template>
  <div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">WIP Transaction</h1>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Lokasi:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
              <i class="fas fa-industry mr-1.5 opacity-70"></i>
              {{ plantSelectionStore.selectedPlantName }}
            </span>
          </div>
        </div>
        <div class="hidden h-10 w-px bg-gray-200 md:block"></div>
        <PlantSelector @change="onPlantChange" />
      </div>
      <span class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white">
        QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
      </span>
    </div>

    <div class="max-w-md rounded-lg bg-black p-2 shadow">
      <select
        v-model="selectedSection"
        class="w-full rounded border-0 bg-black px-3 py-2 text-sm font-semibold text-white focus:ring-2 focus:ring-green-500"
      >
        <option v-for="opt in wipSectionFilterOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>
    </div>

    <p v-if="!plantSelectionStore.selectedPlantId" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
      <i class="fas fa-info-circle mr-2"></i>
      Pilih plant spesifik via <strong>Switch Plant</strong> untuk entry feed/rundown dan generate trace no batch.
    </p>

    <WipSectionBlock
      v-for="section in visibleSections"
      :key="section.id"
      :section="section"
      :section-mode="sectionModes[section.id]"
      :plant-id="plantSelectionStore.selectedPlantId"
      :show-plant-column="isAllPlants"
      :refresh-key="refreshKey"
      :set-panel-ref="setPanelRef"
      :panel-listeners="panelListeners"
      @update:section-mode="(v) => (sectionModes[section.id] = v)"
    />

    <WipFeedModal :open="feedModalOpen" :panel="activePanel" :plant-id="effectivePlantId" @close="feedModalOpen = false" @saved="refreshAll" />
    <WipRundownModal :open="rundownModalOpen" :panel="activePanel" :plant-id="effectivePlantId" @close="rundownModalOpen = false" @saved="refreshAll" />
    <WipBalanceModal :open="balanceModalOpen" :panel="activePanel" :plant-id="plantSelectionStore.selectedPlantId" @close="balanceModalOpen = false" />
    <WipLogModal
      :open="logModalOpen"
      :panel="activePanel"
      :plant-id="plantSelectionStore.selectedPlantId"
      @close="logModalOpen = false"
      @changed="refreshAll"
      @matl-doc="(row) => openMatlDocModal(row, activePanel)"
    />
    <WipMatlDocModal :open="matlDocOpen" :row="matlDocRow" :plant-id="effectivePlantId" @close="matlDocOpen = false" @saved="refreshAll" />
    <WipSubTankModal :open="subTankOpen" :row="subTankRow" :plant-id="effectivePlantId" @close="subTankOpen = false" @saved="refreshPanelReload" />

    <PlantSelectionModal ref="plantSelectionModal" @selected="onPlantChange" />
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import PlantSelectionModal from '@/components/shared/PlantSelectionModal.vue'
import WipSectionBlock from '@/components/transaction/wip/WipSectionBlock.vue'
import WipFeedModal from '@/components/transaction/wip/WipFeedModal.vue'
import WipRundownModal from '@/components/transaction/wip/WipRundownModal.vue'
import WipBalanceModal from '@/components/transaction/wip/WipBalanceModal.vue'
import WipLogModal from '@/components/transaction/wip/WipLogModal.vue'
import WipMatlDocModal from '@/components/transaction/wip/WipMatlDocModal.vue'
import WipSubTankModal from '@/components/transaction/wip/WipSubTankModal.vue'
import { wipSections, wipSectionFilterOptions } from '@/components/transaction/wip/wipConfig'
import { useWipPlant } from '@/composables/useWipPlant'

const { plantSelectionStore, requirePlantId, canTransact } = useWipPlant()

const selectedSection = ref('allSection')
const refreshKey = ref(0)
const sectionModes = reactive({})
const panelRefs = ref({})

const feedModalOpen = ref(false)
const rundownModalOpen = ref(false)
const balanceModalOpen = ref(false)
const logModalOpen = ref(false)
const matlDocOpen = ref(false)
const subTankOpen = ref(false)
const activePanel = ref(null)
const matlDocRow = ref(null)
const subTankRow = ref(null)
const activePanelIdForReload = ref(null)
const plantSelectionModal = ref(null)

wipSections.forEach((section) => {
  if (section.defaultMode) sectionModes[section.id] = section.defaultMode
})

const visibleSections = computed(() => {
  if (selectedSection.value === 'allSection') return wipSections
  return wipSections.filter((s) => s.id === selectedSection.value)
})

const isAllPlants = computed(() => !plantSelectionStore.selectedPlantId)
const effectivePlantId = computed(() => plantSelectionStore.selectedPlantId)

const panelListeners = {
  entry: (panel) => openEntryModal(panel),
  balance: (panel) => openBalanceModal(panel),
  log: (panel) => openLogModal(panel),
  'matl-doc': ({ row, panel }) => openMatlDocModal(row, panel),
  'edit-sloc': ({ row, panel }) => openSubTankModal(row, panel),
}

function setPanelRef(id, el) {
  if (el) panelRefs.value[id] = el
}

function openEntryModal(panel) {
  if (!requirePlantId()) return
  activePanel.value = panel
  if (panel.kind === 'feed') feedModalOpen.value = true
  else rundownModalOpen.value = true
}

function openBalanceModal(panel) {
  activePanel.value = panel
  balanceModalOpen.value = true
}

function openLogModal(panel) {
  activePanel.value = panel
  logModalOpen.value = true
}

function openMatlDocModal(row, panel) {
  if (!requirePlantId()) return
  matlDocRow.value = { ...row, panel }
  matlDocOpen.value = true
}

function openSubTankModal(row, panel) {
  if (!requirePlantId()) return
  subTankRow.value = { ...row, panel }
  activePanelIdForReload.value = panel?.id
  subTankOpen.value = true
}

function refreshAll() {
  refreshKey.value += 1
  Object.values(panelRefs.value).forEach((panel) => panel?.reload?.())
}

function refreshPanelReload() {
  const id = activePanelIdForReload.value
  if (id && panelRefs.value[id]) panelRefs.value[id].reload()
  else refreshAll()
}

function onPlantChange() {
  refreshAll()
}

onMounted(() => {
  if (!plantSelectionStore.hasSelectedPlant) {
    plantSelectionModal.value?.open()
  } else {
    refreshAll()
  }
})
</script>
