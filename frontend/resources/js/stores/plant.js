import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import * as plantApi from '@/modules/m-plant/services/index.js'

// Cache reset callback for dependent stores
let cacheResetCallback = null

export function registerCacheResetCallback(callback) {
  cacheResetCallback = callback
}

export const useSetupPlantStore = defineStore('setupPlant', () => {
  const plants  = ref([])
  const loading = ref(false)

  async function fetchPlants() {
    loading.value = true
    try {
      const res = await plantApi.getPlants()
      plants.value = res.data.data
    } catch {
      plants.value = []
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

export const usePlantSelectionStore = defineStore('plantSelection', () => {
  const selectedPlantId = ref(null)
  const selectedPlantName = ref('All Plants')
  const selectedPlantCode = ref('')
  const hasUserSelected = ref(false)

  const hasSelectedPlant = computed(() => hasUserSelected.value)

  function setPlant(id, name, code = '') {
    selectedPlantId.value = id
    selectedPlantName.value = name || 'All Plants'
    selectedPlantCode.value = code || ''
    hasUserSelected.value = true
    // Trigger cache reset when plant changes
    if (cacheResetCallback) {
      cacheResetCallback()
    }
  }

  function clearPlant() {
    selectedPlantId.value = null
    selectedPlantName.value = 'All Plants'
    selectedPlantCode.value = ''
    hasUserSelected.value = false
    // Trigger cache reset when plant changes
    if (cacheResetCallback) {
      cacheResetCallback()
    }
  }

  return {
    selectedPlantId,
    selectedPlantName,
    selectedPlantCode,
    hasSelectedPlant,
    hasUserSelected,
    setPlant,
    clearPlant
  }
})
