import { defineStore } from 'pinia'
import { ref } from 'vue'
import { MaterialRepository } from '@/repositories'

export const useSetupMaterialStore = defineStore('setupMaterial', () => {
  const materials        = ref([])
  const packagings       = ref([])
  const sourceProducts   = ref([])
  const loading          = ref(false)
  const loadingPkg       = ref(false)

  const repository = MaterialRepository

  async function fetchMaterials() {
    loading.value = true
    try {
      const res = await repository.all()
      materials.value = res
    } finally {
      loading.value = false
    }
  }

  async function fetchPackagings() {
    loadingPkg.value = true
    try {
      const res = await repository.getPackagings()
      packagings.value = res
    } finally {
      loadingPkg.value = false
    }
  }

  async function fetchSourceProducts() {
    const res = await repository.getSourceProducts()
    sourceProducts.value = res
  }

  async function createMaterial(data) {
    const res = await repository.create(data)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function editMaterial(id, data) {
    const res = await repository.update(id, data)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function toggleMaterial(id, currentStatus) {
    const res = currentStatus == 1
      ? await repository.deactivate(id)
      : await repository.activate(id)
    if (res.status === 1) await fetchMaterials()
    return res
  }

  async function createPackaging(data) {
    const res = await repository.createPackaging(data)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function editPackaging(id, data) {
    const res = await repository.updatePackaging(id, data)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function togglePackaging(id, currentStatus) {
    const res = currentStatus == 1
      ? await repository.deactivatePackaging(id)
      : await repository.activatePackaging(id)
    if (res.status === 1) await fetchPackagings()
    return res
  }

  async function searchMaterials(query, limit = 10) {
    return await repository.search(query, limit)
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
