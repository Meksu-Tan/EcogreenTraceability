import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/modules/trace-backward/api', () => ({
  default: {
    getBackwardList: vi.fn(),
    getTraceDetail: vi.fn(),
    getDatShipment: vi.fn(),
    getShipmentBatchPackaging: vi.fn(),
    getPreparationRecord: vi.fn(),
    getDatSoAllocation: vi.fn(),
  },
}))

import traceApi from '@/modules/trace-backward/api'
import { useTraceBackwardStore } from './traceBackwardStore'

describe('useTraceBackwardStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchList', () => {
    it('populates list and meta on success', async () => {
      traceApi.getBackwardList.mockResolvedValue({
        data: {
          data: [
            { id_shipment_head: 1, trace_no: '300001-001', source: 'B001 :: 100001-001 / PO-001' },
            { id_shipment_head: 2, trace_no: '300001-002', source: 'B002 :: 100002-001 / PO-002' },
          ],
          total: 2,
          page: 1,
          per_page: 25,
          last_page: 1,
        },
      })

      const store = useTraceBackwardStore()
      await store.fetchList({ page: 1, per_page: 25 })

      expect(store.list).toHaveLength(2)
      expect(store.list[0].source).toContain('PO-001')
      expect(store.listMeta.total).toBe(2)
      expect(store.loading).toBe(false)
    })

    it('captures error from failed list call', async () => {
      traceApi.getBackwardList.mockRejectedValue(new Error('Server 500'))

      const store = useTraceBackwardStore()
      await store.fetchList()

      expect(store.list).toEqual([])
      expect(store.error).toBe('Server 500')
    })
  })

  describe('fetchDetail', () => {
    it('populates detail array on success', async () => {
      traceApi.getTraceDetail.mockResolvedValue({
        data: {
          data: [
            { curr_trace: '100001-001', level: 1, material: 'CPO' },
            { curr_trace: '200001-001', level: 2, material: 'Refined' },
          ],
        },
      })

      const store = useTraceBackwardStore()
      await store.fetchDetail({ trace_no: '300001-001', id_material: 3 })

      expect(Array.isArray(store.detail)).toBe(true)
      expect(store.detail).toHaveLength(2)
      expect(store.detail[0].material).toBe('CPO')
      expect(store.loadingDetail).toBe(false)
    })
  })

  describe('fetchShipmentDetail', () => {
    it('populates shipmentData from nested response', async () => {
      traceApi.getDatShipment.mockResolvedValue({
        data: { data: { data: { SO_NO: 'SO-001', BATCH: 'B-001' } } },
      })

      const store = useTraceBackwardStore()
      await store.fetchShipmentDetail({ batchNo: 'B-001', soNo: 'SO-001' })

      expect(store.shipmentData).toEqual({ SO_NO: 'SO-001', BATCH: 'B-001' })
      expect(store.loadingShipment).toBe(false)
    })

    it('sets null shipmentData on error', async () => {
      traceApi.getDatShipment.mockRejectedValue(new Error('SAP down'))

      const store = useTraceBackwardStore()
      await store.fetchShipmentDetail({})

      expect(store.shipmentData).toBeNull()
      expect(store.error).toBe('SAP down')
    })
  })

  describe('fetchBatchPackaging', () => {
    it('orchestrates 3 parallel api calls', async () => {
      traceApi.getShipmentBatchPackaging.mockResolvedValue({
        data: { data: [{ batch_no: 'B-001', packaging: 'drum' }] },
      })
      traceApi.getPreparationRecord.mockResolvedValue({
        data: { data: [{ prep_no: 'P-001' }, { prep_no: 'P-002' }] },
      })
      traceApi.getDatSoAllocation.mockResolvedValue({
        data: { data: { data: { IT_EXPORT: [{ so_item: '00010' }, { so_item: '00020' }] } } },
      })

      const store = useTraceBackwardStore()
      await store.fetchBatchPackaging({ batchNo: 'B-001' })

      expect(store.batchData).toEqual({ batch_no: 'B-001', packaging: 'drum' })
      expect(store.preparationRecords).toHaveLength(2)
      expect(store.sapAllocations).toHaveLength(2)
      expect(store.loadingBatch).toBe(false)
    })

    it('handles empty batch packaging result', async () => {
      traceApi.getShipmentBatchPackaging.mockResolvedValue({ data: { data: [] } })
      traceApi.getPreparationRecord.mockResolvedValue({ data: { data: [] } })
      traceApi.getDatSoAllocation.mockResolvedValue({
        data: { data: { data: { IT_EXPORT: [] } } },
      })

      const store = useTraceBackwardStore()
      await store.fetchBatchPackaging({ batchNo: 'X' })

      expect(store.batchData).toBeNull()
      expect(store.preparationRecords).toEqual([])
      expect(store.sapAllocations).toEqual([])
    })
  })

  describe('clear', () => {
    it('resets all state to initial values', async () => {
      traceApi.getBackwardList.mockResolvedValue({
        data: { data: [{ id_shipment_head: 1 }], total: 1, page: 1, per_page: 25, last_page: 1 },
      })
      const store = useTraceBackwardStore()
      await store.fetchList()
      expect(store.list.length).toBe(1)

      store.clear()

      expect(store.list).toEqual([])
      expect(store.detail).toEqual([])
      expect(store.shipmentData).toBeNull()
      expect(store.batchData).toBeNull()
      expect(store.preparationRecords).toEqual([])
      expect(store.sapAllocations).toEqual([])
      expect(store.error).toBeNull()
    })
  })
})
