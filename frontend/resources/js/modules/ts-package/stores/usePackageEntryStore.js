import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import packageService from '../services/packageService'
import { useToastStore } from '@/stores/toast.js'
import { usePlantSelectionStore, registerCacheResetCallback } from '@/stores/plant.js'
import { useTransactionList } from '@/composables/useTransactionList.js'

export const usePackageEntryStore = defineStore('packageEntry', () => {
  const toastStore = useToastStore()
  const plantStore = usePlantSelectionStore()

  const {
    list: entries,
    loading,
    error,
    pagination,
    setPage,
    resetCache: resetListCache,
    fetchList: doFetchList,
    isFresh,
    touch,
    hasEntries,
    entriesCount
  } = useTransactionList(
    (params) => packageService.getEntries(params),
    { listKey: 'entries' }
  )

  function resetCache() {
    resetListCache()
  }
  registerCacheResetCallback(resetCache)

  async function fetchEntries(params = {}) {
    return doFetchList({ plant: plantId.value, ...params })
  }
  const activeFgProducts = ref([])
  const wipMaterials = ref([])
  const wipBalance = ref(0)
  const wipMaterialLabel = ref('')
  const activeTanks = ref([])
  const activeWarehouses = ref([])
  const allWarehouses = ref([])
  const specificTanks = ref([])
  const newTraceNo = ref('')
  const traceNoLoading = ref(false)

  // Getters
  const plantId = computed(() => plantStore.selectedPlantId)

  // Actions
  // fetchEntries is replaced by the composable wrapper

  async function fetchActiveFgProducts() {
    try {
      const res = await packageService.getActiveFgProducts()
      activeFgProducts.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load finished goods products')
    }
  }

  async function fetchAllWarehouses() {
    try {
      const res = await packageService.getAllWarehouses()
      allWarehouses.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load warehouses')
    }
  }

  async function fetchWipMaterials(idMaterialPck, tank = null) {
    try {
      const res = await packageService.getWipMaterials(idMaterialPck, tank, plantId.value)
      if (res.data?.data && res.data.data.length > 0) {
        wipBalance.value = parseFloat(res.data.data[0].balance) || 0
        wipMaterialLabel.value = res.data.data[0].wip_material || ''
        
        // Also fetch active tanks for the rundown id associated with the product
        if (res.data.data[0].id_rundown) {
          await fetchActiveTanks(res.data.data[0].id_rundown)
        }
      } else {
        wipBalance.value = 0
        wipMaterialLabel.value = 'Product : N/A'
      }
    } catch (error) {
      toastStore.error('Failed to load WIP materials')
    }
  }

  async function fetchActiveTanks(rundownID) {
    try {
      const res = await packageService.getActiveTanks(rundownID)
      activeTanks.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load active tanks')
    }
  }

  async function fetchActiveWarehouses(batchNo) {
    try {
      const res = await packageService.getActiveWarehouses(batchNo)
      activeWarehouses.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load active warehouses')
    }
  }

  async function fetchSpecificTanks(sloc, fgProduct) {
    try {
      const res = await packageService.getSpecificTanks(sloc, fgProduct)
      specificTanks.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load specific tanks')
    }
  }

  async function storeEntry(data) {
    loading.value = true
    try {
      const res = await packageService.store({
        ...data,
        id_plant: plantId.value
      })
      toastStore.success(res.data?.message || 'Packaging entry saved successfully')
      await fetchEntries()
      return res.data
    } catch (error) {
      const errMsg = error.response?.data?.message || 'Failed to save packaging entry'
      toastStore.error(errMsg)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function cancelEntry(id, traceNo) {
    loading.value = true
    try {
      const res = await packageService.cancel(id, traceNo)
      toastStore.success(res.data?.message || 'Packaging entry cancelled')
      await fetchEntries()
      return res.data
    } catch (error) {
      const errMsg = error.response?.data?.message || 'Failed to cancel packaging entry'
      toastStore.error(errMsg)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updatePo(data) {
    try {
      const res = await packageService.updatePo(data)
      toastStore.success(res.data?.message || 'PO updated successfully')
      await fetchEntries()
      return res.data
    } catch (error) {
      toastStore.error('Failed to update PO')
      throw error
    }
  }

  async function updateBatch(data) {
    try {
      const res = await packageService.updateBatch(data)
      toastStore.success(res.data?.message || 'Batch number and warehouse updated')
      await fetchEntries()
      return res.data
    } catch (error) {
      toastStore.error('Failed to update batch number')
      throw error
    }
  }

  async function updateSubTank(data) {
    try {
      const res = await packageService.updateSubTank(data)
      toastStore.success(res.data?.message || 'Subtanks updated')
      await fetchEntries()
      return res.data
    } catch (error) {
      toastStore.error('Failed to update subtanks')
      throw error
    }
  }

  async function fetchNewTraceNo(id_material, id_plant) {
    const activePlant = id_plant || plantId.value || plantStore.selectedPlantId
    if (!id_material) {
      newTraceNo.value = ''
      return
    }
    traceNoLoading.value = true
    try {
      const res = await packageService.getNewTraceNo(id_material, activePlant)
      if (res.data?.data && res.data.data.length > 0) {
        newTraceNo.value = res.data.data[0].traceNo || ''
      } else {
        newTraceNo.value = ''
      }
    } catch {
      newTraceNo.value = ''
    } finally {
      traceNoLoading.value = false
    }
  }

  function resetState() {
    newTraceNo.value = ''
    wipBalance.value = 0
    wipMaterialLabel.value = ''
    activeTanks.value = []
    specificTanks.value = []
    activeWarehouses.value = []
  }

  function clearTraceNo() {
    newTraceNo.value = ''
  }

  return {
    entries,
    loading,
    error,
    pagination,
    setPage,
    hasEntries,
    pagination,
    setPage,
    hasEntries,
    entriesCount,
    activeFgProducts,
    wipMaterials,
    wipBalance,
    wipMaterialLabel,
    activeTanks,
    activeWarehouses,
    allWarehouses,
    specificTanks,
    newTraceNo,
    traceNoLoading,
    plantId,

    fetchEntries,
    fetchActiveFgProducts,
    fetchAllWarehouses,
    fetchWipMaterials,
    fetchActiveTanks,
    fetchActiveWarehouses,
    fetchSpecificTanks,
    fetchNewTraceNo,
    storeEntry,
    cancelEntry,
    updatePo,
    updateBatch,
    updateSubTank,
    resetState,
    clearTraceNo,
    resetCache,
  }
})
