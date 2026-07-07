import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/rm-entries'

export default {
  async getList(params = {}) {
    const response = await api.get(BASE_URL, { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`${BASE_URL}/${id}`)
    return response.data
  },

  async create(data) {
    const response = await api.post(BASE_URL, data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`${BASE_URL}/${id}`, data)
    return response.data
  },

  async deactivate(id) {
    const response = await api.delete(`${BASE_URL}/${id}`)
    return response.data
  },

  async getNewNumber(params = {}) {
    const response = await api.get(`${BASE_URL}/new-number`, { params })
    return response.data
  },

  async getTanks(params = {}) {
    const response = await api.get(`${BASE_URL}/tanks`, { params })
    return response.data
  },

  async getTankDetails(tankId, params = {}) {
    const response = await api.get(`${BASE_URL}/tanks/${encodeURIComponent(tankId)}/details`, { params })
    return response.data
  },

  async getMaterials() {
    const response = await api.get(`${BASE_URL}/materials`)
    return response.data
  },

  async searchSuppliers(query) {
    const response = await api.get(`${BASE_URL}/suppliers/search`, {
      params: { q: query }
    })
    return response.data
  },

  async getBatchCode(supplierId) {
    const response = await api.get(`${BASE_URL}/batch-code`, {
      params: { id_supplier: supplierId }
    })
    return response.data
  },

  async addSupplier(data) {
    const response = await api.post(`${BASE_URL}/suppliers`, data)
    return response.data
  },

  async getSupplierList(entryNo) {
    const response = await api.get(`${BASE_URL}/suppliers/list`, {
      params: { entry_no: entryNo }
    })
    return response.data
  },

  async deleteSupplier(id) {
    const response = await api.delete(`${BASE_URL}/suppliers/${id}`)
    return response.data
  },

  async clearTempSuppliers(entryNo) {
    const response = await api.delete(`${BASE_URL}/suppliers/clear/${entryNo}`)
    return response.data
  },

  async getTotalQty(entryNo) {
    const response = await api.get(`${BASE_URL}/total-qty`, {
      params: { entry_no: entryNo }
    })
    return response.data
  },

  async transfer(data) {
    const response = await api.post(`${BASE_URL}/transfer`, data)
    return response.data
  },

  async getTransferNumber(params = {}) {
    const response = await api.get(`${BASE_URL}/transfer-number`, { params })
    return response.data
  },

  async checkStockSync(params = {}) {
    const response = await api.get(`${BASE_URL}/stock-sync-check`, { params })
    return response.data
  },

  async debugFifoStock(params = {}) {
    const response = await api.get(`${BASE_URL}/debug-fifo-stock`, { params })
    return response.data
  },

  // Storage and Feed Log endpoints
  async getStorageLog(params = {}) {
    const response = await api.get(`${BASE_URL}/storage-log`, { params })
    return response.data
  },

  async getFeedLog(params = {}) {
    const response = await api.get(`${BASE_URL}/feed-log`, { params })
    return response.data
  },

  // Transfer endpoints
  async getTransferList(params = {}) {
    const response = await api.get(`${BASE_URL}/transfers`, { params })
    return response.data
  },

  async deactivateTransfer(id) {
    const response = await api.delete(`${BASE_URL}/transfers/${id}`)
    return response.data
  },

  async deactivateFeedLog(id) {
    const response = await api.delete(`${BASE_URL}/feed-log/${id}`)
    return response.data
  },

  async getDestTanks(plantId = null) {
    const response = await api.get(`${BASE_URL}/dest-tanks`, {
      params: { id_plant: plantId }
    })
    return response.data
  },

  async getActiveManufacturers() {
    const response = await api.get('/api/v1/manufacturers/active')
    return response.data
  },

  async updateSubTank(data) {
    const response = await api.post(`${BASE_URL}/update-sub-tank`, data)
    return response.data
  },

  async updateMatlDoc(data) {
    const response = await api.post(`${BASE_URL}/matl-doc`, data)
    return response.data
  },

  async getSpecificTanks(params = {}) {
    const response = await api.get(`${BASE_URL}/specific-tanks`, { params })
    return response.data
  }
}
