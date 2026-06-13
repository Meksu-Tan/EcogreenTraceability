import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as storageApi from '../services'

export const useSetupStorageStore = defineStore('setupStorage', () => {
  const tanks      = ref([])
  const details    = ref([])
  const warehouses = ref([])
  const loading    = ref(false)

  async function fetchTanks() {
    loading.value = true
    try { const res = await storageApi.getTanks(); tanks.value = res.data.data }
    finally { loading.value = false }
  }

  async function fetchDetails(tankId) {
    const res = await storageApi.getDetails(tankId)
    details.value = res.data.data
  }

  async function fetchWarehouses() {
    loading.value = true
    try { const res = await storageApi.getWarehouses(); warehouses.value = res.data.data }
    finally { loading.value = false }
  }

  async function createTank(data)         { const r = await storageApi.storeTank(data);         if (r.data.status===1) await fetchTanks(); return r.data }
  async function editTank(id, data)       { const r = await storageApi.updateTank(id, data);    if (r.data.status===1) await fetchTanks(); return r.data }
  async function toggleTank(id, status)   { const r = status==1 ? await storageApi.deactivateTank(id) : await storageApi.activateTank(id); if (r.data.status===1) await fetchTanks(); return r.data }

  async function createDetail(data)       { const r = await storageApi.storeDetail(data);       if (r.data.status===1) await fetchDetails(data.id_tank); return r.data }
  async function editDetail(id, data)     { const r = await storageApi.updateDetail(id, data);  if (r.data.status===1) await fetchDetails(data.id_tank); return r.data }
  async function toggleDetail(id, status, tankId) { const r = status==1 ? await storageApi.deactivateDetail(id) : await storageApi.activateDetail(id); if (r.data.status===1) await fetchDetails(tankId); return r.data }

  async function createWarehouse(data)    { const r = await storageApi.storeWarehouse(data);    if (r.data.status===1) await fetchWarehouses(); return r.data }
  async function editWarehouse(id, data)  { const r = await storageApi.updateWarehouse(id, data); if (r.data.status===1) await fetchWarehouses(); return r.data }
  async function toggleWarehouse(id, status){ const r = status==1 ? await storageApi.deactivateWarehouse(id) : await storageApi.activateWarehouse(id); if (r.data.status===1) await fetchWarehouses(); return r.data }

  return {
    tanks, details, warehouses, loading,
    fetchTanks, fetchDetails, fetchWarehouses,
    createTank, editTank, toggleTank,
    createDetail, editDetail, toggleDetail,
    createWarehouse, editWarehouse, toggleWarehouse,
  }
})
