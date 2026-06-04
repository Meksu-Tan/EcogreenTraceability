import api from '@/api/axios'

export default {
  getDatShipment(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/sap-shipment', { params })
  },

  getShipmentBatchPackaging(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/batch-packaging', { params })
  },

  getPreparationRecord(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/preparation-record', { params })
  },

  getDatSoAllocation(params = {}) {
    return api.get('/api/v1/transactions/shipment-entries/sap-so-allocation', { params })
  },
}
