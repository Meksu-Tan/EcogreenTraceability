import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useLoadingState } from '@/composables/useLoadingState'
import stockApi from '@/modules/ts-stock/services/index.js'

export const useStockStore = defineStore('stock', () => {
  const { loading, error, withLoading } = useLoadingState()
  const stockData = ref([])
  const stockDetail = ref(null)
  const activeMaterials = ref([])
  const movements = ref([])

  async function fetchStock(params = {}) {
    await withLoading(async () => {
      const res = await stockApi.getStock(params)
      stockData.value = res.data?.data || res.data || []
    })
    if (error.value) stockData.value = []
  }
  async function fetchStockDetail(id) {
    await withLoading(async () => {
      const res = await stockApi.getStockById(id)
      stockDetail.value = res.data?.data || res.data
    })
    if (error.value) stockDetail.value = null
  }
  async function fetchActiveMaterials(params = {}) {
    try { const res = await stockApi.getActiveMaterials(params); activeMaterials.value = res.data?.data || [] }
    catch { activeMaterials.value = [] }
  }
  async function fetchMovements(params = {}) {
    await withLoading(async () => {
      const res = await stockApi.getMovements(params)
      movements.value = res.data?.data || res.data || []
    })
    if (error.value) movements.value = []
  }
  function clearData() { stockData.value = []; stockDetail.value = null; activeMaterials.value = []; movements.value = []; error.value = null }
  return { stockData, stockDetail, activeMaterials, movements, loading, error, fetchStock, fetchStockDetail, fetchActiveMaterials, fetchMovements, clearData }
})
