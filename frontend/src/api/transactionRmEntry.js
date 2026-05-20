import axios from './axios'

const BASE_URL = '/api/v1/transactions/rm-entries'

export default {
  // Get RM Entry list
  async getList(params = {}) {
    const response = await axios.get(BASE_URL, { params })
    return response.data
  },

  // Create new RM Entry
  async create(data) {
    const response = await axios.post(BASE_URL, data)
    return response.data
  },

  // Deactivate RM Entry
  async deactivate(id) {
    const response = await axios.delete(`${BASE_URL}/${id}`)
    return response.data
  },

  // Generate new RM number
  async getNewNumber(params = {}) {
    const response = await axios.get(`${BASE_URL}/new-number`, { params })
    return response.data
  },

  // Get storage tanks
  async getTanks(params = {}) {
    const response = await axios.get(`${BASE_URL}/tanks`, { params })
    return response.data
  },

  // Get tank details (sub tanks)
  async getTankDetails(tankId) {
    const response = await axios.get(`${BASE_URL}/tanks/${tankId}/details`)
    return response.data
  },

  // Get RM materials
  async getMaterials() {
    const response = await axios.get(`${BASE_URL}/materials`)
    return response.data
  },

  // Search suppliers
  async searchSuppliers(query) {
    const response = await axios.get(`${BASE_URL}/suppliers/search`, {
      params: { q: query }
    })
    return response.data
  },

  // Generate batch code
  async getBatchCode(supplierId) {
    const response = await axios.get(`${BASE_URL}/batch-code`, {
      params: { id_supplier: supplierId }
    })
    return response.data
  },

  // Add supplier to temporary
  async addSupplier(data) {
    const response = await axios.post(`${BASE_URL}/suppliers`, data)
    return response.data
  },

  // Get supplier list from temporary
  async getSupplierList(entryNo) {
    const response = await axios.get(`${BASE_URL}/suppliers/list`, {
      params: { entry_no: entryNo }
    })
    return response.data
  },

  // Delete supplier from temporary
  async deleteSupplier(id) {
    const response = await axios.delete(`${BASE_URL}/suppliers/${id}`)
    return response.data
  },

  // Get total qty from temporary
  async getTotalQty(entryNo) {
    const response = await axios.get(`${BASE_URL}/total-qty`, {
      params: { entry_no: entryNo }
    })
    return response.data
  },

  // Transfer RM to Feed Tank
  async transfer(data) {
    const response = await axios.post(`${BASE_URL}/transfer`, data)
    return response.data
  },

  // Generate new transfer number
  async getTransferNumber(params = {}) {
    const response = await axios.get(`${BASE_URL}/transfer-number`, { params })
    return response.data
  }
}

