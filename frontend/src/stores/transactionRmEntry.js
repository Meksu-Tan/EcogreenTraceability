import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import transactionRmEntryApi from '@/api/transactionRmEntry'
import { useToastStore } from './toast'

export const useTransactionRmEntryStore = defineStore('transactionRmEntry', () => {
  const toastStore = useToastStore()

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
      const response = await transactionRmEntryApi.getList(params)
      entries.value = response.data || []
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
      const response = await transactionRmEntryApi.create(data)
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

  async function updateEntry(id, data) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.update(id, data)
      toastStore.success('RM Entry updated successfully')
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to update RM entry')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deactivateEntry(id) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.deactivate(id)
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
      const response = await transactionRmEntryApi.getNewNumber(params)
      rmNumber.value = response.data.rm_number
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to generate RM number')
      throw error
    }
  }

  async function generateTransferNumber(params = {}) {
    try {
      const response = await transactionRmEntryApi.getTransferNumber(params)
      trfNumber.value = response.data.rm_number
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to generate transfer number')
      throw error
    }
  }

  async function fetchTanks(force = false) {
    if (!force && tanks.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.getTanks()
      tanks.value = response.data || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch tanks')
      throw error
    }
  }

  async function fetchTankDetails(tankId) {
    try {
      const response = await transactionRmEntryApi.getTankDetails(tankId)
      tankDetails.value = response.data || []
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
      materials.value = response.data || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch materials')
      throw error
    }
  }

  async function searchSuppliers(query, force = false) {
    if (!force && !query && suppliers.value.length > 0) return
    try {
      const response = await transactionRmEntryApi.searchSuppliers(query)
      suppliers.value = Array.isArray(response) ? response : []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to search suppliers')
      throw error
    }
  }

  async function generateBatchCode(supplierId) {
    try {
      const response = await transactionRmEntryApi.getBatchCode(supplierId)
      return response.data.batch_code
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to generate batch code')
      throw error
    }
  }

  async function addSupplier(data) {
    try {
      const response = await transactionRmEntryApi.addSupplier(data)
      toastStore.success('Supplier added successfully')
      const params = {}
      if (data.mode === 'UPDATE') {
        params.mode = 'UPDATE'
        params.id_balance_head = data.idHead
      }
      await fetchSupplierList(data.entry_no, params)
      await fetchTotalQty(data.entry_no, params)
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to add supplier')
      throw error
    }
  }

  async function fetchSupplierList(entryNo, params = {}) {
    try {
      const response = await transactionRmEntryApi.getSupplierList(entryNo, params)
      supplierList.value = response.data || []
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch supplier list')
      throw error
    }
  }

  async function deleteSupplier(id, entryNo, params = {}) {
    try {
      const response = await transactionRmEntryApi.deleteSupplier(id, params)
      toastStore.success('Supplier deleted successfully')
      await fetchSupplierList(entryNo, params)
      await fetchTotalQty(entryNo, params)
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to delete supplier')
      throw error
    }
  }

  async function fetchTotalQty(entryNo, params = {}) {
    try {
      const response = await transactionRmEntryApi.getTotalQty(entryNo, params)
      totalQty.value = response.data.total
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to fetch total qty')
      throw error
    }
  }

  async function transferEntry(data) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.transfer(data)
      toastStore.success('RM Transfer processed successfully')
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.error(error.response?.data?.message || 'Failed to process RM transfer')
      throw error
    } finally {
      loading.value = false
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
    updateEntry,
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
    fetchTotalQty,
    transferEntry,
    resetForm
  }
})
