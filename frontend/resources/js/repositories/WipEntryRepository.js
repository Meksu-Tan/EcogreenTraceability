/**
 * @note Naming convention mismatch: this file lives in `repositories/` but should be in `services/`
 *       per project convention (View → Store → Service → Axios). Rename deferred due to risk.
 */
import wipApi from '@/modules/ts-wip/services/wip'

class WipEntryRepository {
  // Feed operations
  async getFeed(feedId, mode = 'LATEST', params = {}) {
    const res = await wipApi.getFeed(feedId, mode, params)
    return res.data?.data ?? res.data ?? res
  }

  async getFeedNewBatchNumber(feedId, params = {}) {
    const res = await wipApi.getFeedNewBatchNumber(feedId, params)
    return res.data?.data ?? res.data ?? res
  }

  async getFeedLastBatch(feedId, params = {}) {
    const res = await wipApi.getFeedLastBatch(feedId, params)
    return res.data?.data ?? res.data ?? res
  }

  async getActiveTanksFeed(feedId, params = {}) {
    const res = await wipApi.getActiveTanksFeed(feedId, params)
    return res.data?.data ?? res.data ?? res
  }

  async getActiveSpecificTanks(slocId, params = {}) {
    const res = await wipApi.getActiveSpecificTanks(slocId, params)
    return res.data?.data ?? res.data ?? res
  }

  // Rundown operations
  async getRundown(rundownId, mode = 'LATEST', params = {}) {
    const res = await wipApi.getRundown(rundownId, mode, params)
    return res.data?.data ?? res.data ?? res
  }

  async getRundownNewBatchNumber(rundownId, params = {}) {
    const res = await wipApi.getRundownNewBatchNumber(rundownId, params)
    return res.data?.data ?? res.data ?? res
  }

  async getRundownLastBatch(rundownId, params = {}) {
    const res = await wipApi.getRundownLastBatch(rundownId, params)
    return res.data?.data ?? res.data ?? res
  }

  async getActiveTanksRundown(rundownId, params = {}) {
    const res = await wipApi.getActiveTanksRundown(rundownId, params)
    return res.data?.data ?? res.data ?? res
  }

  // Balance
  async getBalance(rundownId, params = {}) {
    const res = await wipApi.getBalance(rundownId, params)
    return res.data?.data ?? res.data ?? res
  }

  // Quantifier (DCS integration)
  async getQuantifierData(date, tagNumber, params = {}) {
    const res = await wipApi.getQuantifierData(date, tagNumber, params)
    return res.data?.data ?? res.data ?? res
  }

  // Write operations
  async postFeed(data) {
    const res = await wipApi.store({
      flag: 'post_materialFeed',
      ...data,
    })
    return this.normalizeResponse(res)
  }

  async postRundown(data) {
    const res = await wipApi.store({
      flag: 'post_materialRundown',
      ...data,
    })
    return this.normalizeResponse(res)
  }

  async cancelFeed(traceNo, params = {}) {
    const res = await wipApi.store({
      flag: 'post_cancelFeed',
      traceNo,
      ...params,
    })
    return this.normalizeResponse(res)
  }

  async cancelRundown(traceNo, params = {}) {
    const res = await wipApi.store({
      flag: 'post_cancelRundown',
      traceNo,
      ...params,
    })
    return this.normalizeResponse(res)
  }

  async saveMaterialDocument(mode, idTraceHead, number) {
    const res = await wipApi.store({
      flag: 'post_matlDocNumber',
      mode,
      id: idTraceHead,
      number,
    })
    return this.normalizeResponse(res)
  }

  async updateSubTank(idHead, tankTails) {
    const res = await wipApi.store({
      flag: 'post_updateEntrySubTank',
      idHead,
      idTankTail: tankTails,
    })
    return this.normalizeResponse(res)
  }

  normalizeResponse(res) {
    const status = (res.data?.status ?? res.status ?? res.success) ? 1 : 0
    return {
      success: status === 1,
      message: res.data?.message ?? res.message ?? '',
      data: res.data?.data ?? res.data ?? res,
    }
  }
}

export const wipEntryRepository = new WipEntryRepository()