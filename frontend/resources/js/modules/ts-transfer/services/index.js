import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/transfers'

export default {
  async getTransferList(plantId = 0, page = 1, perPage = 5) {
    const response = await api.get(BASE_URL, {
      params: { id_plant: plantId, page, per_page: perPage }
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

  async postUpdateEntrySubTank(idHead, idSlocTail) {
    const response = await api.post(`${BASE_URL}/update-sub-tank`, { idHead, idSlocTail })
    return response.data
  },

  // ── Approval Workflow ──────────────────────────────────────────────

  async getPendingApprovals(params = {}) {
    const response = await api.get(`${BASE_URL}/approval/pending`, { params })
    return response.data
  },

  async submitForApproval(id, data = {}) {
    const response = await api.post(`${BASE_URL}/approval/submit`, { id, ...data })
    return response.data
  },

  async approveTransfer(id, data = {}) {
    const response = await api.post(`${BASE_URL}/approval/approve`, { id, ...data })
    return response.data
  },

  async rejectTransfer(id, reason = '') {
    const response = await api.post(`${BASE_URL}/approval/reject`, { id, reason })
    return response.data
  },

  async cancelTransfer(id, reason = '') {
    const response = await api.post(`${BASE_URL}/approval/cancel`, { id, reason })
    return response.data
  },

  async getApprovalHistory(params = {}) {
    const response = await api.get(`${BASE_URL}/approval/history`, { params })
    return response.data
  },

  async getPendingHistory(page = 1, perPage = 5) {
    const response = await api.get(`${BASE_URL}/approval/pending-history`, {
      params: { page, per_page: perPage }
    })
    return response.data
  }
}