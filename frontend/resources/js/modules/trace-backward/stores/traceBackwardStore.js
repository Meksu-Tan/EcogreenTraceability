import { defineStore } from 'pinia'
import { ref } from 'vue'
import traceApi from '@/modules/trace-backward/services/index.js'
import shipmentService from '@/modules/ts-shipment/services/shipmentService'

export const useTraceBackwardStore = defineStore('traceBackward', () => {
  const list = ref([])
  const listMeta = ref({ page: 1, perPage: 10, total: 0, lastPage: 1 })
  const detail = ref([])
  const shipmentData = ref(null)
  const batchData = ref(null)
  const preparationRecords = ref([])
  const sapAllocations = ref([])
  const loading = ref(false)
  const loadingDetail = ref(false)
  const loadingShipment = ref(false)
  const loadingBatch = ref(false)
  const error = ref(null)

  async function fetchList(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await traceApi.getBackwardList(params)
      const payload = res.data?.data || {}
      list.value = payload.data || (Array.isArray(payload) ? payload : [])
      listMeta.value = {
        page: payload.page || params.page || 1,
        perPage: payload.per_page || params.per_page || 10,
        total: payload.total || list.value.length,
        lastPage: payload.last_page || 1,
      }
    } catch (err) {
      error.value = err.message || 'Failed to load backward list'
      list.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchDetail(payload) {
    loadingDetail.value = true
    error.value = null
    try {
      const res = await traceApi.getTraceDetail(payload)
      const data = res.data?.data || (Array.isArray(res.data) ? res.data : [])
      detail.value = Array.isArray(data) ? data : (data.data || [])
    } catch (err) {
      error.value = err.message || 'Failed to load trace detail'
      detail.value = []
    } finally {
      loadingDetail.value = false
    }
  }

  async function fetchShipmentDetail(params) {
    loadingShipment.value = true
    error.value = null
    try {
      const res = await shipmentService.getDatShipment(params)
      // ApiResponse::success() wraps in data.data (2 levels deep)
      const payload = res.data?.data
      shipmentData.value = (Array.isArray(payload) ? payload[0] : payload) || null
    } catch (err) {
      error.value = err.message || 'Failed to load shipment data'
      shipmentData.value = null
    } finally {
      loadingShipment.value = false
    }
  }

  async function fetchBatchPackaging(params) {
    loadingBatch.value = true
    error.value = null
    try {
      // Get batch packaging data
      const batchRes = await shipmentService.getShipmentBatchPackaging(params)
      const batchRecords = batchRes.data?.data || []
      batchData.value = batchRecords.length > 0 ? batchRecords[0] : null

      // Get preparation records
      const prepRes = await shipmentService.getPreparationRecord(params)
      preparationRecords.value = prepRes.data?.data || []

      // Get SAP allocations — IT_EXPORT is nested inside data.data.IT_EXPORT
      const allocRes = await shipmentService.getDatSoAllocation(params)
      const allocPayload = allocRes.data?.data
      sapAllocations.value = Array.isArray(allocPayload?.IT_EXPORT)
        ? allocPayload.IT_EXPORT
        : []
    } catch (err) {
      error.value = err.message || 'Failed to load batch packaging data'
      batchData.value = null
      preparationRecords.value = []
      sapAllocations.value = []
    } finally {
      loadingBatch.value = false
    }
  }

  function clear() {
    list.value = []
    detail.value = []
    shipmentData.value = null
    batchData.value = null
    preparationRecords.value = []
    sapAllocations.value = []
    listMeta.value = { page: 1, perPage: 10, total: 0, lastPage: 1 }
    error.value = null
  }

  function setPage(p) {
    listMeta.value = { ...listMeta.value, page: p }
  }

  return {
    list,
    listMeta,
    detail,
    shipmentData,
    batchData,
    preparationRecords,
    sapAllocations,
    loading,
    loadingDetail,
    loadingShipment,
    loadingBatch,
    error,
    fetchList,
    fetchDetail,
    fetchShipmentDetail,
    fetchBatchPackaging,
    setPage,
    clear,
  }
})
