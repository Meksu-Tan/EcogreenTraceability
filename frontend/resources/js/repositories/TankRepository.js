/**
 * @note Naming convention mismatch: this file lives in `repositories/` but should be in `services/`
 *       per project convention (View → Store → Service → Axios). Rename deferred due to risk.
 */
import RmEntryApi from '@/modules/ts-raw/services'

class TankRepository {
  async getAvailable(params = {}) {
    const res = await RmEntryApi.getTanks(params)
    return res.data?.data ?? res.data ?? res
  }

  async getDetails(tankId) {
    const res = await RmEntryApi.getTankDetails(tankId)
    return res.data?.data ?? res.data ?? res
  }
}

export const tankRepository = new TankRepository()
