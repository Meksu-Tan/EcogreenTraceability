import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useToastStore } from '@/stores/toast'

export function useWipPlant() {
  const plantSelectionStore = usePlantSelectionStore()
  const toast = useToastStore()

  function effectivePlantId() {
    return plantSelectionStore.selectedPlantId
  }

  function plantParams() {
    return { id_plant: plantSelectionStore.selectedPlantId || 0 }
  }

  /** WIP entry/save requires a specific plant (same as legacy admin plant picker). */
  function requirePlantId() {
    const id = plantSelectionStore.selectedPlantId
    if (!id) {
      toast.error('Silakan pilih plant terlebih dahulu (Switch Plant).')
      return null
    }
    return id
  }

  function canTransact() {
    return Boolean(plantSelectionStore.selectedPlantId)
  }

  return {
    plantSelectionStore,
    effectivePlantId,
    plantParams,
    requirePlantId,
    canTransact,
  }
}
