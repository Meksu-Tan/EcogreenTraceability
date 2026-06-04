import { defineStore } from 'pinia'
import { ref } from 'vue'
import traceApi from '@/modules/trace-backward/api'
import shipmentService from '@/modules/ts-shipment/services/shipmentService'

export const useTraceBackwardStore = defineStore('traceBackward', () => {
  const list = ref([])
  const listMeta = ref({ page: 1, perPage: 25, total: 0, lastPage: 1 })
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
        perPage: payload.per_page || params.per_page || 25,
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
      shipmentData.value = res.data?.data?.data || null
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
      const batchRes = await shipmentService.getShipmentBatchPackaging(params)
      const records = batchRes.data?.data || []
      batchData.value = records.length > 0 ? records[0] : null

      const prepRes = await shipmentService.getPreparationRecord(params)
      preparationRecords.value = prepRes.data?.data || []

      const allocRes = await shipmentService.getDatSoAllocation(params)
      sapAllocations.value = allocRes.data?.data?.data?.IT_EXPORT || []
    } catch (err) {
      error.value = err.message || 'Failed to load batch packaging data'
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
    listMeta.value = { page: 1, perPage: 25, total: 0, lastPage: 1 }
    error.value = null
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
    clear,
  }
})
