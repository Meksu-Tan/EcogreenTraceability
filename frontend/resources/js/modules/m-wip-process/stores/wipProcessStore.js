import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import * as wipProcessApi from '../services/wipProcessService.js'

export const useWipProcessStore = defineStore('wipProcess', () => {
  const sections = ref([])
  const selectedSectionId = ref(null)
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')

  const selectedSection = computed(() =>
    sections.value.find(s => Number(s.id) === Number(selectedSectionId.value)) || null
  )

  const selectedSteps = computed(() => selectedSection.value?.steps || [])

  async function fetchSections(params = {}) {
    loading.value = true
    error.value = ''
    try {
      const res = await wipProcessApi.getSections(params)
      sections.value = res.data.data
      if (!selectedSectionId.value && sections.value.length) {
        selectedSectionId.value = sections.value[0].id
      }
    } catch (e) {
      error.value = e.response?.data?.message || 'Failed to load sections'
    } finally {
      loading.value = false
    }
  }

  async function saveSection(payload) {
    saving.value = true
    try {
      const r = payload.id
        ? await wipProcessApi.updateSection(payload.id, payload)
        : await wipProcessApi.storeSection(payload)
      await fetchSections()
      return r.data
    } finally {
      saving.value = false
    }
  }

  async function deleteSection(id) {
    const r = await wipProcessApi.deleteSection(id)
    await fetchSections()
    return r.data
  }

  async function reorderSections(items) {
    const r = await wipProcessApi.reorderSections(items)
    return r.data
  }

  async function saveStep(payload) {
    saving.value = true
    try {
      const r = payload.id
        ? await wipProcessApi.updateStep(payload.id, payload)
        : await wipProcessApi.storeStep(payload)
      await fetchSections()
      return r.data
    } finally {
      saving.value = false
    }
  }

  async function deleteStep(id) {
    const r = await wipProcessApi.deleteStep(id)
    await fetchSections()
    return r.data
  }

  async function reorderSteps(items) {
    const r = await wipProcessApi.reorderSteps(items)
    return r.data
  }

  return {
    sections, selectedSectionId, selectedSection, selectedSteps, loading, saving, error,
    fetchSections, saveSection, deleteSection, reorderSections, saveStep, deleteStep, reorderSteps,
  }
})
