import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { RmEntryRepository, TankRepository, MaterialRepository, SupplierRepository } from '@/repositories'
import transactionRmEntryApi from '../api'
import { useToastStore } from '@/stores/toast'
import api from '@/api/axios'

export const useTransactionRmEntryStore = defineStore('transactionRmEntry', () => {
  const toastStore = useToastStore()

  // Repository instances
  const rmEntryRepo = RmEntryRepository
  const tankRepo = TankRepository
  const materialRepo = MaterialRepository
  const supplierRepo = SupplierRepository

  // State
  const entries = ref([])
  const loading = ref(false)
  const tanks = ref([])
  const tankDetails = ref([])
  const materials = ref([])
  const suppliers = ref([])
  const supplierList = ref([])
  const currentEntry = ref(null)
  const rmNumber = ref('')
  const trfNumber = ref('')
  const totalQty = ref('0.000')

  // Getters
  const entriesCount = computed(() => entries.value.length)
  const hasEntries = computed(() => entries.value.length > 0)

  // Actions
  async function fetchEntries(params = {}) {
    loading.value = true
    try {
      const response = await rmEntryRepo.getList(params)
      entries.value = response || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch RM entries')
      throw error
    } finally {
      loading.value = false
    }
  }

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
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to create RM entry')
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
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to deactivate RM entry')
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
      toastStore.error(error.response?.data?.message || 'Failed to generate RM number')
      throw error
    }
  }

  async function generateTransferNumber(params = {}) {
    try {
      const response = await rmEntryRepo.generateTransferNumber(params)
      trfNumber.value = response.rm_number
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to generate transfer number')
      throw error
    }
  }

  async function fetchTanks(params = {}, force = false) {
    if (!force && tanks.value.length > 0) return
    try {
      const response = await tankRepo.getAvailable(params)
      tanks.value = response || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch tanks')
      throw error
    }
  }

  async function fetchTankDetails(tankId, plantId = null) {
    try {
      const params = plantId ? { id_plant: plantId } : {}
      const response = await tankRepo.getDetails(tankId)
      tankDetails.value = response || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch tank details')
      throw error
    }
  }

  async function fetchMaterials(force = false) {
    if (!force && materials.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.getMaterials()
      // endpoint returns { success, data: [{id_material, material}] }
      const list = response?.data ?? response ?? []
      materials.value = Array.isArray(list) ? list : []
      return materials.value
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch materials')
      throw error
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
      toastStore.error(error.response?.data?.message || 'Failed to search suppliers')
      throw error
    }
  }

  async function generateBatchCode(supplierId) {
    try {
      const response = await rmEntryRepo.generateBatchCode(supplierId)
      return response.batch_code
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to generate batch code')
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
      toastStore.error(error.response?.data?.message || 'Failed to add supplier')
      throw error
    }
  }

  async function fetchSupplierList(entryNo) {
    try {
      const response = await transactionRmEntryApi.getSupplierList(entryNo)
      supplierList.value = response.data || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch supplier list')
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
      toastStore.error(error.response?.data?.message || 'Failed to delete supplier')
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
      console.error('Failed to clear temp list', error)
      throw error
    }
  }

  async function fetchTotalQty(entryNo) {
    try {
      const response = await transactionRmEntryApi.getTotalQty(entryNo)
      totalQty.value = response.data.total
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch total qty')
      throw error
    }
  }

  async function transferEntry(data, refreshParams = {}) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.transfer(data)
      toastStore.success('RM Transfer processed successfully')
      await fetchEntries(refreshParams)
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to process RM transfer')
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

      const tempTotal = parseFloat(totalQty.value.replace(/,/g, ''))

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
    tankDetails.value = []
  }

  return {
    // State
    entries,
    loading,
    tanks,
    tankDetails,
    materials,
    suppliers,
    supplierList,
    currentEntry,
    rmNumber,
    trfNumber,
    totalQty,

    // Getters
    entriesCount,
    hasEntries,

    // Actions
    fetchEntries,
    createEntry,
    deactivateEntry,
    generateRmNumber,
    generateTransferNumber,
    fetchTanks,
    fetchTankDetails,
    fetchMaterials,
    searchSuppliers,
    generateBatchCode,
    addSupplier,
    fetchSupplierList,
    deleteSupplier,
    clearTempList,
    fetchTotalQty,
    transferEntry,
    validateStockSynchronization,
    resetForm
  }
})

export const useTransactionTransferStore = defineStore('transactionTransfer', {
  state: () => ({
    storageLogs: [],
    feedLogs: [],
    sourceEntries: [],
    destTanks: [],
    tankDetails: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchStorageLogs(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/storage-log', { params })
        const rows = response.data?.data
        this.storageLogs = Array.isArray(rows) ? rows : []
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchFeedLogs(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/feed-log', { params })
        const rows = response.data?.data
        this.feedLogs = Array.isArray(rows) ? rows : []
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchSourceEntries() {
      try {
        const response = await api.get('/api/v1/transactions/transfers/source-entries')
        this.sourceEntries = response.data.data
      } catch (error) {
        console.error('Fetch source entries error:', error)
      }
    },

    async fetchDestTanks(params = {}) {
      try {
        const response = await api.get('/api/v1/transactions/transfers/dest-tanks', { params })
        this.destTanks = response.data.data
      } catch (error) {
        console.error('Fetch dest tanks error:', error)
      }
    },

    async fetchTankDetails(tankId, plantId = null) {
      try {
        const params = plantId ? { id_plant: plantId } : {}
        const response = await api.get(`/api/v1/transactions/rm-entries/tanks/${encodeURIComponent(tankId)}/details`, { params })
        this.tankDetails = response.data.data
      } catch (error) {
        console.error('Fetch tank details error:', error)
      }
    },

    async performTransfer(data) {
      this.loading = true
      try {
        const response = await api.post('/api/v1/transactions/transfers', data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteTransfer(id) {
      this.loading = true
      try {
        const response = await api.delete(`/api/v1/transactions/transfers/${id}`)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
