import api from './axios'

const BASE = '/api/v1/transactions/wip-entries'

export default {
  getBalance(rundownId, params = {}) {
    return api.get(`${BASE}/balance`, { params: { rundownId, ...params } })
  },

  getFeed(feedId, params = {}) {
    return api.get(`${BASE}/feed`, { params: { feedID: feedId, ...params } })
  },

  getRundown(rundownId, params = {}) {
    return api.get(`${BASE}/rundown`, { params: { rundownId, ...params } })
  },

  getOptions(option, params = {}) {
    return api.get(`${BASE}/options/${option}`, { params })
  },

  storeFeed(data) {
    return api.post(`${BASE}/feed`, data)
  },

  storeRundown(data) {
    return api.post(`${BASE}/rundown`, data)
  },

  cancelFeed(data) {
    return api.post(`${BASE}/cancel/feed`, data)
  },

  cancelRundown(data) {
    return api.post(`${BASE}/cancel/rundown`, data)
  },

  saveMaterialDoc(data) {
    return api.post('/api/v1/transactions/wip/material-document', data)
  },

  updateSubTank(data) {
    return api.post('/api/v1/transactions/wip/subtank', data)
  },
}
