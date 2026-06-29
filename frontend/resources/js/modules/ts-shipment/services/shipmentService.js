import api from '@/api/axios'

export const shipmentService = {
  getEntries(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries', { params })
  },
  store(data) {
    // If the data has file, we send as FormData
    const headers = data instanceof FormData 
      ? { 'Content-Type': 'multipart/form-data' }
      : {}
    return api.post('/api/v1/transactions/shipment-entries', data, { headers })
  },
  cancel(id, traceNo) {
    return api.delete(`/api/v1/transactions/shipment-entries/${id}`, { data: { traceNo } })
  },
  updateSo(data) {
    return api.put('/api/v1/transactions/shipment-entries/so', data)
  },
  getActiveFgProducts() {
    return api.get('/api/v1/transactions/shipment-entries/active-fg-products')
  },
  getWipMaterials(idMaterial, idPlant) {
    return api.get('/api/v1/transactions/shipment-entries/wip-materials', {
      params: { idMaterial, id_plant: idPlant }
    })
  },
  getActiveBatches(idMaterial, idPlant) {
    return api.get('/api/v1/transactions/shipment-entries/active-batches', {
      params: { idMaterial, id_plant: idPlant }
    })
  },
  getShipmentBatchPackaging(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/batch-packaging', { params })
  },
  getPreparationRecord(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/preparation-record', { params })
  },
  getLabel(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/label', { params })
  },
  getSpecialLabel(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/special-label', { params })
  },
  getCustomerMark(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/customer-mark', { params })
  },
  getDatShipment(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/sap-shipment', { params })
  },
  getDatSoAllocation(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/sap-so-allocation', { params })
  },
  getNewTraceNo({ id_material, id_plant }) {
    return api.get('/api/v1/transactions/shipment-entries/new-trace-no', {
      params: { id_plant, id_material }
    })
  }
}

export default shipmentService
