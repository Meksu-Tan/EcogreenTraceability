import RmEntryApi from '@/api/transactionRmEntry'

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
