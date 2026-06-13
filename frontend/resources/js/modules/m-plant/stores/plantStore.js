import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as plantApi from '@/modules/m-plant/services'

export const useSetupPlantStore = defineStore('setupPlant', () => {
  const plants = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchPlants() {
    loading.value = true
    error.value = null
    try {
      const res = await plantApi.getPlants()
      plants.value = res.data.data || res.data || []
    } catch (err) {
      error.value = err.message || 'Failed to fetch plants'
      plants.value = []
    } finally {
      loading.value = false
    }
  }

  async function createPlant(data) {
    loading.value = true
    error.value = null
    try {
      const res = await plantApi.storePlant(data)
      if (res.data.status === 1) {
        await fetchPlants()
      }
      return res.data
    } catch (err) {
      error.value = err.message || 'Failed to create plant'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function editPlant(id, data) {
    loading.value = true
    error.value = null
    try {
      const res = await plantApi.updatePlant(id, data)
      if (res.data.status === 1) {
        await fetchPlants()
      }
      return res.data
    } catch (err) {
      error.value = err.message || 'Failed to update plant'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function togglePlant(id, status) {
    loading.value = true
    error.value = null
    try {
      const res = status == 1
        ? await plantApi.deactivatePlant(id)
        : await plantApi.activatePlant(id)
      if (res.data.status === 1) {
        await fetchPlants()
      }
      return res.data
    } catch (err) {
      error.value = err.message || 'Failed to toggle plant status'
      throw err
    } finally {
      loading.value = false
    }
  }

  function clearError() {
    error.value = null
  }

  return {
    plants,
    loading,
    error,
    fetchPlants,
    createPlant,
    editPlant,
    togglePlant,
    clearError
  }
})