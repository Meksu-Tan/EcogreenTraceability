import { defineStore } from 'pinia'
import { ref } from 'vue'
import wipApi from '@/api/transactionWip'
import { useToastStore } from './toast'

export const useTransactionWipStore = defineStore('transactionWip', () => {
  const toastStore = useToastStore()
  const loading = ref(false)

  function plantParams(plantId) {
    return { id_plant: plantId ?? 0 }
  }

  function unwrapList(response) {
    const payload = response?.data
    if (Array.isArray(payload)) return payload
    if (Array.isArray(payload?.data)) return payload.data
    return payload ? [payload] : []
  }

  async function fetchLatestFeed(feedId, plantId) {
    loading.value = true
    try {
      const response = await wipApi.getFeed(feedId, {
        mode: 'LATEST',
        ...plantParams(plantId),
      })
      return unwrapList(response)
    } catch (err) {
      toastStore.error(err.response?.data?.message || 'Failed to load feed log')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchLatestRundown(rundownId, plantId) {
    loading.value = true
    try {
      const response = await wipApi.getRundown(rundownId, {
        mode: 'LATEST',
        ...plantParams(plantId),
      })
      return unwrapList(response)
    } catch (err) {
      toastStore.error(err.response?.data?.message || 'Failed to load rundown log')
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchFeedLog(feedId, plantId) {
    const response = await wipApi.getFeed(feedId, {
      mode: 'LOG',
      ...plantParams(plantId),
    })
    return unwrapList(response)
  }

  async function fetchRundownLog(rundownId, plantId) {
    const response = await wipApi.getRundown(rundownId, {
      mode: 'LOG',
      ...plantParams(plantId),
    })
    return unwrapList(response)
  }

  async function fetchBalance(rundownId, plantId) {
    const response = await wipApi.getBalance(rundownId, plantParams(plantId))
    return unwrapList(response)
  }

  async function fetchOption(option, params = {}) {
    const response = await wipApi.getOptions(option, params)
    const payload = response?.data
    if (Array.isArray(payload?.data)) return payload.data
    if (Array.isArray(payload)) return payload
    const data = payload?.data ?? payload
    return data ?? []
  }

  async function saveFeed(data, plantId) {
    const response = await wipApi.storeFeed({ ...data, ...plantParams(plantId) })
    return response.data
  }

  async function saveRundown(data, plantId) {
    const response = await wipApi.storeRundown({ ...data, ...plantParams(plantId) })
    return response.data
  }

  async function cancelFeed(data, plantId) {
    const response = await wipApi.cancelFeed({ ...data, ...plantParams(plantId) })
    return response.data
  }

  async function cancelRundown(data, plantId) {
    const response = await wipApi.cancelRundown({ ...data, ...plantParams(plantId) })
    return response.data
  }

  async function saveMaterialDoc(data, plantId) {
    const response = await wipApi.saveMaterialDoc({ ...data, ...plantParams(plantId) })
    return response.data
  }

  async function saveSubTank(data, plantId) {
    const response = await wipApi.updateSubTank({ ...data, ...plantParams(plantId) })
    return response.data
  }

  return {
    loading,
    plantParams,
    fetchLatestFeed,
    fetchLatestRundown,
    fetchFeedLog,
    fetchRundownLog,
    fetchBalance,
    fetchOption,
    saveFeed,
    saveRundown,
    cancelFeed,
    cancelRundown,
    saveMaterialDoc,
    saveSubTank,
  }
})
