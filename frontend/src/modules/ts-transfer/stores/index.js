import { defineStore } from 'pinia'
import { ref } from 'vue'
import transferApi from '../api'
import { useToastStore } from '@/stores/toast'

export const useTsTransferStore = defineStore('transactionTransfer', () => {
  const toastStore = useToastStore()

  const transferList = ref([])
  const activeMaterials = ref([])
  const activeTanks = ref([])
  const activeSpecificTanks = ref([])
  const currentEntryNo = ref('')
  const totalStock = ref(0)
  const supplierCode = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchTransferList(plantId = 0) {
    loading.value = true
    error.value = null
    try {
      const response = await transferApi.getTransferList(plantId)
      transferList.value = response?.data || []
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      transferList.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchActiveMaterials() {
    try {
      const response = await transferApi.getActiveMaterials()
      activeMaterials.value = response?.data || []
    } catch (err) {
      console.error('Failed to fetch active materials:', err)
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

  async function fetchTotalStockMaterial(params = {}) {
    try {
      const response = await transferApi.getTotalStock(params)
      totalStock.value = parseFloat(response?.data?.[0]?.total || 0)
      return response
    } catch (err) {
      console.error('Failed to fetch total stock:', err)
      throw err
    }
  }

  async function fetchActiveTanksRundown(params = {}) {
    try {
      const response = await transferApi.getTanksRundown(params)
      activeTanks.value = response?.data || []
      return response
    } catch (err) {
      console.error('Failed to fetch tanks:', err)
      throw err
    }
  }

  async function fetchActiveSpecificTanksRundown(params = {}) {
    try {
      const response = await transferApi.getSpecificTanksRundown(params)
      activeSpecificTanks.value = response?.data || []
      return response
    } catch (err) {
      console.error('Failed to fetch specific tanks:', err)
      throw err
    }
  }

  async function fetchSupplierCode(params = {}) {
    try {
      const response = await transferApi.getSupplierCode(params)
      supplierCode.value = response?.data?.[0] || null
      return response
    } catch (err) {
      console.error('Failed to fetch supplier code:', err)
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
      } else {
        toastStore.error(response?.message || 'Transfer failed')
      }
      return response
    } catch (err) {
      error.value = err.message
      toastStore.error(err.message || 'Transfer failed')
      throw err
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

  async function submitUpdateEntrySubTank(idHead, idTankTail) {
    try {
      const response = await transferApi.postUpdateEntrySubTank(idHead, idTankTail)
      if (response?.status === 1) {
        toastStore.success('Sub-tank updated')
      }
      return response
    } catch (err) {
      toastStore.error(err.message || 'Failed to update sub-tank')
      throw err
    }
  }

  async function deleteTransfer(id) {
    loading.value = true
    try {
      const response = await transferApi.deactivateTransfer(id)
      if (response?.status === 1) {
        toastStore.success('Transfer deactivated successfully')
        await fetchTransferList()
      } else {
        toastStore.error(response?.message || 'Failed to deactivate transfer')
      }
      return response
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      toastStore.error(err.message || 'Failed to deactivate transfer')
      throw err
    } finally {
      loading.value = false
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
    fetchTransferList,
    fetchActiveMaterials,
    fetchNewEntryNo,
    fetchTotalStockMaterial,
    fetchActiveTanksRundown,
    fetchActiveSpecificTanksRundown,
    fetchSupplierCode,
    submitTransferEntry,
    submitMatlDocNumber,
    submitUpdateEntrySubTank,
    deleteTransfer
  }
})

export const useTsRawTransferStore = useTsTransferStore
