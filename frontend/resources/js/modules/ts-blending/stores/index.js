import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import blendingApi from '../api'
import { useToastStore } from '@/stores/toast'

export const useTsBlendingStore = defineStore('transactionBlending', () => {
  const toastStore = useToastStore()

  const blendingList = ref([])
  const materialList = ref([])
  const activeMaterials = ref([])
  const activeTanks = ref([])
  const activeSpecificTanks = ref([])
  const tanks = ref([])
  const tankDetails = ref([])
  const allTanks = ref([])
  const currentEntryNo = ref('')
  const totalStock = ref(0)
  const totalQty = ref(0)
  const loading = ref(false)
  const error = ref(null)

  const blendingCount = computed(() => blendingList.value.length)

  async function fetchBlendingList(params = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.getList(params)
      blendingList.value = response?.data || []
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchActiveMaterials() {
    try {
      const response = await blendingApi.getActiveMaterials()
      activeMaterials.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch active materials:')
      throw err
    }
  }

  async function fetchNewEntryNo(params = {}) {
    try {
      const response = await blendingApi.getNewEntryNo(params)
      currentEntryNo.value = response?.data?.[0]?.entryNo || ''
      return response
    } catch (err) {
      toastStore.error('Failed to generate entry no:', err)
      throw err
    }
  }

  async function fetchTotalStockMaterial(params = {}) {
    try {
      const response = await blendingApi.getTotalStockMaterial(params)
      totalStock.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      toastStore.error('Failed to fetch total stock:')
      throw err
    }
  }

  async function fetchTotalQtyMaterial(params = {}) {
    try {
      const response = await blendingApi.getTotalQtyMaterial(params)
      totalQty.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      toastStore.error('Failed to fetch total qty:')
      throw err
    }
  }

  async function fetchMaterialList(params = {}) {
    try {
      const response = await blendingApi.getMaterialList(params)
      materialList.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch material list:')
      throw err
    }
  }

  async function fetchActiveTanksRundown(params = {}) {
    try {
      const response = await blendingApi.getActiveTanksRundown(params)
      activeTanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch active tanks:')
      throw err
    }
  }

  async function fetchActiveSpecificTanksRundown(params = {}) {
    try {
      const response = await blendingApi.getActiveSpecificTanksRundown(params)
      activeSpecificTanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch specific tanks:')
      throw err
    }
  }

  async function fetchTanks(params = {}) {
    try {
      const response = await blendingApi.getTanks(params)
      tanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch tanks:')
      throw err
    }
  }

  async function fetchTankDetails(tankId, params = {}) {
    try {
      const response = await blendingApi.getTankDetails(tankId, params)
      tankDetails.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch tank details:')
      throw err
    }
  }

  async function fetchAllTanks(params = {}) {
    try {
      const response = await blendingApi.getAllTanks(params)
      allTanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch all tanks:')
      throw err
    }
  }

  async function addMaterialToBlending(data) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.store({
        ...data,
        flag: 'post_blendingEntryMaterial'
      })
      if (response?.status === 1) {
        toastStore.success('Material added to blending')
      } else {
        toastStore.error(response?.message || 'Failed to add material')
      }
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function executeBlending(data) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.store({
        ...data,
        flag: 'post_blendingEntry'
      })
      if (response?.status === 1) {
        toastStore.success('Blending executed successfully')
        await fetchBlendingList()
      } else {
        toastStore.error(response?.message || 'Blending execution failed')
      }
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteBlendingMaterial(data) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.store({
        ...data,
        flag: 'delete_blendingMaterial'
      })
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deactivateBlending(id) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.deactivate(id)
      if (response?.status === 1) {
        toastStore.success('Blending deactivated')
        await fetchBlendingList()
      }
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateMaterialDoc(data) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.store({
        ...data,
        flag: 'post_matlDocNumber'
      })
      if (response?.status === 1) {
        toastStore.success(response?.message || 'Material document updated')
      }
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateSubTank(data) {
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.store({
        ...data,
        flag: 'post_updateEntrySubTank'
      })
      if (response?.status === 1) {
        toastStore.success('Sub-tank updated')
      }
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    blendingList,
    materialList,
    activeMaterials,
    activeTanks,
    activeSpecificTanks,
    tanks,
    tankDetails,
    allTanks,
    currentEntryNo,
    totalStock,
    totalQty,
    loading,
    error,
    blendingCount,
    fetchBlendingList,
    fetchActiveMaterials,
    fetchNewEntryNo,
    fetchTotalStockMaterial,
    fetchTotalQtyMaterial,
    fetchMaterialList,
    fetchActiveTanksRundown,
    fetchActiveSpecificTanksRundown,
    fetchTanks,
    fetchTankDetails,
    fetchAllTanks,
    addMaterialToBlending,
    executeBlending,
    deleteBlendingMaterial,
    deactivateBlending,
    updateMaterialDoc,
    updateSubTank
  }
})
