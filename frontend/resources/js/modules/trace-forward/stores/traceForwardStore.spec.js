import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/modules/trace-forward/services', () => ({
  default: {
    getForwardList: vi.fn(),
    getTraceDetail: vi.fn(),
  },
}))

import traceApi from '@/modules/trace-forward/services/index.js'
import { useTraceForwardStore } from './traceForwardStore'

describe('useTraceForwardStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchList', () => {
    it('populates list and meta on success', async () => {
      const mockResponse = {
        data: {
          data: [
            { id_balance_head: 1, trace_no: '100001-001', batch_sap: 'B001', traced: 'TRACED' },
            { id_balance_head: 2, trace_no: '100001-002', batch_sap: 'B002', traced: 'N/A' },
          ],
          total: 2,
          page: 1,
          per_page: 25,
          last_page: 1,
        },
      }
      traceApi.getForwardList.mockResolvedValue(mockResponse)

      const store = useTraceForwardStore()
      await store.fetchList({ page: 1, per_page: 25 })

      expect(store.list).toHaveLength(2)
      expect(store.list[0].trace_no).toBe('100001-001')
      expect(store.list[1].batch_sap).toBe('B002')
      expect(store.listMeta).toEqual({
        page: 1,
        perPage: 25,
        total: 2,
        lastPage: 1,
      })
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('sets error state and clears list on failure', async () => {
      traceApi.getForwardList.mockRejectedValue(new Error('Network error'))

      const store = useTraceForwardStore()
      await store.fetchList()

      expect(store.list).toEqual([])
      expect(store.error).toBe('Network error')
      expect(store.loading).toBe(false)
    })

    it('handles empty response gracefully', async () => {
      traceApi.getForwardList.mockResolvedValue({ data: { data: [] } })

      const store = useTraceForwardStore()
      await store.fetchList()

      expect(store.list).toEqual([])
      expect(store.listMeta.total).toBe(0)
    })

    it('passes params to api call', async () => {
      traceApi.getForwardList.mockResolvedValue({ data: { data: [] } })

      const store = useTraceForwardStore()
      await store.fetchList({ page: 2, per_page: 10, id_plant: '1002' })

      expect(traceApi.getForwardList).toHaveBeenCalledWith({
        page: 2,
        per_page: 10,
        id_plant: '1002',
      })
    })
  })

  describe('fetchDetail', () => {
    it('populates detail.initial and detail.chain on success', async () => {
      traceApi.getTraceDetail.mockResolvedValue({
        data: {
          data: {
            initial: [{ curr_trace: '100001-001', level: 1 }],
            chain: [
              { curr_trace: '200001-001', level: 2 },
              { curr_trace: '300001-001', level: 3 },
            ],
          },
        },
      })

      const store = useTraceForwardStore()
      await store.fetchDetail({ id_header: 1, trace_no: '100001-001', id_material: 5 })

      expect(store.detail.initial).toHaveLength(1)
      expect(store.detail.initial[0].curr_trace).toBe('100001-001')
      expect(store.detail.chain).toHaveLength(2)
      expect(store.detail.chain[1].level).toBe(3)
      expect(store.loadingDetail).toBe(false)
    })

    it('resets detail to empty on failure', async () => {
      traceApi.getTraceDetail.mockRejectedValue(new Error('timeout'))

      const store = useTraceForwardStore()
      await store.fetchDetail({ trace_no: 'X' })

      expect(store.detail).toEqual({ initial: [], chain: [] })
      expect(store.error).toBe('timeout')
    })
  })

  describe('clear', () => {
    it('resets all state to initial values', async () => {
      traceApi.getForwardList.mockResolvedValue({
        data: { data: [{ id_balance_head: 1 }], total: 1, page: 1, per_page: 25, last_page: 1 },
      })
      const store = useTraceForwardStore()
      await store.fetchList()
      expect(store.list.length).toBe(1)

      store.clear()

      expect(store.list).toEqual([])
      expect(store.detail).toEqual({ initial: [], chain: [] })
      expect(store.listMeta).toEqual({ page: 1, perPage: 10, total: 0, lastPage: 1 })
      expect(store.error).toBeNull()
    })
  })
})
