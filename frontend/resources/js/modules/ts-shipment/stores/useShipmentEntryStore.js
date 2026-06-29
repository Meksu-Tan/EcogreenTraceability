import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import shipmentService from '../services/shipmentService'
import { useToastStore } from '@/stores/toast.js'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useTransactionList } from '@/composables/useTransactionList.js'
import { useTransactionAction } from '@/composables/useTransactionAction'

export const useShipmentEntryStore = defineStore('shipmentEntry', () => {
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
    (params) => shipmentService.getEntries(params),
    { listKey: 'entries' }
  )

  async function fetchEntries(params = {}) {
    return doFetchList({ id_plant: plantId.value, ...params })
  }
  const activeFgProducts = ref([])
  const wipBalance = ref(0)
  const wipMaterialLabel = ref('')
  const activeBatches = ref([])
  const newTraceNo = ref('')
  const traceNoLoading = ref(false)
  
  // Secondary details modals state
  const selectedBatchDetails = ref(null)
  const preparationRecords = ref([])
  const sapShipment = ref(null)
  const sapSoAllocation = ref(null)

  // Getters
  const plantId = computed(() => plantStore.selectedPlantId)

  // Actions
  // fetchEntries is replaced by the composable wrapper

  async function fetchActiveFgProducts() {
    try {
      const res = await shipmentService.getActiveFgProducts()
      activeFgProducts.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load finished goods products')
    }
  }

  async function fetchWipMaterials(idMaterial, id_plant) {
    try {
      const res = await shipmentService.getWipMaterials(idMaterial, id_plant || plantId.value)
      if (res.data?.data && res.data.data.length > 0) {
        wipBalance.value = parseFloat(res.data.data[0].balance) || 0
        wipMaterialLabel.value = res.data.data[0].wip_material || ''
      } else {
        wipBalance.value = 0
        wipMaterialLabel.value = 'Product : N/A'
      }
    } catch (error) {
      toastStore.error('Failed to load balance stock')
    }
  }

  async function fetchActiveBatches(idMaterial, id_plant) {
    try {
      const res = await shipmentService.getActiveBatches(idMaterial, id_plant || plantId.value)
      activeBatches.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load active product batches')
    }
  }

  async function fetchBatchDetails(batchNo) {
    try {
      const res = await shipmentService.getShipmentBatchPackaging({ batchNo })
      selectedBatchDetails.value = res.data?.data && res.data.data.length > 0 ? res.data.data[0] : null
    } catch (error) {
      toastStore.error('Failed to load batch packaging details')
    }
  }

  async function fetchPreparationRecords(batchNo) {
    try {
      const res = await shipmentService.getPreparationRecord({ batchNo })
      preparationRecords.value = res.data?.data || []
    } catch (error) {
      toastStore.error('Failed to load preparation records')
    }
  }

  async function fetchSapShipment(batchNo, soNo, soItem) {
    try {
      const res = await shipmentService.getDatShipment({ batchNo, soNo, soItem })
      sapShipment.value = res.data?.data || null
    } catch (error) {
      toastStore.error('Failed to load SAP shipment details')
    }
  }

  async function fetchSapSoAllocation(batchNo) {
    try {
      const res = await shipmentService.getDatSoAllocation({ batchNo })
      sapSoAllocation.value = res.data?.data || null
    } catch (error) {
      toastStore.error('Failed to load SAP SO allocations')
    }
  }

  async function storeEntry(formData) {
    loading.value = true
    try {
      // Append plant context if not already there
      if (formData instanceof FormData) {
        if (!formData.has('id_plant') && plantId.value) {
          formData.append('id_plant', plantId.value)
        }
      }
      const res = await shipmentService.store(formData)
      toastStore.success(res.data?.message || 'Shipment entry stored successfully')
      await fetchEntries()
      return res.data
    } catch (error) {
      const errMsg = error.response?.data?.message || 'Failed to save shipment'
      toastStore.error(errMsg)
      throw error
    } finally {
      loading.value = false
    }
  }

  const { execute: cancelEntry } = useTransactionAction(
    (id, traceNo) => shipmentService.cancel(id, traceNo),
    fetchEntries,
    'Shipment entry cancelled'
  )

  async function updateSo(data) {
    try {
      const res = await shipmentService.updateSo(data)
      toastStore.success(res.data?.message || 'SO updated successfully')
      await fetchEntries()
      return res.data
    } catch (error) {
      toastStore.error('Failed to update SO number')
      throw error
    }
  }

  async function fetchNewTraceNo(id_plant, id_material) {
    const activePlant = id_plant || plantId.value || plantStore.selectedPlantId
    if (!id_material) {
      newTraceNo.value = ''
      return
    }
    traceNoLoading.value = true
    try {
      const res = await shipmentService.getNewTraceNo({ id_material, id_plant: activePlant })
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
    activeBatches.value = []
  }

  function resetBatchSelection() {
    activeBatches.value = []
    wipBalance.value = 0
    wipMaterialLabel.value = ''
  }

  return {
    entries,
    loading,
    error,
    pagination,
    setPage,
    hasEntries,
    entriesCount,
    activeFgProducts,
    wipBalance,
    wipMaterialLabel,
    activeBatches,
    selectedBatchDetails,
    preparationRecords,
    sapShipment,
    sapSoAllocation,
    newTraceNo,
    traceNoLoading,
    plantId,

    fetchEntries,
    fetchActiveFgProducts,
    fetchWipMaterials,
    fetchActiveBatches,
    fetchBatchDetails,
    fetchPreparationRecords,
    fetchSapShipment,
    fetchSapSoAllocation,
    fetchNewTraceNo,
    storeEntry,
    cancelEntry,
    updateSo,
    resetState,
    resetBatchSelection
  }
})
