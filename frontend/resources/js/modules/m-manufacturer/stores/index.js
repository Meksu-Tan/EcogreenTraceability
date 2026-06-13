import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as manufacturerApi from '../services'

export const useSetupManufacturerStore = defineStore('setupManufacturer', () => {
  const manufacturers = ref([])
  const loading       = ref(false)

  async function fetchManufacturers() {
    loading.value = true
    try {
      const res = await manufacturerApi.getManufacturers()
      manufacturers.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function createManufacturer(data) {
    const r = await manufacturerApi.storeManufacturer(data)
    if (r.data.status === 1) await fetchManufacturers()
    return r.data
  }

  async function editManufacturer(id, data) {
    const r = await manufacturerApi.updateManufacturer(id, data)
    if (r.data.status === 1) await fetchManufacturers()
    return r.data
  }

  async function toggleManufacturer(id, status) {
    const r = status == 1
      ? await manufacturerApi.deactivateManufacturer(id)
      : await manufacturerApi.activateManufacturer(id)
    if (r.data.status === 1) await fetchManufacturers()
    return r.data
  }

  return {
    manufacturers,
    loading,
    fetchManufacturers,
    createManufacturer,
    editManufacturer,
    toggleManufacturer
  }
})
