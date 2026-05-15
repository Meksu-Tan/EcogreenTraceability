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
      toastStore.showError(error.response?.data?.message || 'Failed to fetch RM entries')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createEntry(data) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.create(data)
      toastStore.showSuccess('RM Entry created successfully')
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to create RM entry')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deactivateEntry(id) {
    loading.value = true
    try {
      const response = await transactionRmEntryApi.deactivate(id)
      toastStore.showSuccess('RM Entry deactivated successfully')
      await fetchEntries()
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to deactivate RM entry')
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
      toastStore.showError(error.response?.data?.message || 'Failed to generate RM number')
      throw error
    }
  }

  async function fetchTanks() {
    try {
      const response = await transactionRmEntryApi.getTanks()
      tanks.value = response.data || []
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to fetch tanks')
      throw error
    }
  }

  async function fetchTankDetails(tankId) {
    try {
      const response = await transactionRmEntryApi.getTankDetails(tankId)
      tankDetails.value = response.data || []
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to fetch tank details')
      throw error
    }
  }

  async function fetchMaterials() {
    try {
      const response = await transactionRmEntryApi.getMaterials()
      materials.value = response.data || []
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to fetch materials')
      throw error
    }
  }

  async function searchSuppliers(query) {
    try {
      const response = await transactionRmEntryApi.searchSuppliers(query)
      suppliers.value = response || []
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to search suppliers')
      throw error
    }
  }

  async function generateBatchCode(supplierId) {
    try {
      const response = await transactionRmEntryApi.getBatchCode(supplierId)
      return response.data.batch_code
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to generate batch code')
      throw error
    }
  }

  async function addSupplier(data) {
    try {
      const response = await transactionRmEntryApi.addSupplier(data)
      toastStore.showSuccess('Supplier added successfully')
      await fetchSupplierList(data.entry_no)
      await fetchTotalQty(data.entry_no)
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to add supplier')
      throw error
    }
  }

  async function fetchSupplierList(entryNo) {
    try {
      const response = await transactionRmEntryApi.getSupplierList(entryNo)
      supplierList.value = response.data || []
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to fetch supplier list')
      throw error
    }
  }

  async function deleteSupplier(id, entryNo) {
    try {
      const response = await transactionRmEntryApi.deleteSupplier(id)
      toastStore.showSuccess('Supplier deleted successfully')
      await fetchSupplierList(entryNo)
      await fetchTotalQty(entryNo)
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to delete supplier')
      throw error
    }
  }

  async function fetchTotalQty(entryNo) {
    try {
      const response = await transactionRmEntryApi.getTotalQty(entryNo)
      totalQty.value = response.data.total
      return response
    } catch (error) {
      toastStore.showError(error.response?.data?.message || 'Failed to fetch total qty')
      throw error
    }
  }

  function resetForm() {
    currentEntry.value = null
    rmNumber.value = ''
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
    totalQty,

    // Getters
    entriesCount,
    hasEntries,

    // Actions
    fetchEntries,
    createEntry,
    deactivateEntry,
    generateRmNumber,
    fetchTanks,
    fetchTankDetails,
    fetchMaterials,
    searchSuppliers,
    generateBatchCode,
    addSupplier,
    fetchSupplierList,
    deleteSupplier,
    fetchTotalQty,
    resetForm
  }
})
