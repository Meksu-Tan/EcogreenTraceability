<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Packaging Entry</h1>
        <div class="d-flex align-center gap-2 mt-1">
          <span class="text-caption text-medium-emphasis">Location:</span>
          <VChip size="x-small" color="primary" variant="tonal" prepend-icon="ri-factory-line">
            {{ plantSelectionStore.selectedPlantName }}
          </VChip>
        </div>
      </div>
      <PlantSelector @change="fetchData" />
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardText class="pa-6">
        <VAlert type="info" variant="tonal" prepend-icon="ri-information-line">
          This module is under development for <strong>{{ plantSelectionStore.selectedPlantName }}</strong>. Full functionality will be available soon.
        </VAlert>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'

const plantSelectionStore = usePlantSelectionStore()

function fetchData() {}

onMounted(() => { fetchData() })
</script>