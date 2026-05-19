import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePlantSelectionStore = defineStore('plantSelection', () => {
  const selectedPlantId = ref(null)
  const selectedPlantName = ref('All Plants')
  const hasUserSelected = ref(false)

  const hasSelectedPlant = computed(() => hasUserSelected.value)

  function setPlant(id, name) {
    selectedPlantId.value = id
    selectedPlantName.value = name || 'All Plants'
    hasUserSelected.value = true
  }

  function clearPlant() {
    selectedPlantId.value = null
    selectedPlantName.value = 'All Plants'
    hasUserSelected.value = false
  }

  return {
    selectedPlantId,
    selectedPlantName,
    hasSelectedPlant,
    hasUserSelected,
    setPlant,
    clearPlant
  }
})
