import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as tankApi from '../services/index.js'

export const useSetupTankStore = defineStore('setupTank', () => {
  const tanks   = ref([])
  const loading = ref(false)
  const lastSyncAt = ref(null)
  const lastSyncUser = ref(null)
  const lastSyncCount = ref(0)
  const lastSyncTanks = ref([])

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

  async function syncTanks(refresh = false) {
    const r = await tankApi.syncTanks(refresh)
    if (r.data.status === 1 || r.data.status === 2) {
      await fetchTanks()
      await fetchLastSync()
    }
    return r.data
  }

  async function fetchLastSync() {
    try {
      const r = await tankApi.getLastSync()
      lastSyncAt.value = r.data?.data?.last_sync_at || null
      lastSyncUser.value = r.data?.data?.last_sync_user || null
      lastSyncCount.value = r.data?.data?.last_sync_count || 0
      lastSyncTanks.value = r.data?.data?.last_sync_tanks || []
    } catch {
      lastSyncAt.value = null
      lastSyncUser.value = null
      lastSyncCount.value = 0
      lastSyncTanks.value = []
    }
  }

  return {
    tanks, loading, lastSyncAt, lastSyncUser, lastSyncCount, lastSyncTanks,
    fetchTanks, createTank, editTank, toggleTank, syncTanks, fetchLastSync
  }
})

export { useSetupWarehouseStore } from './warehouseStore.js'
