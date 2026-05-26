import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/blendings'

export default {
  async getList(params = {}) {
    const response = await api.get(BASE_URL, { params })
    return response.data
  },

  async store(data) {
    const response = await api.post(BASE_URL, data)
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
  }
}
