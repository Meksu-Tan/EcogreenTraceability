import api from '@/api/axios'

export default {
  getStock(params = {}) {
    return api.get('/api/v1/inquiries/stock', { params })
  },

  getStockById(id) {
    return api.get(`/api/v1/inquiries/stock/${id}`)
  },

  getTsReport(params = {}) {
    return api.get('/api/v1/inquiries/ts-report', { params })
  },

  getRmReport(params = {}) {
    return api.get('/api/v1/inquiries/rm-report', { params })
  }
}
