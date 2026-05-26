import api from '@/api/axios'

const RAW_URL = '/api/v1/transactions/rm-entries'

export default {
  async getTransferList(plantId = 0) {
    const response = await api.get(`${RAW_URL}/transfers`, {
      params: { id_plant: plantId }
    })
    return response.data
  },

  async getSpecificTankRundown(sloc) {
    const response = await api.get(`${RAW_URL}/tanks/${encodeURIComponent(sloc)}/details`)
    return response.data
  },

  async postMatlDocNumber(mode, id, number) {
    const formData = new FormData()
    formData.append('mode', mode)
    formData.append('id', id)
    formData.append('number', number)
    const response = await api.post(`${RAW_URL}/matl-doc`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async postUpdateEntrySubTank(idHead, idTankTail) {
    const formData = new FormData()
    formData.append('id_head', idHead)
    formData.append('id_tank_tail', idTankTail)
    const response = await api.post(`${RAW_URL}/update-sub-tank`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async deactivateTransfer(id) {
    const response = await api.delete(`${RAW_URL}/transfers/${id}`)
    return response.data
  }
}
