import { defineStore } from 'pinia'
import { ref } from 'vue'
import inquiryApi from '@/modules/inquiry/api'

export const useInquiryStore = defineStore('inquiry', () => {
  const stockData = ref([])
  const tsReportData = ref([])
  const rmReportData = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchStock(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await inquiryApi.getStock(params)
      stockData.value = res.data.data || res.data
    } catch (err) {
      error.value = err.message || 'Failed to fetch stock data'
      console.error('Fetch stock error:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchTsReport(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await inquiryApi.getTsReport(params)
      tsReportData.value = res.data.data || res.data
    } catch (err) {
      error.value = err.message || 'Failed to fetch TS report data'
      console.error('Fetch TS report error:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchRmReport(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await inquiryApi.getRmReport(params)
      rmReportData.value = res.data.data || res.data
    } catch (err) {
      error.value = err.message || 'Failed to fetch RM report data'
      console.error('Fetch RM report error:', err)
    } finally {
      loading.value = false
    }
  }

  function clearData() {
    stockData.value = []
    tsReportData.value = []
    rmReportData.value = []
    error.value = null
  }

  return {
    stockData,
    tsReportData,
    rmReportData,
    loading,
    error,
    fetchStock,
    fetchTsReport,
    fetchRmReport,
    clearData
  }
})