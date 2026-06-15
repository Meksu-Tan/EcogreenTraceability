import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/wip-entries'

export default {
  // GET - main page data
  async getIndex(params = {}) {
    const res = await api.get(BASE_URL, { params })
    return res.data
  },

  // POST methods
  async storeFeed(data) {
    const res = await api.post(`${BASE_URL}/feed`, data)
    return res.data
  },

  async storeRundown(data) {
    const res = await api.post(`${BASE_URL}/rundown`, data)
    return res.data
  },

  async cancelFeed(traceNo, id_plant) {
    const res = await api.post(`${BASE_URL}/feed/cancel`, { traceNo, id_plant })
    return res.data
  },

  async cancelRundown(traceNo, id_plant) {
    const res = await api.post(`${BASE_URL}/rundown/cancel`, { traceNo, id_plant })
    return res.data
  },

  async postMaterialDocument(mode, idTraceHead, number) {
    const res = await api.post(`${BASE_URL}/matl-doc`, { mode, id: idTraceHead, number })
    return res.data
  },

  async updateSubTank(idHead, idTankTail) {
    const res = await api.post(`${BASE_URL}/update-sub-tank`, { idHead, idTankTail })
    return res.data
  },

  // DELETE
  async deactivate(id) {
    const res = await api.delete(`${BASE_URL}/${id}`)
    return res.data
  },

  // Generic store() placeholder for compatibility if used loosely
  async store(data) {
    switch(data.flag) {
      case 'post_materialFeed': return this.storeFeed(data);
      case 'post_materialRundown': return this.storeRundown(data);
      case 'post_cancelFeed': return this.cancelFeed(data.traceNo, data.id_plant);
      case 'post_cancelRundown': return this.cancelRundown(data.traceNo, data.id_plant);
      case 'post_matlDocNumber': return this.postMaterialDocument(data.mode, data.id, data.number);
      case 'post_updateEntrySubTank': return this.updateSubTank(data.idHead, data.idTankTail);
      default: return api.post(BASE_URL, data).then(r => r.data);
    }
  },

  // GET methods
  async getBalance(rundownId, params = {}) {
    const res = await api.get(`${BASE_URL}/balance`, { params: { rundownId, per_page: 5, ...params } })
    return res.data
  },

  async getFeed(feedId, mode = 'LATEST', params = {}) {
    const res = await api.get(`${BASE_URL}/feed`, { params: { feedId, mode, per_page: 5, ...params } })
    return res.data
  },

  async getRundown(rundownId, mode = 'LATEST', params = {}) {
    const res = await api.get(`${BASE_URL}/rundown`, { params: { rundownId, mode, per_page: 5, ...params } })
    return res.data
  },

  async getFeedNewBatchNumber(feedID, params = {}) {
    const res = await api.get(`${BASE_URL}/feed/new-batch`, { params: { feedID, ...params } })
    return res.data?.data
  },

  async getRundownNewBatchNumber(rundownID, params = {}) {
    const res = await api.get(`${BASE_URL}/rundown/new-batch`, { params: { rundownID, ...params } })
    return res.data?.data
  },

  async getNewFeedNumber(feedId, params = {}) {
    const res = await api.get(`${BASE_URL}/feed/new-number`, { params: { feedId, ...params } })
    return res.data?.data
  },

  async getNewRundownNumber(rundownId, params = {}) {
    const res = await api.get(`${BASE_URL}/rundown/new-number`, { params: { rundownId, ...params } })
    return res.data?.data
  },

  async getFeedLastBatch(feedID, params = {}) {
    const res = await api.get(`${BASE_URL}/feed/last-batch`, { params: { feedID, ...params } })
    return res.data?.data
  },

  async getRundownLastBatch(rundownID, params = {}) {
    const res = await api.get(`${BASE_URL}/rundown/last-batch`, { params: { rundownID, ...params } })
    return res.data?.data
  },

  async getActiveTanksFeed(feedID, params = {}) {
    const res = await api.get(`${BASE_URL}/tanks/feed`, { params: { feedID, ...params } })
    return res.data?.data
  },

  async getActiveTanksRundown(rundownID, params = {}) {
    const res = await api.get(`${BASE_URL}/tanks/rundown`, { params: { rundownID, ...params } })
    return res.data?.data
  },

  async getActiveSpecificTanks(sloc, params = {}) {
    const res = await api.get(`${BASE_URL}/tanks/specific`, { params: { sloc, ...params } })
    return res.data?.data
  },

  async getQuantifierData(date, tagNumber, params = {}) {
    const res = await api.get(`${BASE_URL}/quantifier`, { params: { date, tagNumber, ...params } })
    return res.data?.data
  },

  async getTsWipTree(params = {}) {
    const res = await api.get(`${BASE_URL}/tree`, { params })
    return res.data?.data || res.data
  },
}
