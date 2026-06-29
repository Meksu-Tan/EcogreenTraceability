import { ref } from 'vue'

export function useLoadingState() {
  const loading = ref(false)
  const error = ref(null)

  async function withLoading(fn) {
    loading.value = true
    error.value = null
    try {
      return await fn()
    } catch (e) {
      error.value = e?.response?.data?.message || e?.message || 'Terjadi kesalahan'
      throw e
    } finally {
      loading.value = false
    }
  }

  return { loading, error, withLoading }
}
