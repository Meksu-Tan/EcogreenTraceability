import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as supplierApi from '@/api/setupSupplier'

export const useSetupSupplierStore = defineStore('setupSupplier', () => {
  const suppliers = ref([])
  const loading   = ref(false)

  async function fetchSuppliers() {
    loading.value = true
    try { const res = await supplierApi.getSuppliers(); suppliers.value = res.data.data }
    finally { loading.value = false }
  }

  async function createSupplier(data)      { const r = await supplierApi.storeSupplier(data);         if (r.data.status===1) await fetchSuppliers(); return r.data }
  async function editSupplier(id, data)    { const r = await supplierApi.updateSupplier(id, data);    if (r.data.status===1) await fetchSuppliers(); return r.data }
  async function toggleSupplier(id, status){ const r = status==1 ? await supplierApi.deactivateSupplier(id) : await supplierApi.activateSupplier(id); if (r.data.status===1) await fetchSuppliers(); return r.data }

  return { suppliers, loading, fetchSuppliers, createSupplier, editSupplier, toggleSupplier }
})
