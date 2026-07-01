import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useLoadingState } from '@/composables/useLoadingState'
import traceApi from '@/modules/trace-backward/services/index.js'
import shipmentService from '@/modules/ts-shipment/services/shipmentService'

export const useTraceBackwardStore = defineStore('traceBackward', () => {
  const { loading, error, withLoading } = useLoadingState()
  const { loading: loadingDetail } = useLoadingState()
  const list = ref([])
  const listMeta = ref({ page: 1, perPage: 10, total: 0, lastPage: 1 })
  const detail = ref({ initial: [], chain: [] })
  const shipmentData = ref(null)
  const batchList = ref([])
  const preparationRecords = ref([])
  const sapAllocations = ref([])
  const loadingShipment = ref(false)
  const loadingBatch = ref(false)

  async function fetchList(params = {}) {
    await withLoading(async () => {
      const res = await traceApi.getBackwardList(params)
      const payload = res.data?.data || {}
      list.value = payload.data || (Array.isArray(payload) ? payload : [])
      listMeta.value = {
        page: payload.page || params.page || 1,
        perPage: payload.per_page || params.per_page || 10,
        total: payload.total || list.value.length,
        lastPage: payload.last_page || 1,
      }
    })
    if (error.value) list.value = []
  }

  async function fetchDetail(payload) {
    loadingDetail.value = true
    error.value = null
    try {
      const res = await traceApi.getTraceDetail(payload)
      const data = res.data?.data || {}
      detail.value = {
        initial: data.initial || [],
        chain: data.chain || [],
      }
    } catch (err) {
      error.value = err.message || 'Failed to load trace detail'
      detail.value = { initial: [], chain: [] }
    } finally {
      loadingDetail.value = false
    }
  }

  async function fetchShipmentDetail(params) {
    loadingShipment.value = true
    error.value = null
    try {
      const res = await shipmentService.getDatShipment(params)
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
      const batchRes = await shipmentService.getShipmentBatchPackaging(params)
      batchList.value = batchRes.data?.data || []

      const prepRes = await shipmentService.getPreparationRecord(params)
      preparationRecords.value = prepRes.data?.data || []

      const allocRes = await shipmentService.getDatSoAllocation(params)
      const allocPayload = allocRes.data?.data
      sapAllocations.value = Array.isArray(allocPayload) ? allocPayload : []
    } catch (err) {
      error.value = err.message || 'Failed to load batch packaging data'
      batchList.value = []
      preparationRecords.value = []
      sapAllocations.value = []
    } finally {
      loadingBatch.value = false
    }
  }

  function clear() {
    list.value = []
    detail.value = { initial: [], chain: [] }
    shipmentData.value = null
    batchList.value = []
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
    batchList,
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
