import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useLoadingState } from '@/composables/useLoadingState'
import traceApi from '@/modules/trace-forward/services/index.js'

export const useTraceForwardStore = defineStore('traceForward', () => {
  const { loading, error, withLoading } = useLoadingState()
  const { loading: loadingDetail } = useLoadingState()
  const list = ref([])
  const listMeta = ref({ page: 1, perPage: 10, total: 0, lastPage: 1 })
  const detail = ref({ initial: [], chain: [] })

  async function fetchList(params = {}) {
    await withLoading(async () => {
      const res = await traceApi.getForwardList(params)
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

  function setPage(p) {
    listMeta.value = { ...listMeta.value, page: p }
  }

  function clear() {
    list.value = []
    detail.value = { initial: [], chain: [] }
    listMeta.value = { page: 1, perPage: 10, total: 0, lastPage: 1 }
    error.value = null
  }

  return {
    list,
    listMeta,
    detail,
    loading,
    loadingDetail,
    error,
    fetchList,
    fetchDetail,
    setPage,
    clear,
  }
})
