import RmEntryApi from '@/modules/ts-raw/api'

class RmEntryRepository {
  async getList(params = {}) {
    const res = await RmEntryApi.getList(params)
    return res.data?.data ?? res.data ?? res
  }

  async getById(id) {
    const res = await RmEntryApi.getById(id)
    return res.data?.data ?? res.data ?? res
  }

  async save(data) {
    const res = await RmEntryApi.create(data)
    return res.data?.data ?? res.data ?? res
  }

  async update(id, data) {
    const res = await RmEntryApi.update(id, data)
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

  async getStorageLog(plantId) {
    const res = await RmEntryApi.getStorageLog({ id_plant: plantId })
    return res.data?.data ?? res.data ?? res
  }

  async getFeedLog(params = {}) {
    const queryParams = typeof params === 'object' ? params : { id_plant: params }
    const res = await RmEntryApi.getFeedLog(queryParams)
    return res.data?.data ?? res.data ?? res
  }

  async getDestTanks(plantId) {
    const res = await RmEntryApi.getDestTanks(plantId)
    return res.data?.data ?? res.data ?? res
  }

  async getTransferList(plantId = 0) {
    const res = await RmEntryApi.getTransferList({ id_plant: plantId })
    return res.data?.data ?? res.data ?? res
  }

  async deactivateTransfer(id) {
    const res = await RmEntryApi.deactivateTransfer(id)
    return res.data?.data ?? res.data ?? res
  }
}

export const rmEntryRepository = new RmEntryRepository()
