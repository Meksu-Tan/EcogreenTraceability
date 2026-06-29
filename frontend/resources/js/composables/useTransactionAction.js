import { useLoadingState } from '@/composables/useLoadingState'
import { useToastStore } from '@/stores/toast'

export function useTransactionAction(apiFn, refreshFn, successMsg = 'Berhasil') {
  const { loading, error } = useLoadingState()
  const toastStore = useToastStore()

  async function execute(...args) {
    try {
      const res = await apiFn(...args)
      await refreshFn()
      toastStore.show(res?.data?.message || successMsg)
      return res
    } catch (e) {
      const msg = e?.response?.data?.message || e?.message || 'Gagal'
      toastStore.error(msg)
      throw e
    }
  }

  return { loading, error, execute }
}
