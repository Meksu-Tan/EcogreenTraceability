import api from './axios'

export const legacyApi = {
  forwardList(params = {}) {
    return api.get('/api/v1/trace/forward', { params }).then((response) => response.data)
  },
  forwardTrace(id, params = {}) {
    return api.get(`/api/v1/trace/forward/${id}`, { params }).then((response) => response.data)
  },
  backwardList(params = {}) {
    return api.get('/api/v1/trace/backward', { params }).then((response) => response.data)
  },
  backwardTrace(id, params = {}) {
    return api.get(`/api/v1/trace/backward/${id}`, { params }).then((response) => response.data)
  },
  stockDetail(params = {}) {
    return api.get('/api/v1/inquiries/stock', { params }).then((response) => response.data)
  },
  stockSummary(params = {}) {
    return api.get('/api/v1/inquiries/stock/summary', { params }).then((response) => response.data)
  },
  stockMaterials(params = {}) {
    return api.get('/api/v1/inquiries/stock/materials', { params }).then((response) => response.data)
  },
  stockSloc(params = {}) {
    return api.get('/api/v1/inquiries/stock/sloc', { params }).then((response) => response.data)
  },
  tsReport(type = 'all', params = {}) {
    return api.get(`/api/v1/inquiries/ts-report/${type}`, { params }).then((response) => response.data)
  },
  rmReportSummary(params = {}) {
    return api.get('/api/v1/inquiries/rm-report', { params }).then((response) => response.data)
  },
  rmReportTank(params = {}) {
    return api.get('/api/v1/inquiries/rm-report/tank', { params }).then((response) => response.data)
  },
  rmReportAdjustmentOut(params = {}) {
    return api.get('/api/v1/inquiries/rm-report/adjustment-out', { params }).then((response) => response.data)
  },
  rmReportWarehouse(params = {}) {
    return api.get('/api/v1/inquiries/rm-report/warehouse', { params }).then((response) => response.data)
  },
  blendingList(params = {}) {
    return api.get('/api/v1/transactions/blendings', { params }).then((response) => response.data)
  },
  transferList(params = {}) {
    return api.get('/api/v1/transactions/transfers', { params }).then((response) => response.data)
  },
  wipFeed(params = {}) {
    return api.get('/api/v1/transactions/wip-entries/feed', { params }).then((response) => response.data)
  },
  wipRundown(params = {}) {
    return api.get('/api/v1/transactions/wip-entries/rundown', { params }).then((response) => response.data)
  },
  wipBalance(params = {}) {
    return api.get('/api/v1/transactions/wip-entries/balance', { params }).then((response) => response.data)
  },
}

export default legacyApi
