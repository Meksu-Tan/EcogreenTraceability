import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import blendingApi from '../services'
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
  const pagination = ref({ currentPage: 1, perPage: 5, total: 0, lastPage: 1 })

  const STALE_TIME = 30 * 1000
  const _cache = { blendingList: 0, activeMaterials: 0, materialList: 0, allTanks: 0 }
  const blendingCount = computed(() => blendingList.value.length)

  function _isFresh(key) {
    return Date.now() - (_cache[key] || 0) < STALE_TIME
  }

  function _touch(key) {
    _cache[key] = Date.now()
  }

  function resetCache() {
    Object.keys(_cache).forEach(k => { _cache[k] = 0 })
  }

  function setPage(p) {
    pagination.value = { ...pagination.value, currentPage: p }
  }

  async function fetchBlendingList(params = {}) {
    if (_isFresh('blendingList') && blendingList.value.length > 0) return
    loading.value = true
    error.value = null
    try {
      const response = await blendingApi.getList(params)
      blendingList.value = response?.data || []
      pagination.value = {
        currentPage: response?.current_page || 1,
        perPage: response?.per_page || 5,
        total: response?.total || 0,
        lastPage: response?.last_page || 1
      }
      _touch('blendingList')
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchActiveMaterials() {
    if (_isFresh('activeMaterials') && activeMaterials.value.length > 0) return
    try {
      const response = await blendingApi.getActiveMaterials()
      activeMaterials.value = response?.data || []
      _touch('activeMaterials')
      return response
    } catch (err) {
      toastStore.error('Failed to fetch active materials: ' + (err.message || err))
      throw err
    }
  }

  async function fetchNewEntryNo(params = {}) {
    try {
      const response = await blendingApi.getNewEntryNo(params)
      currentEntryNo.value = response?.data?.[0]?.entryNo || ''
      return response
    } catch (err) {
      toastStore.error('Failed to generate entry no: ' + (err.message || err))
      throw err
    }
  }

  async function fetchTotalStockMaterial(params = {}) {
    try {
      const response = await blendingApi.getTotalStockMaterial(params)
      totalStock.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      toastStore.error('Failed to fetch total stock: ' + (err.message || err))
      throw err
    }
  }

  async function fetchQty(payload){
    try{
        const res = await import('@/modules/shared/services/qty').then(m=>m.fetchQty(payload))
        return res.data
    }catch(e){
        toastStore.error('Fetch qty failed: '+(e.message||e))
        throw e
    }
}

  async function fetchTotalQtyMaterial(params = {}) {
    try {
      const response = await blendingApi.getTotalQtyMaterial(params)
      totalQty.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      toastStore.error('Failed to fetch total qty: ' + (err.message || err))
      throw err
    }
  }

  async function fetchMaterialList(params = {}) {
    if (_isFresh('materialList') && materialList.value.length > 0) return
    try {
      const response = await blendingApi.getMaterialList(params)
      materialList.value = response?.data || []
      _touch('materialList')
      return response
    } catch (err) {
      toastStore.error('Failed to fetch material list: ' + (err.message || err))
      throw err
    }
  }

  async function fetchActiveTanksRundown(params = {}) {
    try {
      const response = await blendingApi.getActiveTanksRundown(params)
      activeTanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch active tanks: ' + (err.message || err))
      throw err
    }
  }

  async function fetchActiveSpecificTanksRundown(params = {}) {
    try {
      const response = await blendingApi.getActiveSpecificTanksRundown(params)
      // ApiResponse::success() wraps Collection in data.data (2 levels)
      activeSpecificTanks.value = response?.data?.data || response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch specific tanks: ' + (err.message || err))
      throw err
    }
  }

  async function fetchTanks(params = {}) {
    try {
      const response = await blendingApi.getTanks(params)
      tanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch tanks: ' + (err.message || err))
      throw err
    }
  }

  async function fetchTankDetails(tankId, params = {}) {
    try {
      const response = await blendingApi.getTankDetails(tankId, params)
      tankDetails.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch tank details: ' + (err.message || err))
      throw err
    }
  }

  async function fetchAllTanks(params = {}) {
    try {
      const response = await blendingApi.getAllTanks(params)
      allTanks.value = response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch all tanks: ' + (err.message || err))
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
    pagination,
    blendingCount,
    setPage,
    resetCache,
    fetchBlendingList,
    fetchActiveMaterials,
    fetchNewEntryNo,
    fetchTotalStockMaterial,
    fetchQty,
    fetchTotalQtyMaterial,
    fetchMaterialList,
    fetchActiveTanksRundown,
    fetchActiveSpecificTanksRundown,
    fetchTanks,
    fetchTankDetails,
    fetchAllTanks,
    addMaterialToBlending,
    fetchQty,
    executeBlending,
    deleteBlendingMaterial,
    deactivateBlending,
    updateMaterialDoc,
    updateSubTank
  }
})
