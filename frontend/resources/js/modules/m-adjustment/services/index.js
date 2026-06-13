import api from '@/api/axios'

const BASE_URL = '/api/v1/master/adjustment'

export default {
  // ——— List / Detail ———
  getAdjustmentList(params = {}) {
    return api.get(BASE_URL, { params })
  },
  getAdjustmentDetail(id) {
    return api.get(`${BASE_URL}/${id}`)
  },

  // ——— Lookups ———
  getActiveMaterials() {
    return api.get(`${BASE_URL}/active-materials`)
  },
  getActiveMaterialWhx() {
    return api.get(`${BASE_URL}/active-material-whx`)
  },
  getActiveTanks(params = {}) {
    return api.get(`${BASE_URL}/active-tanks`, { params })
  },
  getActiveSpecificTanks(sloc) {
    return api.get(`${BASE_URL}/active-specific-tanks/${sloc}`)
  },
  getActiveWhx() {
    return api.get(`${BASE_URL}/active-whx`)
  },
  getSupplierList(params = {}) {
    return api.get(`${BASE_URL}/supplier-list`, { params })
  },
  getTotalQty(params = {}) {
    return api.get(`${BASE_URL}/total-qty`, { params })
  },
  searchSuppliers(params = {}) {
    return api.get(`${BASE_URL}/suppliers/search`, { params })
  },
  getSupplierByFilter(params = {}) {
    return api.get(`${BASE_URL}/supplier-by-filter`, { params })
  },
  getBatchBySupplier(params = {}) {
    return api.get(`${BASE_URL}/batch-by-supplier`, { params })
  },
  generateBatchCode(params = {}) {
    return api.get(`/api/v1/transactions/rm-entries/batch-code`, { params })
  },
  getEntryNo(params = {}) {
    return api.get(`${BASE_URL}/entry-no`, { params })
  },
  getLockStatus(params = {}) {
    return api.get(`${BASE_URL}/lock-status`, { params })
  },
  getPeriodHeaders() {
    return api.get(`${BASE_URL}/period-headers`)
  },
  getPeriodViewData(params = {}) {
    return api.get(`${BASE_URL}/period-view-data`, { params })
  },
  getAdjustStatus(params = {}) {
    return api.get(`${BASE_URL}/adjust-status`, { params })
  },

  // ——— Mutations (CRUD) ———
  createAdjustment(payload) {
    return api.post(BASE_URL, payload)
  },
  createAdjustmentDetail(payload) {
    return api.post(`${BASE_URL}/detail`, payload)
  },
  storeAdjustment(payload) {
    return api.post(`${BASE_URL}/store-adjustment`, payload)
  },
  storeAdjustmentWhx(payload) {
    return api.post(`${BASE_URL}/store-adjustment-whx`, payload)
  },
  addEntrySupplier(payload) {
    return api.post(`${BASE_URL}/add-entry-supplier`, payload)
  },
  adjustmentInit(payload) {
    return api.post(`${BASE_URL}/init`, payload)
  },
  adjustmentInitWhx(payload) {
    return api.post(`${BASE_URL}/adjustment-init-whx`, payload)
  },
  adjustmentSupplier(payload) {
    return api.post(`${BASE_URL}/supplier-adjust`, payload)
  },
  approveAdjustment(id, params = {}) {
    return api.post(`${BASE_URL}/approve/${id}`, params)
  },
  executeAdjustment(id) {
    return api.post(`${BASE_URL}/execute/${id}`)
  },
  cancelAdjustment(id, payload = {}) {
    return api.post(`${BASE_URL}/cancel/${id}`, payload)
  },
  adjustMaterialDocument(id, payload = {}) {
    return api.put(`${BASE_URL}/material-document/${id}`, payload)
  },
  periodHeadersUpload(data) {
    return api.post(`${BASE_URL}/period-headers-upload`, data, { headers: { 'Content-Type': 'multipart/form-data' } })
  },
  periodViewOnHand(data) {
    return api.post(`${BASE_URL}/period-view-on-hand`, data)
  },
  periodViewAdjustment(data) {
    return api.post(`${BASE_URL}/period-view-adjustment`, data)
  },
  periodHeaderLock(data) {
    return api.post(`${BASE_URL}/period-header-lock`, data)
  },

  // ——— Delete ———
  destroyAdjustment(id) {
    return api.delete(`${BASE_URL}/destroy/${id}`)
  },
  deleteSupplierTemp(id) {
    return api.delete(`${BASE_URL}/delete-supplier-temp/${id}`)
  },
  destroyAdjustmentPeriod(id) {
    return api.delete(`${BASE_URL}/destroy-adjustment-period/${id}`)
  },
}
