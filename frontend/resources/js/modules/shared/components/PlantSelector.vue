<template>
  <VBtn variant="outlined" color="medium-emphasis" size="small" :disabled="!canSwitch">
    <VIcon icon="ri-swap-line" size="16" class="me-2" />
    <div class="text-start">
      <span class="d-block text-overline" style="line-height: 1; font-size: 9px;">Switch Plant</span>
      <span class="d-block text-body-2 font-weight-bold" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
        {{ plantSelectionStore.selectedPlantName || 'Select Plant' }}
      </span>
    </div>
    <VIcon v-if="canSwitch" icon="ri-arrow-down-s-line" size="16" class="ms-1" />

    <VMenu v-if="canSwitch" activator="parent" location="bottom end" :offset="4" width="260">
      <VCard rounded="lg" elevation="2">
        <VCardSubtitle class="pa-3 pb-2 text-overline">Select Plant Location</VCardSubtitle>
        <VDivider />
        <VList density="compact" style="max-height: 260px; overflow-y: auto;">
          <VListItem
            v-if="isAdmin"
            prepend-icon="ri-layout-grid-line"
            title="All Plants"
            :active="plantSelectionStore.selectedPlantId === null"
            base-color="primary"
            @click="selectPlant(null, 'All Plants')"
          >
            <template #append>
              <VIcon v-if="plantSelectionStore.selectedPlantId === null" icon="ri-check-line" color="primary" size="16" />
            </template>
          </VListItem>
          <VListItem
            v-for="plant in visiblePlants"
            :key="plant.id_plant"
            prepend-icon="ri-building-4-line"
            :title="plant.description"
            :active="plantSelectionStore.selectedPlantId === plant.id_plant"
            base-color="primary"
            @click="selectPlant(plant.id_plant, plant.description)"
          >
            <template #append>
              <VIcon v-if="plantSelectionStore.selectedPlantId === plant.id_plant" icon="ri-check-line" color="primary" size="16" />
            </template>
          </VListItem>
        </VList>
      </VCard>
    </VMenu>
  </VBtn>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant'
import { useAuthStore } from '@/modules/auth/stores/authStore'

const emit = defineEmits(['change'])

const authStore = useAuthStore()
const plantSelectionStore = usePlantSelectionStore()
const plantStore          = useSetupPlantStore()

const isAdmin = computed(() => {
  return authStore.hasAnyRole(['super-admin', 'admin'])
})

const visiblePlants = computed(() => {
  if (isAdmin.value) {
    return plantStore.plants
  }
  return authStore.user?.plants || []
})

const canSwitch = computed(() => {
  if (isAdmin.value) return true
  return visiblePlants.value.length > 1
})

function selectPlant(id, name) {
  plantSelectionStore.setPlant(id, name)
  emit('change', id)
}

onMounted(async () => {
  if (plantStore.plants.length === 0) await plantStore.fetchPlants()

  // If not admin and no plant is selected, auto-select the first assigned plant
  if (!isAdmin.value && plantSelectionStore.selectedPlantId === null) {
    const assigned = authStore.user?.plants || []
    if (assigned.length > 0) {
      selectPlant(assigned[0].id_plant, assigned[0].description)
    }
  }
})
</script>