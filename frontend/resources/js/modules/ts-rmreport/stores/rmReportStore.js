import { defineStore } from 'pinia'
import { ref } from 'vue'
import rmReportApi from '@/modules/ts-rmreport/services/index.js'

export const useRmReportStore = defineStore('rmReport', () => {
  const rmReportData = ref([])
  const rmReportSummary = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchRmReport(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await rmReportApi.getRmReport(params)
      rmReportData.value = res.data?.data || res.data || []
    } catch (err) {
      error.value = err.message || 'Failed to fetch RM report'
      rmReportData.value = []
    } finally {
      loading.value = false
    }
  }

  async function fetchRmReportSummary(params = {}) {
    loading.value = true
    error.value = null
    try {
      const res = await rmReportApi.getRmReportSummary(params)
      rmReportSummary.value = res.data?.data || res.data || []
      return rmReportSummary.value
    } catch (err) {
      error.value = err.message || 'Failed to fetch RM report summary'
      rmReportSummary.value = []
      return []
    } finally {
      loading.value = false
    }
  }

  async function fetchDetailOnTank(params) {
    try {
      const res = await rmReportApi.getDetailOnTank(params)
      return res.data?.data || []
    } catch (err) {
      return []
    }
  }

  async function fetchDetailOnAdjOut(params) {
    try {
      const res = await rmReportApi.getDetailOnAdjOut(params)
      return res.data?.data || []
    } catch (err) {
      return []
    }
  }

  async function fetchDetailOnWarehouse(params) {
    try {
      const res = await rmReportApi.getDetailOnWarehouse(params)
      return res.data?.data || []
    } catch (err) {
      return []
    }
  }

  return {
    rmReportData,
    rmReportSummary,
    loading,
    error,
    fetchRmReport,
    fetchRmReportSummary,
    fetchDetailOnTank,
    fetchDetailOnAdjOut,
    fetchDetailOnWarehouse,
  }
})
