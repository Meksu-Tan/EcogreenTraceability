<template>
  <div class="d-flex flex-column ga-5">
    <div class="d-flex align-center justify-space-between flex-wrap ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">WIP Process Setup</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">Maintain WIP transaction sections, feed steps, rundown steps, and mode rules.</p>
      </div>
      <div class="d-flex ga-2">
        <VSelect
          v-model="localPlantId"
          :items="plantOptions"
          item-title="description"
          item-value="id_plant"
          density="compact"
          variant="outlined"
          hide-details
          class="plant-select"
          @update:model-value="onPlantChange"
        />
        <VBtn prepend-icon="ri-refresh-line" variant="tonal" :loading="store.loading" @click="reloadSections">Refresh</VBtn>
        <VBtn prepend-icon="ri-add-line" color="primary" @click="openSection()">Section</VBtn>
      </div>
    </div>

    <VAlert v-if="store.error" type="error" variant="tonal">{{ store.error }}</VAlert>

    <VCard v-if="!localPlantId && !store.loading" variant="outlined" class="pa-12 text-center">
      <VIcon icon="ri-factory-line" size="48" color="neutral" class="mb-4" />
      <h3 class="text-h6 font-weight-medium text-medium-emphasis mb-2">Select Plant to View Sections</h3>
      <p class="text-body-2 text-medium-emphasis">Choose a plant from the dropdown above to see WIP process sections.</p>
    </VCard>

    <div v-else class="wip-process-layout">
      <VCard rounded="lg" elevation="1">
        <VCardTitle class="d-flex align-center justify-space-between">
          <span class="text-subtitle-1 font-weight-bold">Sections</span>
          <div class="d-flex align-center ga-2">
            <VChip size="small" color="primary" variant="tonal">{{ activePlantLabel }}</VChip>
            <VChip size="small" color="primary" variant="tonal">{{ store.sections.length }}</VChip>
          </div>
        </VCardTitle>
        <VDivider />
        <VTable density="comfortable" hover>
          <thead>
            <tr>
              <th style="width: 40px" class="px-2"></th>
              <th>Section Name</th>
              <th>Code</th>
              <th>Plant</th>
              <th>Steps</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(section, index) in store.sections"
              :key="section.id"
              :class="{ 
                'bg-primary-lighten-5': Number(store.selectedSectionId) === Number(section.id),
                'dragging': dragIndexSection === index,
                'drag-over-section': dropIndexSection === index 
              }"
              @click="store.selectedSectionId = section.id"
              :draggable="dragEnabledSection === index"
              @dragstart="dragStartSection($event, index)"
              @dragover.prevent="dragOverSection($event, index)"
              @drop="dropSection($event, index)"
              @dragenter.prevent
              class="cursor-pointer"
            >
              <td class="px-2" @mouseenter="dragEnabledSection = index" @mouseleave="dragEnabledSection = null">
                <VIcon class="cursor-move text-medium-emphasis" icon="ri-menu-line" size="small" />
              </td>
              <td class="font-weight-semibold text-primary">{{ section.name }}</td>
              <td><code class="text-medium-emphasis text-body-2">{{ section.code }}</code></td>
              <td>
                <VChip size="x-small" :color="section.plant_id ? 'primary' : 'neutral'" variant="tonal">
                  {{ getPlantName(section.plant_id) }}
                </VChip>
              </td>
              <td><VChip size="small" variant="tonal" color="primary">{{ section.steps?.length || 0 }} steps</VChip></td>
              <td class="text-right">
                <VBtn icon="ri-edit-line" variant="text" size="small" @click.stop="openSection(section)" />
                <VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click.stop="confirmDeleteSection(section)" />
              </td>
            </tr>
            <tr v-if="!store.sections.length">
              <td colspan="6" class="text-center text-medium-emphasis py-8">No sections configured</td>
            </tr>
          </tbody>
        </VTable>
      </VCard>

      <VCard rounded="lg" elevation="1">
        <VCardTitle class="d-flex align-center justify-space-between flex-wrap ga-2">
          <div>
            <span class="text-subtitle-1 font-weight-bold">{{ store.selectedSection?.name || 'Select Section' }}</span>
            <div class="text-caption text-medium-emphasis">{{ store.selectedSection?.code || '-' }}</div>
          </div>
          <VBtn prepend-icon="ri-add-line" color="primary" :disabled="!store.selectedSectionId" @click="openStep()">Step</VBtn>
        </VCardTitle>
        <VDivider />
        <VTable density="comfortable">
          <thead>
            <tr>
              <th style="width: 48px">#</th>
              <th>Type</th>
              <th>Label</th>
              <th>Feed</th>
              <th>Rundown</th>
              <th>DCS Tag</th>
              <th>Mode</th>
              <th>Status</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="(step, index) in store.selectedSteps" 
              :key="step.id"
              :draggable="dragEnabledStep === index"
              @dragstart="dragStartStep($event, index)"
              @dragover.prevent="dragOverStep($event, index)"
              @drop="dropStep($event, index)"
              @dragenter.prevent
              :class="{ 'dragging': dragIndexStep === index, 'drag-over-step': dropIndexStep === index }"
            >
              <td class="d-flex align-center px-2" @mouseenter="dragEnabledStep = index" @mouseleave="dragEnabledStep = null">
                <VIcon class="cursor-move text-medium-emphasis mr-1" icon="ri-menu-line" size="x-small" />
                <span class="text-caption font-weight-medium">{{ step.sort_order }}</span>
              </td>
              <td>
                <VChip size="x-small" :color="stepTypeColor(step.step_type)" variant="tonal" class="text-caption font-weight-medium">
                  {{ stepTypeLabel(step.step_type) }}
                </VChip>
              </td>
              <td class="font-weight-medium">{{ step.label }}</td>
              <td class="text-caption">{{ step.feed_id || '-' }}</td>
              <td class="text-caption">{{ step.rundown_id || '-' }}</td>
              <td class="text-caption"><code v-if="step.dcs_tag" class="text-body-2">{{ step.dcs_tag }}</code><span v-else class="text-medium-emphasis">-</span></td>
              <td>
                <span v-if="step.mode_group" class="text-caption">
                  <span class="font-weight-medium">{{ step.mode_group }}</span>
                  <span v-if="step.mode_value" class="text-medium-emphasis ms-1">= {{ step.mode_value }}</span>
                </span>
                <span v-else class="text-medium-emphasis text-caption">-</span>
              </td>
              <td>
                <VChip :color="Number(step.status) === 1 ? 'success' : 'error'" size="x-small" variant="tonal">
                  {{ Number(step.status) === 1 ? 'Active' : 'Inactive' }}
                </VChip>
              </td>
              <td class="text-right">
                <VBtn icon="ri-edit-line" variant="text" size="small" @click="openStep(step)" />
                <VBtn icon="ri-delete-bin-line" variant="text" size="small" color="error" @click="store.deleteStep(step.id)" />
              </td>
            </tr>
            <tr v-if="!store.selectedSteps.length">
              <td colspan="9" class="text-center text-medium-emphasis py-8">No steps configured</td>
            </tr>
          </tbody>
        </VTable>
      </VCard>
    </div>

    <WipSectionDialog v-model="sectionDialog" :section="editingSection" :plant-id="localPlantId" :saving="store.saving" @save="saveSection" />
    <WipStepDialog v-model="stepDialog" :step="editingStep" :section="store.selectedSection" :saving="store.saving" @save="saveStep" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useSetupPlantStore } from '@/stores/plant.js'
import { useWipProcessStore } from '../stores/wipProcessStore'
import WipSectionDialog from './WipSectionDialog.vue'
import WipStepDialog from './WipStepDialog.vue'

const store = useWipProcessStore()
const setupPlantStore = useSetupPlantStore()
const sectionDialog = ref(false)
const stepDialog = ref(false)
const editingSection = ref(null)
const editingStep = ref(null)

const dragIndexSection = ref(null)
const dropIndexSection = ref(null)
const dragIndexStep = ref(null)
const dropIndexStep = ref(null)

const dragEnabledSection = ref(null)
const dragEnabledStep = ref(null)

const localPlantId = ref(null)
const localPlantName = ref('')

const plantOptions = computed(() => {
  return setupPlantStore.plants.filter(p => p.status == 1)
})

const activePlantLabel = computed(() =>
  localPlantName.value || 'EOB-1'
)

const defaultPlantCode = 'EOB1'

function getPlantName(plantId) {
  if (!plantId) return 'All Plants'
  const plant = setupPlantStore.plants.find(p => Number(p.id_plant) === Number(plantId))
  return plant?.description || plantId
}

function stepTypeLabel(type) {
  const map = { label: 'Label', feed: 'Feed', rundown: 'Rundown', mode_switch: 'Mode Switch' }
  return map[type] || type
}

function stepTypeColor(type) {
  const map = { label: 'neutral', feed: 'success', rundown: 'info', mode_switch: 'warning' }
  return map[type] || 'neutral'
}

function openSection(section = null) {
  editingSection.value = section
  sectionDialog.value = true
}

function openStep(step = null) {
  editingStep.value = step
  stepDialog.value = true
}

async function saveSection(payload) {
  await store.saveSection(payload)
  sectionDialog.value = false
}

async function saveStep(payload) {
  await store.saveStep(payload)
  stepDialog.value = false
}

async function confirmDeleteSection(section) {
  if (confirm(`Apakah Anda yakin ingin menonaktifkan seksi "${section.name}"?`)) {
    await store.deleteSection(section.id)
    if (Number(store.selectedSectionId) === Number(section.id)) {
      store.selectedSectionId = store.sections.length ? store.sections[0].id : null
    }
  }
}

function reloadSections() {
  return store.fetchSections({ id_plant: localPlantId.value || 0 })
}

function onPlantChange(val) {
  const plant = setupPlantStore.plants.find(p => Number(p.id_plant) === Number(val))
  localPlantName.value = plant?.description || ''
  reloadSections()
}

function dragStartSection(e, index) {
  dragIndexSection.value = index
  e.dataTransfer.effectAllowed = 'move'
}

function dragOverSection(e, index) {
  dropIndexSection.value = index
}

async function dropSection(e, index) {
  const fromIndex = dragIndexSection.value
  const toIndex = index
  dragIndexSection.value = null
  dropIndexSection.value = null
  
  if (fromIndex === null || fromIndex === toIndex) return

  const items = [...store.sections]
  const [movedItem] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, movedItem)
  
  const payload = items.map((item, i) => ({ id: item.id, sort_order: i + 1 }))
  
  store.sections = items
  await store.reorderSections(payload)
}

function dragStartStep(e, index) {
  dragIndexStep.value = index
  e.dataTransfer.effectAllowed = 'move'
}

function dragOverStep(e, index) {
  dropIndexStep.value = index
}

async function dropStep(e, index) {
  const fromIndex = dragIndexStep.value
  const toIndex = index
  dragIndexStep.value = null
  dropIndexStep.value = null
  
  if (fromIndex === null || fromIndex === toIndex) return

  const items = [...store.selectedSteps]
  const [movedItem] = items.splice(fromIndex, 1)
  items.splice(toIndex, 0, movedItem)
  
  const payload = items.map((item, i) => ({ id: item.id, sort_order: i + 1 }))
  
  if (store.selectedSection) {
    store.selectedSection.steps = items
  }
  await store.reorderSteps(payload)
}

onMounted(async () => {
  if (setupPlantStore.plants.length === 0) await setupPlantStore.fetchPlants()
  const defaultPlant = setupPlantStore.plants.find(p => p.code_2 === defaultPlantCode)
  if (defaultPlant) {
    localPlantId.value = defaultPlant.id_plant
    localPlantName.value = defaultPlant.description
  }
  reloadSections()
})
</script>

<style scoped>
.wip-process-layout {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

@media (max-width: 960px) {
  .wip-process-layout {
    gap: 16px;
  }
}

.dragging {
  opacity: 0.5;
  background-color: rgba(var(--v-theme-surface-variant), 0.5);
}

.drag-over-section {
  border-top: 2px dashed rgb(var(--v-theme-primary));
}

.drag-over-step td {
  border-top: 2px dashed rgb(var(--v-theme-primary));
}

.cursor-move {
  cursor: move;
}

.plant-select {
  min-width: 180px;
}

code {
  font-size: inherit;
}
</style>
