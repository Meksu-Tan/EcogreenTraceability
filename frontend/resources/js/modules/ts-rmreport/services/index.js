import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/rm-report'

export default {
  getRmReport(params = {}) {
    return api.get(BASE_URL, { params })
  },
  getRmReportSummary(params = {}) {
    return api.get(`${BASE_URL}/summary`, { params })
  },
}
