import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/transfers'

export default {
  async getTransferList(plantId = 0) {
    const response = await api.get(BASE_URL, {
      params: { id_plant: plantId }
    })
    return response.data
  },

  async store(data) {
    const response = await api.post(BASE_URL, data)
    return response.data
  },

  async deactivateTransfer(id) {
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

  async getTotalStock(params = {}) {
    const response = await api.get(`${BASE_URL}/total-stock`, { params })
    return response.data
  },

  async getTanksRundown(params = {}) {
    const response = await api.get(`${BASE_URL}/tanks-rundown`, { params })
    return response.data
  },

  async getSpecificTanksRundown(params = {}) {
    const response = await api.get(`${BASE_URL}/specific-tanks-rundown`, { params })
    return response.data
  },

  async getSupplierCode(params = {}) {
    const response = await api.get(`${BASE_URL}/supplier-code`, { params })
    return response.data
  },

  async postMatlDocNumber(mode, id, number) {
    const response = await api.post(`${BASE_URL}/matl-doc`, { mode, id, number })
    return response.data
  },

  async postUpdateEntrySubTank(idHead, idTankTail) {
    const response = await api.post(`${BASE_URL}/update-sub-tank`, { idHead, idTankTail })
    return response.data
  }
}
