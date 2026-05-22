import RmEntryApi from '@/modules/ts-raw/api'

class RmEntryRepository {
  async getList(params = {}) {
    const res = await RmEntryApi.getList(params)
    return res.data?.data ?? res.data ?? res
  }

  async save(data) {
    const res = await RmEntryApi.create(data)
    return res.data?.data ?? res.data ?? res
  }

  async deactivate(id) {
    const res = await RmEntryApi.deactivate(id)
    return res.data?.data ?? res.data ?? res
  }

  async generateRmNumber(params = {}) {
    const res = await RmEntryApi.getNewNumber(params)
    return res.data?.data ?? res.data ?? res
  }

  async generateTransferNumber(params = {}) {
    const res = await RmEntryApi.getTransferNumber(params)
    return res.data?.data ?? res.data ?? res
  }

  async generateBatchCode(params = {}) {
    const res = await RmEntryApi.getBatchCode(params)
    return res.data?.data ?? res.data ?? res
  }
}

export const rmEntryRepository = new RmEntryRepository()
