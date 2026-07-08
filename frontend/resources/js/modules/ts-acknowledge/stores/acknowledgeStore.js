import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as acknowledgeApi from '../services/acknowledgeService.js'

export const useAcknowledgeStore = defineStore('acknowledge', () => {
  const sections = ref([])
  const allSections = ref([])
  const pagination = ref({ current_page: 1, per_page: 15, total: 0, last_page: 1 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const savingRows = ref({})

  async function fetchDashboard(params = {}) {
    loading.value = true
    error.value = ''
    try {
      const res = await acknowledgeApi.getDashboard(params)
      sections.value = res.data.data || []
      if (res.data.pagination) pagination.value = res.data.pagination
      if (res.data.allSections) allSections.value = res.data.allSections
    } catch (e) {
      error.value = e.response?.data?.message || 'Failed to load dashboard'
    } finally {
      loading.value = false
    }
  }

  async function saveAcknowledge(data) {
    saving.value = true
    try {
      const r = await acknowledgeApi.saveAcknowledge(data)
      return r.data
    } finally {
      saving.value = false
    }
  }

  async function fetchDcs(data) {
    const r = await acknowledgeApi.fetchDcs(data)
    return r.data
  }

  async function syncDcs(data) {
    const r = await acknowledgeApi.syncDcs(data)
    return r.data
  }

  function setRowLoading(key, value) {
    savingRows.value[key] = value
  }

  return { sections, allSections, pagination, loading, saving, error, savingRows, fetchDashboard, saveAcknowledge, fetchDcs, syncDcs, setRowLoading }
})
