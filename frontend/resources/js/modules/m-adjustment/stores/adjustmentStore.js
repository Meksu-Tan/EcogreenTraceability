import { defineStore } from 'pinia'
import { ref } from 'vue'
import adjustmentApi from '@/modules/m-adjustment/services'

export const useAdjustmentStore = defineStore('adjustment', () => {
  // ——— State ———
  const data = ref([])
  const detail = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const listMeta = ref({ page: 1, perPage: 10, total: 0, lastPage: 1 })
  const periodMeta = ref({ page: 1, perPage: 10, total: 0, lastPage: 1 })

  // Lookup caches
  const activeMaterials = ref([])
  const activeMaterialWhx = ref([])
  const activeTanks = ref([])
  const activeSpecificTanks = ref([])
  const activeWhx = ref([])
  const supplierList = ref([])
  const searchSuppliersList = ref([])
  const batches = ref([])
  const lockStatus = ref(null)
  const entryNo = ref(null)

  // Period state
  const periodHeaders = ref([])
  const periodViewData = ref(null)
  const adjustStatus = ref(null)

  // ——— List / Detail ———
  async function fetchList(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await adjustmentApi.getAdjustmentList(params)
      const payload = res.data?.data || {}
      data.value = payload.data || (Array.isArray(payload) ? payload : [])
      listMeta.value = {
        page: payload.page || params.page || 1,
        perPage: payload.per_page || params.per_page || 10,
        total: payload.total || data.value.length,
        lastPage: payload.last_page || 1,
      }
    } catch (err) {
      error.value = err.message || 'Failed to fetch adjustment list'
      data.value = []
    } finally {
      loading.value = false
    }
  }

  function setPage(p) {
    listMeta.value = { ...listMeta.value, page: p }
  }

  async function fetchDetail(id) {
    loading.value = true
    error.value = null
    try {
      const res = await adjustmentApi.getAdjustmentDetail(id)
      detail.value = res.data?.data || res.data || null
    } catch (err) {
      error.value = err.message || 'Failed to fetch detail'
      detail.value = null
    } finally {
      loading.value = false
    }
  }

  // ——— Lookups ———
  async function fetchActiveMaterials() {
    if (activeMaterials.value.length > 0) return
    try {
      const res = await adjustmentApi.getActiveMaterials()
      activeMaterials.value = res.data?.data || []
    } catch { activeMaterials.value = [] }
  }

  async function fetchActiveMaterialWhx() {
    if (activeMaterialWhx.value.length > 0) return
    try {
      const res = await adjustmentApi.getActiveMaterialWhx()
      activeMaterialWhx.value = res.data?.data || []
    } catch { activeMaterialWhx.value = [] }
  }

  async function fetchActiveTanks(params = {}) {
    if (activeTanks.value.length > 0 && !params.id_plant) return
    try {
      const res = await adjustmentApi.getActiveTanks(params)
      activeTanks.value = res.data?.data || []
    } catch { activeTanks.value = [] }
  }

  async function fetchActiveSpecificTanks(sloc) {
    try {
      const res = await adjustmentApi.getActiveSpecificTanks(sloc)
      activeSpecificTanks.value = res.data?.data || []
    } catch { activeSpecificTanks.value = [] }
  }

  async function fetchActiveWhx() {
    if (activeWhx.value.length > 0) return
    try {
      const res = await adjustmentApi.getActiveWhx()
      activeWhx.value = res.data?.data || []
    } catch { activeWhx.value = [] }
  }

  async function fetchSupplierList(params = {}) {
    try {
      const res = await adjustmentApi.getSupplierList(params)
      supplierList.value = res.data?.data || []
    } catch { supplierList.value = [] }
  }

  async function fetchSuppliers(params = {}) {
    try {
      const res = await adjustmentApi.searchSuppliers(params)
      searchSuppliersList.value = res.data?.data || []
    } catch { searchSuppliersList.value = [] }
  }

  async function fetchBatchBySupplier(params = {}) {
    try {
      const res = await adjustmentApi.getBatchBySupplier(params)
      batches.value = res.data?.data || []
    } catch { batches.value = [] }
  }

  async function fetchLockStatus(params = {}) {
    try {
      const res = await adjustmentApi.getLockStatus(params)
      lockStatus.value = res.data?.data || res.data || null
    } catch { lockStatus.value = null }
  }

  async function fetchEntryNo(params = {}) {
    try {
      const res = await adjustmentApi.getEntryNo(params)
      entryNo.value = res.data?.entry_no || res.data?.data?.entry_no || null
    } catch { entryNo.value = null }
  }

  async function fetchPeriodHeaders(params = {}) {
    try {
      const res = await adjustmentApi.getPeriodHeaders(params)
      const payload = res.data?.data || {}
      periodHeaders.value = payload.data || (Array.isArray(payload) ? payload : [])
      periodMeta.value = {
        page: payload.page || params.page || 1,
        perPage: payload.per_page || params.per_page || 10,
        total: payload.total || periodHeaders.value.length,
        lastPage: payload.last_page || 1,
      }
    } catch {
      periodHeaders.value = []
      periodMeta.value = { page: 1, perPage: 10, total: 0, lastPage: 1 }
    }
  }

  function setPeriodPage(p) {
    periodMeta.value = { ...periodMeta.value, page: p }
  }

  async function fetchPeriodViewData(params = {}) {
    try {
      const res = await adjustmentApi.getPeriodViewData(params)
      periodViewData.value = res.data?.data || null
    } catch { periodViewData.value = null }
  }

  async function fetchAdjustStatus(params = {}) {
    try {
      const res = await adjustmentApi.getAdjustStatus(params)
      adjustStatus.value = res.data?.data || null
    } catch { adjustStatus.value = null }
  }

  // ——— Mutations ———
  async function storeAdjustment(payload) {
    const res = await adjustmentApi.storeAdjustment(payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function storeAdjustmentWhx(payload) {
    const res = await adjustmentApi.storeAdjustmentWhx(payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function destroyAdjustment(id) {
    const res = await adjustmentApi.destroyAdjustment(id)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function addEntrySupplier(payload) {
    const res = await adjustmentApi.addEntrySupplier(payload)
    return res.data
  }

  async function deleteSupplierTemp(id) {
    const res = await adjustmentApi.deleteSupplierTemp(id)
    return res.data
  }

  async function adjustmentInit(payload) {
    const res = await adjustmentApi.adjustmentInit(payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function adjustmentInitWhx(payload) {
    const res = await adjustmentApi.adjustmentInitWhx(payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function adjustmentSupplier(payload) {
    const res = await adjustmentApi.adjustmentSupplier(payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function approveAdjustment(id, params = {}) {
    const res = await adjustmentApi.approveAdjustment(id, params)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function executeAdjustment(id) {
    const res = await adjustmentApi.executeAdjustment(id)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function cancelAdjustment(id, payload) {
    const res = await adjustmentApi.cancelAdjustment(id, payload)
    if (res.data?.status === 1) {
      await fetchList()
    }
    return res.data
  }

  async function adjustMaterialDocument(id, payload = {}) {
    const res = await adjustmentApi.adjustMaterialDocument(id, payload)
    return res.data
  }

  async function periodHeadersUpload(data) {
    const res = await adjustmentApi.periodHeadersUpload(data)
    if (res.data?.status === 1) {
      await fetchPeriodHeaders()
    }
    return res.data
  }

  async function periodViewOnHand(data) {
    const res = await adjustmentApi.periodViewOnHand(data)
    return res.data
  }

  async function periodViewAdjustment(data) {
    const res = await adjustmentApi.periodViewAdjustment(data)
    if (res.data?.status === 1) {
      await fetchPeriodHeaders()
    }
    return res.data
  }

  async function periodHeaderLock(data) {
    const res = await adjustmentApi.periodHeaderLock(data)
    if (res.data?.status === 1) {
      await fetchPeriodHeaders()
    }
    return res.data
  }

  async function destroyAdjustmentPeriod(id) {
    const res = await adjustmentApi.destroyAdjustmentPeriod(id)
    if (res.data?.status === 1) {
      await fetchPeriodHeaders()
    }
    return res.data
  }

  async function generateBatchCode(supplierId) {
    try {
      const res = await adjustmentApi.generateBatchCode({ id_supplier: supplierId })
      return res.data?.batch_code || res.data?.data?.batch_code || ''
    } catch {
      return ''
    }
  }

  function clearData() {
    data.value = []
    detail.value = null
    error.value = null
    periodHeaders.value = []
    periodViewData.value = null
    adjustStatus.value = null
  }

  function clearSpecificTanks() {
    activeSpecificTanks.value = []
  }

  return {
    data, detail, loading, error, listMeta, periodMeta,
    activeMaterials, activeMaterialWhx, activeTanks, activeSpecificTanks, activeWhx,
    supplierList, searchSuppliersList, batches, lockStatus, entryNo,
    periodHeaders, periodViewData, adjustStatus,
    fetchList, fetchDetail, setPage, setPeriodPage,
    fetchActiveMaterials, fetchActiveMaterialWhx, fetchActiveTanks,
    fetchActiveSpecificTanks, fetchActiveWhx,
    fetchSupplierList, fetchSuppliers, fetchBatchBySupplier, fetchLockStatus, fetchEntryNo,
    fetchPeriodHeaders, fetchPeriodViewData, fetchAdjustStatus,
    storeAdjustment, storeAdjustmentWhx, destroyAdjustment,
    addEntrySupplier, deleteSupplierTemp,
    adjustmentInit, adjustmentInitWhx, adjustmentSupplier,
    approveAdjustment, executeAdjustment, cancelAdjustment,
    adjustMaterialDocument,
    periodHeadersUpload, periodViewOnHand, periodViewAdjustment,
    periodHeaderLock, destroyAdjustmentPeriod,
    generateBatchCode,
    clearData, clearSpecificTanks,
  }
})
