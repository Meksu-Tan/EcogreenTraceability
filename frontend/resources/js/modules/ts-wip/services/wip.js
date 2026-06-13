import api from '@/api/axios'

const BASE_URL = '/api/v1/transactions/wip-entries'

export default {
  // GET - main page data
  async getIndex(params = {}) {
    const res = await api.get(BASE_URL, { params })
    return res.data
  },

  // POST - all write operations via flag
  async store(data) {
    const res = await api.post(BASE_URL, data)
    return res.data
  },

  // DELETE
  async deactivate(id) {
    const res = await api.delete(`${BASE_URL}/${id}`)
    return res.data
  },

  // GET - all read operations via flag (single generic endpoint)
  async getData(flag, params = {}) {
    const res = await api.get(`${BASE_URL}/${flag}`, { params: { flag, ...params } })
    return res.data
  },

  // Convenience methods
  async getBalance(rundownId, params = {}) {
    return this.getData('get_dtBalance', { rundownId, per_page: 5, ...params })
  },

  async getFeed(feedId, mode = 'LATEST', params = {}) {
    return this.getData('get_dtFeed', { feedId, mode, per_page: 5, ...params })
  },

  async getRundown(rundownId, mode = 'LATEST', params = {}) {
    return this.getData('get_dtRundown', { rundownId, mode, per_page: 5, ...params })
  },

  async getFeedNewBatchNumber(feedID, params = {}) {
    const res = await this.getData('get_feedNewBatchNumber', { feedID, ...params })
    return res.data
  },

  async getRundownNewBatchNumber(rundownID, params = {}) {
    const res = await this.getData('get_rundownNewBatchNumber', { rundownID, ...params })
    return res.data
  },

  async getNewFeedNumber(feedId, params = {}) {
    const res = await this.getData('get_newFeedNumber', { feedId, ...params })
    return res.data
  },

  async getNewRundownNumber(rundownId, params = {}) {
    const res = await this.getData('get_newRundownNumber', { rundownId, ...params })
    return res.data
  },


  async getFeedLastBatch(feedID, params = {}) {
    const res = await this.getData('get_feedLastBatch', { feedID, ...params })
    return res.data
  },

  async getRundownLastBatch(rundownID, params = {}) {
    const res = await this.getData('get_rundownLastBatch', { rundownID, ...params })
    return res.data
  },

  async getActiveTanksFeed(feedID, params = {}) {
    const res = await this.getData('get_cmbActiveTank_trf', { feedID, ...params })
    return res.data
  },

  async getActiveTanksRundown(rundownID, params = {}) {
    const res = await this.getData('get_cmbActiveTank_rundown', { rundownID, ...params })
    return res.data
  },

  async getActiveSpecificTanks(sloc, params = {}) {
    const res = await this.getData('get_cmbActiveSpecificTank_trf', { sloc, ...params })
    return res.data
  },

  async getQuantifierData(date, tagNumber, params = {}) {
    const res = await this.getData('get_quantifierData', { date, tagNumber, ...params })
    return res.data
  },

  // B8: Add getTsWipTree() - for dashboard/tree view
  async getTsWipTree(params = {}) {
    return this.getData('get_wipTree', params)
  },

  // G2: Material Document endpoint (handled via store flag)
  async postMaterialDocument(mode, idTraceHead, number) {
    return this.store({
      flag: 'post_matlDocNumber',
      mode,
      id: idTraceHead,
      number,
    })
  },

  // G3: Sub-Tank Update endpoint
  async updateSubTank(idHead, idTankTail) {
    return this.store({
      flag: 'post_updateEntrySubTank',
      idHead,
      idTankTail,
    })
  },
}
