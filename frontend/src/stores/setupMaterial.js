import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as materialApi from '@/api/setupMaterial'

export const useSetupMaterialStore = defineStore('setupMaterial', () => {
  const materials        = ref([])
  const packagings       = ref([])
  const sourceProducts   = ref([])
  const loading          = ref(false)
  const loadingPkg       = ref(false)

  async function fetchMaterials() {
    loading.value = true
    try {
      const res = await materialApi.getMaterials()
      materials.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function fetchPackagings() {
    loadingPkg.value = true
    try {
      const res = await materialApi.getPackagings()
      packagings.value = res.data.data
    } finally {
      loadingPkg.value = false
    }
  }

  async function fetchSourceProducts() {
    const res = await materialApi.getSourceProducts()
    sourceProducts.value = res.data.data
  }

  async function createMaterial(data) {
    const res = await materialApi.storeMaterial(data)
    if (res.data.status === 1) await fetchMaterials()
    return res.data
  }

  async function editMaterial(id, data) {
    const res = await materialApi.updateMaterial(id, data)
    if (res.data.status === 1) await fetchMaterials()
    return res.data
  }

  async function toggleMaterial(id, currentStatus) {
    const res = currentStatus == 1
      ? await materialApi.deactivateMaterial(id)
      : await materialApi.activateMaterial(id)
    if (res.data.status === 1) await fetchMaterials()
    return res.data
  }

  async function createPackaging(data) {
    const res = await materialApi.storePackaging(data)
    if (res.data.status === 1) await fetchPackagings()
    return res.data
  }

  async function editPackaging(id, data) {
    const res = await materialApi.updatePackaging(id, data)
    if (res.data.status === 1) await fetchPackagings()
    return res.data
  }

  async function togglePackaging(id, currentStatus) {
    const res = currentStatus == 1
      ? await materialApi.deactivatePackaging(id)
      : await materialApi.activatePackaging(id)
    if (res.data.status === 1) await fetchPackagings()
    return res.data
  }

  return {
    materials, packagings, sourceProducts, loading, loadingPkg,
    fetchMaterials, fetchPackagings, fetchSourceProducts,
    createMaterial, editMaterial, toggleMaterial,
    createPackaging, editPackaging, togglePackaging,
  }
})
