import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as tankApi from '../services/index.js'

export const useSetupTankStore = defineStore('setupTank', () => {
  const tanks   = ref([])
  const loading = ref(false)

  async function fetchTanks() {
    loading.value = true
    try {
      const res = await tankApi.getTanks()
      tanks.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function createTank(data) {
    const r = await tankApi.storeTank(data)
    if (r.data.status === 1) await fetchTanks()
    return r.data
  }

  async function editTank(id, data) {
    const r = await tankApi.updateTank(id, data)
    if (r.data.status === 1) await fetchTanks()
    return r.data
  }

  async function toggleTank(id, status) {
    const r = status == 1 ? await tankApi.deactivateTank(id) : await tankApi.activateTank(id)
    if (r.data.status === 1) await fetchTanks()
    return r.data
  }

  async function syncTanks() {
    const r = await tankApi.syncTanks()
    if (r.data.status === 1) await fetchTanks()
    return r.data
  }

  return {
    tanks, loading,
    fetchTanks, createTank, editTank, toggleTank, syncTanks
  }
})
