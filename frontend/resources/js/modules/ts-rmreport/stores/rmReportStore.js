import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useLoadingState } from '@/composables/useLoadingState'
import rmReportApi from '@/modules/ts-rmreport/services/index.js'

export const useRmReportStore = defineStore('rmReport', () => {
  const { loading, error, withLoading } = useLoadingState()
  const rmReportData = ref([])
  const rmReportSummary = ref([])

  async function fetchRmReport(params = {}) {
    await withLoading(async () => {
      const res = await rmReportApi.getRmReport(params)
      rmReportData.value = res.data?.data || res.data || []
    })
    if (error.value) rmReportData.value = []
  }

  async function fetchRmReportSummary(params = {}) {
    let result = []
    await withLoading(async () => {
      const res = await rmReportApi.getRmReportSummary(params)
      rmReportSummary.value = res.data?.data || res.data || []
      result = rmReportSummary.value
    })
    if (error.value) rmReportSummary.value = []
    return result
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
