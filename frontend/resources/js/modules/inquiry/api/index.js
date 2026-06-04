import api from '@/api/axios'

export default {
  // Stock Inquiry
  getStock(params = {}) {
    return api.get('/api/v1/inquiries/stock', { params })
  },

  getStockById(id) {
    return api.get(`/api/v1/inquiries/stock/${id}`)
  },

  // TS Report
  getTsReport(params = {}) {
    return api.get('/api/v1/inquiries/ts-report', { params })
  },

  // RM Report
  getRmReport(params = {}) {
    return api.get('/api/v1/inquiries/rm-report', { params })
  },

  // PSPA Report
  getPsPaReportList(params = {}) {
    return api.get('/api/v1/inquiries/pspa-report', { params })
  },

  getPsPaReportDetail(id, params = {}) {
    return api.get(`/api/v1/inquiries/pspa-report/${id}`, { params })
  },

  getMaterialStock(params = {}) {
    return api.get('/api/v1/inquiries/pspa-report/material-stock', { params })
  },

  generatePsPaReport(payload) {
    return api.post('/api/v1/inquiries/pspa-report', payload)
  },

  calculatePsPaReport(id) {
    return api.post(`/api/v1/inquiries/pspa-report/calculate/${id}`)
  },

  approvePsPaReport(id) {
    return api.post(`/api/v1/inquiries/pspa-report/approve/${id}`)
  },
}
