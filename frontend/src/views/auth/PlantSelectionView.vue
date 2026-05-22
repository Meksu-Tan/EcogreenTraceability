<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Select Your Plant</h1>
        <p class="text-slate-600">Please choose a plant to start managing transactions</p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- All Plants Option -->
        <div 
          @click="selectPlant(null, 'All Plants')"
          class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-green-500 hover:shadow-md transition-all cursor-pointer group"
        >
          <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-50 transition-colors">
            <svg class="w-6 h-6 text-slate-600 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900 mb-1">All Plants</h3>
          <p class="text-sm text-slate-500">View transactions from all available plants</p>
        </div>

        <!-- Individual Plants -->
        <div 
          v-for="plant in plants" 
          :key="plant.id_plant"
          @click="selectPlant(plant.id_plant, plant.description)"
          class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:border-green-500 hover:shadow-md transition-all cursor-pointer group"
        >
          <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-4 group-hover:bg-green-100 transition-colors">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-slate-900 mb-1">{{ plant.description }}</h3>
          <p class="text-sm text-slate-500">{{ plant.code }} • {{ plant.code_2 }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSetupPlantStore } from '@/stores/plant'
import { usePlantSelectionStore } from '@/stores/plant'
import { storeToRefs } from 'pinia'

const router = useRouter()
const plantStore = useSetupPlantStore()
const selectionStore = usePlantSelectionStore()
const { plants, loading } = storeToRefs(plantStore)

onMounted(async () => {
  await plantStore.fetchPlants()
})

const selectPlant = (id, name) => {
  selectionStore.setPlant(id, name)
  router.push('/dashboard')
}
</script>
