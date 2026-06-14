import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as materialService from '../services/index.js'

export const useSetupMaterialStore = defineStore('setupMaterial', () => {
  const materials        = ref([])
  const packagings       = ref([])
  const sourceProducts   = ref([])
  const loading          = ref(false)
  const loadingPkg       = ref(false)

  async function fetchMaterials() {
    loading.value = true
    try {
      const res = await materialService.getMaterials()
      materials.value = res.data?.data || res.data || []
    } finally {
      loading.value = false
    }
  }

  async function fetchPackagings() {
    loadingPkg.value = true
    try {
      const res = await materialService.getPackagings()
      packagings.value = res.data?.data || res.data || []
    } finally {
      loadingPkg.value = false
    }
  }

  async function fetchSourceProducts() {
    const res = await materialService.getSourceProducts()
    sourceProducts.value = res.data?.data || res.data || []
  }

  async function createMaterial(data) {
    const res = await materialService.storeMaterial(data)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function editMaterial(id, data) {
    const res = await materialService.updateMaterial(id, data)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function toggleMaterial(id, currentStatus) {
    const res = currentStatus == 1
      ? await materialService.deactivateMaterial(id)
      : await materialService.activateMaterial(id)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function createPackaging(data) {
    const res = await materialService.storePackaging(data)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function editPackaging(id, data) {
    const res = await materialService.updatePackaging(id, data)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function togglePackaging(id, currentStatus) {
    const res = currentStatus == 1
      ? await materialService.deactivatePackaging(id)
      : await materialService.activatePackaging(id)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function searchMaterials(query, limit = 10) {
    const res = await materialService.getMaterials({ search: query, per_page: limit })
    return res.data?.data || res.data || []
  }

  return {
    materials, packagings, sourceProducts, loading, loadingPkg,
    fetchMaterials, fetchPackagings, fetchSourceProducts,
    createMaterial, editMaterial, toggleMaterial,
    createPackaging, editPackaging, togglePackaging,
    searchMaterials,
  }
})

export { useMaterialStore } from './materialStore'
