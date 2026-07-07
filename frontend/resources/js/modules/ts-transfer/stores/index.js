import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import transferApi from '../services/index.js'
import { useToastStore } from '@/stores/toast.js'
import { registerCacheResetCallback } from '@/stores/plant.js'
import { useTransactionList } from '@/composables/useTransactionList.js'
import { useTransactionAction } from '@/composables/useTransactionAction'

export const useTsTransferStore = defineStore('transactionTransfer', () => {
  const toastStore = useToastStore()

  // Use the composable for the main list
  const {
    list: transferList,
    loading,
    error,
    pagination,
    setPage,
    resetCache: resetListCache,
    fetchList: doFetchList
  } = useTransactionList(
    (params) => transferApi.getTransferList(params.id_plant || 0, params.page || 1, params.per_page || 5),
    { listKey: 'transferList' }
  )

  async function fetchTransferList(plantId = 0, page = 1, perPage = 5) {
    return doFetchList({ id_plant: plantId, page, per_page: perPage })
  }

  const activeMaterials = ref([])
  const activeTanks = ref([])
  const activeSpecificTanks = ref([])
  const currentEntryNo = ref('')
  const totalStock = ref(0)
  const supplierCode = ref(null)

  // Pending history state
  const pendingHistoryList = ref([])
  const pendingHistoryPagination = ref({ total: 0, page: 1, perPage: 5, lastPage: 1 })
  const showPendingHistory = ref(false)
  const pendingCount = ref(0)
  const hasPending = computed(() => pendingCount.value > 0)

  const STALE_TIME = 30 * 1000
  const _cache = { activeMaterials: 0 }

  function _isFresh(key) {
    return Date.now() - (_cache[key] || 0) < STALE_TIME
  }

  function _touch(key) {
    _cache[key] = Date.now()
  }

  function resetCache() {
    resetListCache()
    Object.keys(_cache).forEach(k => { _cache[k] = 0 })
  }
  registerCacheResetCallback(resetCache)

  async function fetchActiveMaterials() {
    if (_isFresh('activeMaterials') && activeMaterials.value.length > 0) return
    try {
      const response = await transferApi.getActiveMaterials()
      activeMaterials.value = response?.data || []
      _touch('activeMaterials')
    } catch {
      toastStore.error('Failed to fetch active materials:')
    }
  }

  async function fetchNewEntryNo(params = {}) {
    try {
      const response = await transferApi.getNewEntryNo(params)
      currentEntryNo.value = response?.data?.[0]?.entryNo || ''
      return response
    } catch (err) {
      toastStore.error('Failed to generate entry no:', err)
      throw err
    }
  }

  async function fetchTransferQty(payload){
    try{
        const res = await import('@/modules/shared/services/qty').then(m=>m.fetchQty(payload))
        return res.data
    }catch(e){
        toastStore.error('Fetch qty failed: '+(e.message||e))
        throw e
    }
}

  async function fetchTotalStockMaterial(params = {}) {
    try {
      const response = await transferApi.getTotalStock(params)
      totalStock.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      toastStore.error('Failed to fetch total stock:')
      throw err
    }
  }


  async function fetchActiveTanksRundown(params = {}) {
    try {
      const response = await transferApi.getTanksRundown(params)
      activeTanks.value = response?.data?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch tanks:')
      throw err
    }
  }

  async function fetchActiveSpecificTanksRundown(params = {}) {
    try {
      const response = await transferApi.getSpecificTanksRundown(params)
      activeSpecificTanks.value = response?.data?.data || response?.data || []
      return response
    } catch (err) {
      toastStore.error('Failed to fetch specific tanks:')
      throw err
    }
  }

  async function fetchSupplierCode(params = {}) {
    try {
      const response = await transferApi.getSupplierCode(params)
      supplierCode.value = response?.data?.[0] || null
      return response
    } catch (err) {
      toastStore.error('Failed to fetch supplier code:')
      throw err
    }
  }

  async function submitTransferEntry(data) {
    loading.value = true
    error.value = null
    try {
      const response = await transferApi.store({
        ...data,
        flag: 'post_transferEntry'
      })
      if (response?.status === 1) {
        toastStore.success('Transfer executed successfully')
        await fetchTransferList()
        await checkPendingCount()
      } else {
        toastStore.error(response?.message || 'Transfer failed')
      }
      return response
    } catch (err) {
      const apiMsg = err.response?.data?.message || err.message || 'Transfer failed'
      error.value = apiMsg
      toastStore.error(apiMsg)
      return { status: 0, message: apiMsg }
    } finally {
      loading.value = false
    }
  }

  async function submitMatlDocNumber(mode, id, number) {
    try {
      const response = await transferApi.postMatlDocNumber(mode, id, number)
      if (response?.status === 1) {
        toastStore.success('Material document updated')
      }
      return response
    } catch (err) {
      toastStore.error(err.message || 'Failed to update material document')
      throw err
    }
  }

  async function submitUpdateEntrySubTank(idHead, idSlocTail) {
    try {
      const response = await transferApi.postUpdateEntrySubTank(idHead, idSlocTail)
      if (response?.status === 1) {
        toastStore.success('Sub-tank updated')
      }
      return response
    } catch (err) {
      toastStore.error(err.message || 'Failed to update sub-tank')
      throw err
    }
  }

  const { execute: deleteTransfer } = useTransactionAction(
    (id) => transferApi.deactivateTransfer(id),
    fetchTransferList,
    'Transfer deactivated successfully'
  )

  // Pending history methods
  async function fetchPendingHistory(page = 1, perPage = 5) {
    loading.value = true
    error.value = null
    try {
      const response = await transferApi.getPendingHistory(page, perPage)
      pendingHistoryList.value = response?.data?.data || []
      pendingHistoryPagination.value = {
        total: response?.data?.total || 0,
        page: response?.data?.page || 1,
        perPage: response?.data?.per_page || 5,
        lastPage: response?.data?.last_page || 1
      }
      pendingCount.value = response?.data?.total || 0
      return response
    } catch (err) {
      const apiMsg = err.response?.data?.message || err.message || 'Failed to fetch pending history'
      error.value = apiMsg
      toastStore.error(apiMsg)
      return { status: 0, message: apiMsg }
    } finally {
      loading.value = false
    }
  }

  async function approvePendingTransfer(idBalanceHead, notes = '') {
    loading.value = true
    error.value = null
    try {
      const response = await transferApi.approveTransfer(idBalanceHead, { notes })
      if (response?.status === 1) {
        toastStore.success('Transfer approved successfully')
        await fetchPendingHistory(pendingHistoryPagination.value.page, pendingHistoryPagination.value.perPage)
        await fetchTransferList()
      } else {
        toastStore.error(response?.message || 'Failed to approve transfer')
      }
      return response
    } catch (err) {
      const apiMsg = err.response?.data?.message || err.message || 'Failed to approve transfer'
      error.value = apiMsg
      toastStore.error(apiMsg)
      return { status: 0, message: apiMsg }
    } finally {
      loading.value = false
    }
  }

  async function checkPendingCount() {
    try {
      const response = await transferApi.getPendingHistory(1, 1)
      pendingCount.value = response?.data?.total || 0
    } catch {
      // silent fail
    }
  }

  return {
    transferList,
    activeMaterials,
    activeTanks,
    activeSpecificTanks,
    currentEntryNo,
    totalStock,
    supplierCode,
    loading,
    error,
    pagination,
    setPage,
    resetCache,
    fetchTransferList,
    fetchActiveMaterials,
    fetchNewEntryNo,
    fetchTransferQty,
    fetchTotalStockMaterial,
    fetchActiveTanksRundown,
    fetchActiveSpecificTanksRundown,
    fetchSupplierCode,
    submitTransferEntry,
    submitMatlDocNumber,
    submitUpdateEntrySubTank,
    deleteTransfer,
    // Pending history
    pendingHistoryList,
    pendingHistoryPagination,
    showPendingHistory,
    hasPending,
    pendingCount,
    fetchPendingHistory,
    approvePendingTransfer,
    checkPendingCount
  }
})

export const useTsRawTransferStore = useTsTransferStore