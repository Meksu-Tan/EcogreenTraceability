import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as MaterialApi from '../services'

export const useMaterialStore = defineStore('moduleMaterial', () => {
  const items = ref([])
  const loading = ref(false)

  async function fetchAll() {
    loading.value = true
    try {
      const res = await MaterialApi.getMaterials()
      items.value = res.data ?? res ?? []
    } catch (error) {
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function create(data) {
    const res = await MaterialApi.storeMaterial(data)
    if (res.data?.status === 1) await fetchAll()
    return res.data ?? res
  }

  async function update(id, data) {
    const res = await MaterialApi.updateMaterial(id, data)
    if (res.data?.status === 1) await fetchAll()
    return res.data ?? res
  }

  return { items, loading, fetchAll, create, update }
})
