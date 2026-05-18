<template>
  <div class="relative inline-block text-left" ref="dropdownRef">
    <button 
      @click="isOpen = !isOpen"
      class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-green-300 transition-all shadow-sm group"
    >
      <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition-colors">
        <i class="fas fa-sync-alt text-xs"></i>
      </div>
      <div class="text-left">
        <span class="block text-[10px] text-gray-500 uppercase font-bold tracking-wider leading-none">Switch Plant</span>
        <span class="block text-sm font-bold text-gray-700 truncate max-w-[150px]">{{ plantSelectionStore.selectedPlantName || 'Select Plant' }}</span>
      </div>
      <i class="fas fa-chevron-down text-gray-400 text-[10px] ml-2"></i>
    </button>

    <!-- Dropdown Menu -->
    <div v-if="isOpen" class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
      <div class="p-3 bg-gray-50 border-b border-gray-100">
        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Pilih Lokasi Plant</span>
      </div>
      <div class="max-h-64 overflow-y-auto">
        <!-- All Plants -->
        <button 
          @click="selectPlant(null, 'All Plants')"
          class="w-full flex items-center justify-between px-4 py-3 hover:bg-green-50 transition-colors group"
          :class="plantSelectionStore.selectedPlantId === null ? 'bg-green-50 text-green-700' : 'text-gray-600'"
        >
          <div class="flex items-center gap-3">
            <i class="fas fa-globe text-xs opacity-50"></i>
            <span class="text-sm font-semibold">Semua Plant</span>
          </div>
          <i v-if="plantSelectionStore.selectedPlantId === null" class="fas fa-check text-[10px]"></i>
        </button>

        <!-- List -->
        <button 
          v-for="plant in plantStore.plants" 
          :key="plant.id_plant"
          @click="selectPlant(plantValue(plant), plant.description)"
          class="w-full flex items-center justify-between px-4 py-3 hover:bg-green-50 transition-colors group"
          :class="plantSelectionStore.selectedPlantId === plantValue(plant) ? 'bg-green-50 text-green-700' : 'text-gray-600'"
        >
          <div class="flex items-center gap-3">
            <i class="fas fa-factory text-xs opacity-50"></i>
            <span class="text-sm font-semibold">{{ plant.description }}</span>
          </div>
          <i v-if="plantSelectionStore.selectedPlantId === plantValue(plant)" class="fas fa-check text-[10px]"></i>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useSetupPlantStore } from '@/stores/setupPlant'

const emit = defineEmits(['change'])

const plantSelectionStore = usePlantSelectionStore()
const plantStore = useSetupPlantStore()
const isOpen = ref(false)
const dropdownRef = ref(null)

async function selectPlant(id, name) {
  plantSelectionStore.setPlant(id, name)
  isOpen.value = false
  emit('change', id)
}

function plantValue(plant) {
  return plant?.code_3 || plant?.id_plant
}

// Close dropdown on click outside
function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(async () => {
  if (plantStore.plants.length === 0) {
    await plantStore.fetchPlants()
  }
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
