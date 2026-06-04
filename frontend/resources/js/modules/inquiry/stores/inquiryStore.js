import { defineStore } from 'pinia'
import { ref } from 'vue'
import inquiryApi from '@/modules/inquiry/api'

export const useInquiryStore = defineStore('inquiry', () => {
  // Stock
  const stockData = ref([])
  const stockDetail = ref(null)
  const tsReportData = ref([])
  const rmReportData = ref([])
  const loading = ref(false)
  const error = ref(null)

  // PSPA Report
  const psPaReports = ref([])
  const psPaReportDetail = ref(null)
  const materialStock = ref([])
  const psPaLoading = ref(false)
  const psPaError = ref(null)

  // ===== Stock Inquiry =====

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

  async function fetchStockDetail(id) {
    loading.value = true
    error.value = null
    try {
      const res = await inquiryApi.getStockById(id)
      stockDetail.value = res.data.data || res.data
    } catch (err) {
      error.value = err.message || 'Failed to fetch stock detail'
      console.error('Fetch stock detail error:', err)
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
      error.value = err.message || 'Failed to fetch TS report'
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
      error.value = err.message || 'Failed to fetch RM report'
      console.error('Fetch RM report error:', err)
    } finally {
      loading.value = false
    }
  }

  // ===== PSPA Report =====

  async function fetchPsPaReports(params = {}) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.getPsPaReportList(params)
      psPaReports.value = res.data.data || res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to fetch PSPA reports'
      console.error('Fetch PSPA reports error:', err)
    } finally {
      psPaLoading.value = false
    }
  }

  async function fetchPsPaReportDetail(id) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.getPsPaReportDetail(id)
      psPaReportDetail.value = res.data.data || res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to fetch PSPA report detail'
      console.error('Fetch PSPA report detail error:', err)
    } finally {
      psPaLoading.value = false
    }
  }

  async function fetchMaterialStock(params = {}) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.getMaterialStock(params)
      materialStock.value = res.data.data || res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to fetch material stock'
      console.error('Fetch material stock error:', err)
    } finally {
      psPaLoading.value = false
    }
  }

  async function generateReport(payload) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.generatePsPaReport(payload)
      return res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to generate PSPA report'
      console.error('Generate PSPA report error:', err)
      return null
    } finally {
      psPaLoading.value = false
    }
  }

  async function calculateReport(id) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.calculatePsPaReport(id)
      return res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to calculate PSPA report'
      console.error('Calculate PSPA report error:', err)
      return null
    } finally {
      psPaLoading.value = false
    }
  }

  async function approveReport(id) {
    psPaLoading.value = true
    psPaError.value = null
    try {
      const res = await inquiryApi.approvePsPaReport(id)
      return res.data
    } catch (err) {
      psPaError.value = err.message || 'Failed to approve PSPA report'
      console.error('Approve PSPA report error:', err)
      return null
    } finally {
      psPaLoading.value = false
    }
  }

  function clearData() {
    stockData.value = []
    stockDetail.value = null
    tsReportData.value = []
    rmReportData.value = []
    error.value = null
  }

  function clearPsPaReports() {
    psPaReports.value = []
    psPaReportDetail.value = null
    materialStock.value = []
    psPaError.value = null
  }

  return {
    stockData,
    stockDetail,
    tsReportData,
    rmReportData,
    loading,
    error,
    psPaReports,
    psPaReportDetail,
    materialStock,
    psPaLoading,
    psPaError,
    fetchStock,
    fetchStockDetail,
    fetchTsReport,
    fetchRmReport,
    fetchPsPaReports,
    fetchPsPaReportDetail,
    fetchMaterialStock,
    generateReport,
    calculateReport,
    approveReport,
    clearData,
    clearPsPaReports,
  }
})
