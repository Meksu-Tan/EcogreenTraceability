<template>
  <div v-if="isOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100 animate-in zoom-in-95 duration-300">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-600 to-green-600 p-6 text-white">
        <h3 class="text-xl font-bold flex items-center gap-3">
          <i class="fas fa-industry text-green-200"></i>
          Pilih Plant Transaksi
        </h3>
        <p class="text-green-50/80 text-sm mt-1">Silakan pilih plant untuk melihat data transaksi di halaman ini.</p>
      </div>

      <!-- Content -->
      <div class="p-6">
        <div class="grid grid-cols-1 gap-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
          <!-- All Plants Option -->
          <button 
            @click="selectPlant(null, 'All Plants')"
            class="group flex items-center justify-between p-4 rounded-xl border-2 transition-all duration-200 text-left"
            :class="plantSelectionStore.selectedPlantId === null ? 'border-green-500 bg-green-50 shadow-md' : 'border-slate-100 hover:border-green-200 hover:bg-slate-50'"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
                <i class="fas fa-globe"></i>
              </div>
              <div>
                <span class="block font-bold text-slate-800">Semua Plant</span>
                <span class="text-xs text-slate-500 uppercase tracking-wider">Tampilkan data dari semua lokasi</span>
              </div>
            </div>
            <i v-if="plantSelectionStore.selectedPlantId === null" class="fas fa-check-circle text-green-600 text-xl"></i>
          </button>

          <!-- Dynamic Plants -->
          <button 
            v-for="plant in plantStore.plants" 
            :key="plant.id_plant"
            @click="selectPlant(plantValue(plant), plant.description)"
            class="group flex items-center justify-between p-4 rounded-xl border-2 transition-all duration-200 text-left"
            :class="plantSelectionStore.selectedPlantId === plantValue(plant) ? 'border-green-500 bg-green-50 shadow-md' : 'border-slate-100 hover:border-green-200 hover:bg-slate-50'"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
                <i class="fas fa-factory"></i>
              </div>
              <div>
                <span class="block font-bold text-slate-800">{{ plant.description }}</span>
                <span class="text-xs text-slate-500 uppercase tracking-wider">ID Plant: {{ plantValue(plant) }}</span>
              </div>
            </div>
            <i v-if="plantSelectionStore.selectedPlantId === plantValue(plant)" class="fas fa-check-circle text-green-600 text-xl"></i>
          </button>
        </div>
      </div>

      <!-- Footer (Optional) -->
      <div v-if="!isMandatory" class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
        <button 
          @click="close"
          class="px-4 py-2 text-slate-600 font-bold hover:text-slate-800 transition-colors"
        >
          Batal
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useSetupPlantStore } from '@/stores/setupPlant'

const props = defineProps({
  isMandatory: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['selected', 'close'])

const plantSelectionStore = usePlantSelectionStore()
const plantStore = useSetupPlantStore()
const isOpen = ref(false)

function open() {
  isOpen.value = true
}

function close() {
  isOpen.value = false
  emit('close')
}

async function selectPlant(id, name) {
  plantSelectionStore.setPlant(id, name)
  isOpen.value = false
  emit('selected', id)
}

function plantValue(plant) {
  return plant?.code_3 || plant?.id_plant
}

onMounted(async () => {
  if (plantStore.plants.length === 0) {
    await plantStore.fetchPlants()
  }
})

defineExpose({ open, close })
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
