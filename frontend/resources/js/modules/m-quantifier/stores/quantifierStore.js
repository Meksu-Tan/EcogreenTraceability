import { defineStore } from 'pinia'
import { ref } from 'vue'
import quantifierApi from '@/modules/m-quantifier/services'
import { useTransactionList } from '@/composables/useTransactionList.js'

export const useQuantifierStore = defineStore('quantifier', () => {
  const {
    list,
    loading,
    error,
    pagination,
    setPage,
    fetchList: doFetchList,
    resetCache,
  } = useTransactionList(
    (params) => quantifierApi.getQuantifierList(params),
    { listKey: 'quantifier', defaultPerPage: 10 }
  )

  const flowmeters = ref([])
  const detail = ref(null)
  const saving = ref(false)

  async function fetchList(params = {}) {
    return doFetchList(params)
  }

  async function fetchFlowmeters() {
    if (flowmeters.value.length > 0) return
    try {
      const res = await quantifierApi.getFlowmeters()
      flowmeters.value = res.data?.data || []
    } catch { flowmeters.value = [] }
  }

  async function fetchDetail(id) {
    loading.value = true; error.value = null
    try {
      const res = await quantifierApi.getQuantifierDetail(id)
      detail.value = res.data?.data || res.data || null
    } catch (err) { error.value = err.message || 'Failed'; detail.value = null }
    finally { loading.value = false }
  }

  async function save(payload) {
    saving.value = true; error.value = null
    try {
      const res = await quantifierApi.storeQuantifier(payload)
      return { ok: true, data: res.data }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed'
      return { ok: false, error: error.value }
    } finally { saving.value = false }
  }

  async function activate(id) {
    saving.value = true; error.value = null
    try { await quantifierApi.activateQuantifier(id); return { ok: true } }
    catch (err) { error.value = err.message || 'Failed'; return { ok: false, error: error.value } }
    finally { saving.value = false }
  }

  async function deactivate(id) {
    saving.value = true; error.value = null
    try { await quantifierApi.deactivateQuantifier(id); return { ok: true } }
    catch (err) { error.value = err.message || 'Failed'; return { ok: false, error: error.value } }
    finally { saving.value = false }
  }

  function clear() {
    list.value = []; flowmeters.value = []; detail.value = null; error.value = null
  }

  return { list, flowmeters, detail, loading, saving, error, pagination, setPage, fetchList, fetchFlowmeters, fetchDetail, save, activate, deactivate, resetCache, clear }
})
