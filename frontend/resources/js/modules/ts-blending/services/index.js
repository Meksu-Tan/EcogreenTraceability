import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/blendings'

export default {
  async getList(params = {}) {
    const response = await api.get(BASE_URL, { params })
    return response.data
  },

  async storeMaterial(data) {
    const response = await api.post(`${BASE_URL}/material`, data)
    return response.data
  },

  async executeBlending(data) {
    const response = await api.post(`${BASE_URL}/execute`, data)
    return response.data
  },

  async createMatlDoc(data) {
    const response = await api.post(`${BASE_URL}/matl-doc`, data)
    return response.data
  },

  async updateSubTank(data) {
    const response = await api.post(`${BASE_URL}/update-sub-tank`, data)
    return response.data
  },

  async deleteMaterial(id) {
    const response = await api.delete(`${BASE_URL}/material/${id}`)
    return response.data
  },

  async deactivate(id) {
    const response = await api.delete(`${BASE_URL}/${id}`)
    return response.data
  },

  async getActiveMaterials() {
    const response = await api.get(`${BASE_URL}/active-materials`)
    return response.data
  },

  async getNewEntryNo(params = {}) {
    const response = await api.get(`${BASE_URL}/new-entry-no`, { params })
    return response.data
  },

  async getTotalStockMaterial(params = {}) {
    const response = await api.get(`${BASE_URL}/total-stock-material`, { params })
    return response.data
  },

  async getTotalQtyMaterial(params = {}) {
    const response = await api.get(`${BASE_URL}/total-qty-material`, { params })
    return response.data
  },

  async getMaterialList(params = {}) {
    const response = await api.get(`${BASE_URL}/material-list`, { params })
    return response.data
  },

  async getActiveTanksRundown(params = {}) {
    const response = await api.get(`${BASE_URL}/active-tanks-rundown`, { params })
    return response.data
  },

  async getActiveSpecificTanksRundown(params = {}) {
    const response = await api.get(`${BASE_URL}/active-specific-tanks-rundown`, { params })
    return response.data
  },

  async getTanks(params = {}) {
    const response = await api.get(`${BASE_URL}/tanks`, { params })
    return response.data
  },

  async getTankDetails(tankId, params = {}) {
    const response = await api.get(`${BASE_URL}/tanks/${tankId}/details`, { params })
    return response.data
  },

  async getAllTanks(params = {}) {
    const response = await api.get(`${BASE_URL}/all-tanks`, { params })
    return response.data
  }
}
