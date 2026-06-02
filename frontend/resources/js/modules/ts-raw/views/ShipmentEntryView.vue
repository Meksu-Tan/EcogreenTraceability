<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Shipment Entry</h1>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Lokasi:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
              <Icon icon="ri:factory-line" class="mr-1.5 w-3 h-3 opacity-70" />
              {{ plantSelectionStore.selectedPlantName }}
            </span>
          </div>
        </div>
        <div class="h-10 w-px bg-gray-200"></div>
        <PlantSelector @change="fetchData" />
      </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-green-800 text-sm flex items-center gap-2">
          <Icon icon="ri:information-line" class="w-4 h-4" />
          This module is under development for <strong>{{ plantSelectionStore.selectedPlantName }}</strong>. Full functionality will be available soon.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { usePlantSelectionStore } from '@/stores/plant'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'

const plantSelectionStore = usePlantSelectionStore()

function fetchData() {
  // Placeholder for future implementation
}
</script>

<script setup>
import { ref, onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'

const plantSelectionStore = usePlantSelectionStore()

function fetchData() {
  // Plant selection changed - data will be refetched
}

onMounted(() => {
  fetchData()
})
</script>