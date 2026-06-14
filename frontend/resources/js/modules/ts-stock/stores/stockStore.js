import { defineStore } from 'pinia'
import { ref } from 'vue'
import stockApi from '@/modules/ts-stock/services/index.js'

export const useStockStore = defineStore('stock', () => {
  const stockData = ref([])
  const stockDetail = ref(null)
  const activeMaterials = ref([])
  const movements = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchStock(params = {}) {
    loading.value = true; error.value = null
    try { const res = await stockApi.getStock(params); stockData.value = res.data?.data || res.data || [] }
    catch (err) { error.value = err.message || 'Failed'; stockData.value = [] }
    finally { loading.value = false }
  }
  async function fetchStockDetail(id) {
    loading.value = true; error.value = null
    try { const res = await stockApi.getStockById(id); stockDetail.value = res.data?.data || res.data }
    catch (err) { error.value = err.message || 'Failed'; stockDetail.value = null }
    finally { loading.value = false }
  }
  async function fetchActiveMaterials(params = {}) {
    try { const res = await stockApi.getActiveMaterials(params); activeMaterials.value = res.data?.data || [] }
    catch { activeMaterials.value = [] }
  }
  async function fetchMovements(params = {}) {
    loading.value = true; error.value = null
    try { const res = await stockApi.getMovements(params); movements.value = res.data?.data || res.data || [] }
    catch (err) { error.value = err.message || 'Failed'; movements.value = [] }
    finally { loading.value = false }
  }
  function clearData() { stockData.value = []; stockDetail.value = null; activeMaterials.value = []; movements.value = []; error.value = null }
  return { stockData, stockDetail, activeMaterials, movements, loading, error, fetchStock, fetchStockDetail, fetchActiveMaterials, fetchMovements, clearData }
})
