import api from '@/api/axios'

export const packageService = {
  getEntries(params = {}) {
    return api.get('/api/v1/transactions/package-entries', { params })
  },
  store(data) {
    return api.post('/api/v1/transactions/package-entries', data)
  },
  cancel(id, traceNo) {
    return api.delete(`/api/v1/transactions/package-entries/${id}`, { data: { traceNo } })
  },
  updatePo(data) {
    return api.put('/api/v1/transactions/package-entries/po', data)
  },
  updateBatch(data) {
    return api.put('/api/v1/transactions/package-entries/batch', data)
  },
  updateSubTank(data) {
    return api.put('/api/v1/transactions/package-entries/subtank', data)
  },
  getActiveFgProducts() {
    return api.get('/api/v1/transactions/package-entries/active-fg-products')
  },
  getWipMaterials(idMaterialPck, tank, id_plant) {
    return api.get('/api/v1/transactions/package-entries/wip-materials', {
      params: { idMaterialPck, tank, id_plant }
    })
  },
  getActiveTanks(rundownID) {
    return api.get('/api/v1/transactions/package-entries/active-tanks', {
      params: { rundownID }
    })
  },
  getActiveWarehouses(batchNo) {
    return api.get('/api/v1/transactions/package-entries/active-warehouses', {
      params: { batchNo }
    })
  },
  getSpecificTanks(sloc, fgProduct) {
    return api.get('/api/v1/transactions/package-entries/specific-tanks', {
      params: { sloc, fgProduct }
    })
  },
  getNewTraceNo(id_material, id_plant) {
    return api.get('/api/v1/transactions/package-entries/new-trace-no', {
      params: { id_material, id_plant }
    })
  },
  getAllWarehouses() {
    return api.get('/api/v1/transactions/package-entries/all-warehouses')
  }
}

export default packageService
