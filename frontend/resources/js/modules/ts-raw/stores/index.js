import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { rmEntryRepository, tankRepository, materialRepository, supplierRepository } from '@/repositories'
import transactionRmEntryApi from '../services/index.js'
import { useToastStore } from '@/stores/toast.js'
import { registerCacheResetCallback } from '@/stores/plant.js'
import { useTransactionList } from '@/composables/useTransactionList.js'

export const useTsRawRmEntryStore = defineStore('transactionRmEntry', () => {
  const toastStore = useToastStore()

  // Repository instances
  const rmEntryRepo = rmEntryRepository
  const tankRepo = tankRepository
  const materialRepo = materialRepository
  const supplierRepo = supplierRepository

  // Use the composable for the main list
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
    (params) => rmEntryRepo.getList(params),
    { listKey: 'entries' }
  )

  async function fetchEntries(params = {}, force = false) {
    return doFetchList(params, force)
  }

  const feedLoading = ref(false)
  const tanks = ref([])
  const tankDetails = ref([])
  const materials = ref([])
  const suppliers = ref([])
  const supplierList = ref([])
  const manufacturers = ref([])
  const currentEntry = ref(null)
  const rmNumber = ref('')
  const trfNumber = ref('')
  const totalQty = ref('0.000')
  const storageLogs = ref([])
  const feedLogs = ref([])
  // Transfer state (moved from ts-transfer)
  const transferList = ref([])
  const destTanks = ref([])

  const STALE_TIME = 30 * 1000
  const _cache = { materials: 0, tanks: 0 }
  function _isFresh(key) { return Date.now() - (_cache[key] || 0) < STALE_TIME }
  function _touch(key) { _cache[key] = Date.now() }
  function resetCache() { 
    resetListCache()
    Object.keys(_cache).forEach(k => { _cache[k] = 0 })
    manufacturers.value = []
    tanks.value = []
    tankDetails.value = []
    materials.value = []
    suppliers.value = []
    feedLogs.value = []
    storageLogs.value = []
    transferList.value = []
    destTanks.value = []
  }

  // Register cache reset for plant change
  registerCacheResetCallback(resetCache)

  async function createEntry(data) {
    loading.value = true
    try {
      // Validate stock synchronization before submission
      if (data.rm_number && data.total_qty > 0) {
        const stockCheck = await validateStockSynchronization(data.rm_number, data.id_material)
        if (!stockCheck.valid) {
          throw new Error(stockCheck.message || 'Stock synchronization validation failed')
        }
      }

      const response = await rmEntryRepo.save(data)
      toastStore.success('RM Entry created successfully')
      _cache.entries = 0
      return response
    } catch (error) {
      toastStore.error('Failed to create RM entry:')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function prepareEdit(id) {
    loading.value = true
    try {
      const response = await rmEntryRepo.getById(id)
      return response
    } catch (error) {
      toastStore.error('Failed to fetch RM entry for edit:')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deactivateEntry(id) {
    loading.value = true
    try {
      const response = await rmEntryRepo.deactivate(id)
      toastStore.success('RM Entry deactivated successfully')
      _cache.entries = 0
      return response
    } catch (error) {
      toastStore.error('Failed to deactivate RM entry:')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateEntry(id, data) {
    loading.value = true
    try {
      const response = await rmEntryRepo.update(id, data)
      toastStore.success('RM Entry updated successfully')
      _cache.entries = 0
      return response
    } catch (error) {
      toastStore.error('Failed to update RM entry:')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function generateRmNumber(params = {}) {
    try {
      const response = await rmEntryRepo.generateRmNumber(params)
      rmNumber.value = response.rm_number
      return response
    } catch (error) {
      toastStore.error('Failed to generate RM number:')
      throw error
    }
  }

  async function generateTransferNumber(params = {}) {
    try {
      const response = await rmEntryRepo.generateTransferNumber(params)
      trfNumber.value = response.rm_number
      return response
    } catch (error) {
      toastStore.error('Failed to generate transfer number:')
      throw error
    }
  }

  async function fetchTanks(params = {}, force = false) {
    if (!force && _isFresh('tanks') && tanks.value.length > 0) return
    try {
      const response = await tankRepo.getAvailable(params)
      tanks.value = response || []
      _touch('tanks')
      return response
    } catch (error) {
      tanks.value = []
    }
  }

  async function fetchTankDetails(tankId, plantId = null) {
    try {
      const params = plantId ? { id_plant: plantId } : {}
      const response = await tankRepo.getDetails(tankId)
      tankDetails.value = response || []
      return response
    } catch (error) {
      toastStore.error('Failed to fetch tank details:')
      throw error
    }
  }

  async function fetchMaterials(force = false) {
    if (!force && _isFresh('materials') && materials.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.getMaterials()
      const list = response?.data ?? response ?? []
      materials.value = Array.isArray(list) ? list : []
      _touch('materials')
      return materials.value
    } catch (error) {
      materials.value = []
    }
  }

  async function fetchManufacturers(force = false) {
    if (!force && manufacturers.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.getActiveManufacturers()
      const list = response?.data ?? response ?? []
      manufacturers.value = Array.isArray(list) ? list : []
      return manufacturers.value
    } catch (error) {
      manufacturers.value = []
    }
  }

  async function searchSuppliers(query, force = false) {
    if (!force && !query && suppliers.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.searchSuppliers(query || '')
      // endpoint returns { success, data: [{id, text}] }
      const list = response?.data ?? response ?? []
      suppliers.value = Array.isArray(list) ? list : []
      return suppliers.value
    } catch (error) {
      suppliers.value = []
    }
  }

  async function generateBatchCode(supplierId) {
    try {
      const response = await rmEntryRepo.generateBatchCode(supplierId)
      return response.batch_code
    } catch (error) {
      toastStore.error('Failed to generate batch code:')
      throw error
    }
  }

  async function addSupplier(data) {
    try {
      const response = await transactionRmEntryApi.addSupplier(data)
      toastStore.success('Supplier added successfully')
      await fetchSupplierList(data.entry_no)
      await fetchTotalQty(data.entry_no)
      return response
    } catch (error) {
      toastStore.error('Failed to add supplier:')
      throw error
    }
  }

  async function fetchSupplierList(entryNo) {
    try {
      const response = await transactionRmEntryApi.getSupplierList(entryNo)
      supplierList.value = response.data || []
      return response
    } catch (error) {
      toastStore.error('Failed to fetch supplier list:')
      throw error
    }
  }

  async function deleteSupplier(id, entryNo) {
    try {
      const response = await transactionRmEntryApi.deleteSupplier(id)
      toastStore.success('Supplier deleted successfully')
      await fetchSupplierList(entryNo)
      await fetchTotalQty(entryNo)
      return response
    } catch (error) {
      toastStore.error('Failed to delete supplier:')
      throw error
    }
  }

  async function clearTempList(entryNo) {
    try {
      const response = await transactionRmEntryApi.clearTempSuppliers(entryNo)
      await fetchSupplierList(entryNo)
      await fetchTotalQty(entryNo)
      return response
    } catch (error) {
      toastStore.error('Failed to clear temp list')
      throw error
    }
  }

  async function fetchTotalQty(entryNo) {
    try {
      const response = await transactionRmEntryApi.getTotalQty(entryNo)
      totalQty.value = response.data.total
      return response
    } catch (error) {
      toastStore.error('Failed to fetch total qty:')
      throw error
    }
  }

  async function transferEntry(data, refreshParams = {}) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.transfer(data)
      toastStore.success('RM Transfer processed successfully')
      _cache.entries = 0
      return response
    } catch (error) {
      toastStore.error('Failed to process RM transfer:')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function validateStockSynchronization(entryNo, materialId) {
    try {
      // Check if there are temporary records for this entry
      await fetchSupplierList(entryNo)
      await fetchTotalQty(entryNo)

      const tempTotal = parseFloat(String(totalQty.value ?? '0').replace(/,/g, ''))

      if (supplierList.value.length === 0) {
        return {
          valid: false,
          message: 'No supplier data found. Please add supplier information first.'
        }
      }

      if (tempTotal <= 0) {
        return {
          valid: false,
          message: 'Total quantity must be greater than 0. Please check supplier quantities.'
        }
      }

      return { valid: true }
    } catch (error) {
      return {
        valid: false,
        message: 'Stock validation failed: ' + (error.message || 'Unknown error')
      }
    }
  }

  function resetForm() {
    currentEntry.value = null
    rmNumber.value = ''
    trfNumber.value = ''
    supplierList.value = []
    totalQty.value = '0.000'
  }

  // Storage and Feed Log Actions (moved from ts-transfer)
  async function fetchStorageLogs(params = {}) {
    loading.value = true
    try {
      const response = await rmEntryRepo.getStorageLog(params.id_plant || 0)
      storageLogs.value = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : [])
    } catch (error) {
      storageLogs.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchFeedLogs(params = {}) {
    feedLoading.value = true
    try {
      const response = await rmEntryRepo.getFeedLog(params)
      feedLogs.value = Array.isArray(response) ? response : (response && Array.isArray(response.data) ? response.data : [])
    } catch (error) {
      feedLogs.value = []
    } finally {
      feedLoading.value = false
    }
  }

  async function fetchTransferList(plant = 0) {
    loading.value = true
    try {
      const response = await rmEntryRepo.getTransferList(plant)
      transferList.value = Array.isArray(response) ? response : []
    } catch (err) {
      transferList.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchDestTanks(params = {}) {
    loading.value = true
    try {
      const response = await rmEntryRepo.getDestTanks(params.id_plant || 0)
      destTanks.value = Array.isArray(response) ? response : []
    } catch (err) {
      destTanks.value = []
    } finally {
      loading.value = false
    }
  }

  async function performTransfer(data) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.transfer(data)
      return response
    } catch (err) {
      toastStore.error('Failed to perform transfer:')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteTransfer(id) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.deactivateTransfer(id)
      return response
    } catch (err) {
      toastStore.error('Failed to delete transfer:')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteFeedLog(id) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.deactivateFeedLog(id)
      return response
    } catch (err) {
      toastStore.error('Failed to delete feed log entry:')
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    entries,
    loading,
    feedLoading,
    tanks,
    tankDetails,
    materials,
    suppliers,
    supplierList,
    manufacturers,
    currentEntry,
    rmNumber,
    trfNumber,
    totalQty,
    storageLogs,
    feedLogs,
    transferList,
    destTanks,
    pagination,

    // Getters
    entriesCount,
    hasEntries,

    // Actions
    fetchEntries,
    createEntry,
    updateEntry,
    prepareEdit,
    deactivateEntry,
    generateRmNumber,
    generateTransferNumber,
    fetchTanks,
    fetchTankDetails,
    fetchMaterials,
    fetchManufacturers,
    searchSuppliers,
    generateBatchCode,
    addSupplier,
    fetchSupplierList,
    deleteSupplier,
    clearTempList,
    fetchTotalQty,
    transferEntry,
    validateStockSynchronization,
    resetForm,
    resetCache,
    fetchStorageLogs,
    fetchFeedLogs,
    fetchTransferList,
    fetchDestTanks,
    performTransfer,
    deleteTransfer,
    deleteFeedLog
  }
})

