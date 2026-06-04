import { defineStore } from 'pinia'
import { ref } from 'vue'
import tsReportApi from '@/modules/ts-tsreport/api'
export const useTsReportStore = defineStore('tsReport', () => {
  const tsReportData = ref([]), rmSection = ref([]), pckSection = ref([]), shipmentSection = ref([]), transferSection = ref([]), wipSection = ref([]), loading = ref(false), error = ref(null)
  async function fetchTsReport(params = {}) { loading.value = true; error.value = null; try { const r = await tsReportApi.getTsReport(params); tsReportData.value = r.data?.data || r.data || [] } catch (e) { error.value = e.message; tsReportData.value = [] } finally { loading.value = false } }
  async function fetchRmSection(params = {}) { loading.value = true; try { const r = await tsReportApi.getRmSection(params); rmSection.value = r.data?.data || [] } catch { rmSection.value = [] } finally { loading.value = false } }
  async function fetchPckSection(params = {}) { loading.value = true; try { const r = await tsReportApi.getPckSection(params); pckSection.value = r.data?.data || [] } catch { pckSection.value = [] } finally { loading.value = false } }
  async function fetchShipmentSection(params = {}) { loading.value = true; try { const r = await tsReportApi.getShipmentSection(params); shipmentSection.value = r.data?.data || [] } catch { shipmentSection.value = [] } finally { loading.value = false } }
  async function fetchTransferSection(params = {}) { loading.value = true; try { const r = await tsReportApi.getTransferSection(params); transferSection.value = r.data?.data || [] } catch { transferSection.value = [] } finally { loading.value = false } }
  async function fetchWipSection(params = {}) { loading.value = true; try { const r = await tsReportApi.getWipSection(params); wipSection.value = r.data?.data || [] } catch { wipSection.value = [] } finally { loading.value = false } }
  return { tsReportData, rmSection, pckSection, shipmentSection, transferSection, wipSection, loading, error, fetchTsReport, fetchRmSection, fetchPckSection, fetchShipmentSection, fetchTransferSection, fetchWipSection }
})
