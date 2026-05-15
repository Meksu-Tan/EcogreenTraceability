import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as plantApi from '@/api/setupPlant'

export const useSetupPlantStore = defineStore('setupPlant', () => {
  const plants  = ref([])
  const loading = ref(false)

  async function fetchPlants() {
    loading.value = true
    try { 
      const res = await plantApi.getPlants()
      plants.value = res.data.data 
    } finally { 
      loading.value = false 
    }
  }

  async function createPlant(data) { 
    const r = await plantApi.storePlant(data)
    if (r.data.status === 1) await fetchPlants()
    return r.data 
  }

  async function editPlant(id, data) { 
    const r = await plantApi.updatePlant(id, data)
    if (r.data.status === 1) await fetchPlants()
    return r.data 
  }

  async function togglePlant(id, status) { 
    const r = status == 1 ? await plantApi.deactivatePlant(id) : await plantApi.activatePlant(id)
    if (r.data.status === 1) await fetchPlants()
    return r.data 
  }

  return {
    plants, loading,
    fetchPlants, createPlant, editPlant, togglePlant
  }
})
