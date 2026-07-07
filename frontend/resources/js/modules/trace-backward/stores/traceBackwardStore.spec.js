import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

vi.mock('@/modules/trace-backward/services', () => ({
  default: {
    getBackwardList: vi.fn(),
    getTraceDetail: vi.fn(),
  },
}))

vi.mock('@/modules/ts-shipment/services/shipmentService', () => ({
  default: {
    getDatShipment: vi.fn(),
    getShipmentBatchPackaging: vi.fn(),
    getPreparationRecord: vi.fn(),
    getDatSoAllocation: vi.fn(),
  },
}))

import traceApi from '@/modules/trace-backward/services/index.js'
import shipmentService from '@/modules/ts-shipment/services/shipmentService'
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
      await expect(store.fetchList()).rejects.toThrow('Server 500')

      expect(store.list).toEqual([])
      expect(store.error).toBe('Server 500')
    })
  })

  describe('fetchDetail', () => {
    it('populates detail object with initial and chain on success', async () => {
      traceApi.getTraceDetail.mockResolvedValue({
        data: {
          data: {
            initial: [{ curr_trace: '100001-001', level: 1, material: 'CPO' }],
            chain: [{ curr_trace: '200001-001', level: 2, material: 'Refined' }],
          },
        },
      })

      const store = useTraceBackwardStore()
      await store.fetchDetail({ trace_no: '300001-001', id_material: 3 })

      expect(Array.isArray(store.detail.initial)).toBe(true)
      expect(store.detail.initial).toHaveLength(1)
      expect(store.detail.initial[0].material).toBe('CPO')
      expect(store.detail.chain).toHaveLength(1)
      expect(store.loadingDetail).toBe(false)
    })
  })

  describe('fetchShipmentDetail', () => {
    it('populates shipmentData from nested response', async () => {
      shipmentService.getDatShipment.mockResolvedValue({
        data: { data: { SO_NO: 'SO-001', BATCH: 'B-001' } },
      })

      const store = useTraceBackwardStore()
      await store.fetchShipmentDetail({ batchNo: 'B-001', soNo: 'SO-001' })

      expect(store.shipmentData).toEqual({ SO_NO: 'SO-001', BATCH: 'B-001' })
      expect(store.loadingShipment).toBe(false)
    })

    it('sets null shipmentData on error', async () => {
      shipmentService.getDatShipment.mockRejectedValue(new Error('SAP down'))

      const store = useTraceBackwardStore()
      await store.fetchShipmentDetail({})

      expect(store.shipmentData).toBeNull()
      expect(store.error).toBe('SAP down')
    })
  })

  describe('fetchBatchPackaging', () => {
    it('orchestrates 3 parallel api calls', async () => {
      shipmentService.getShipmentBatchPackaging.mockResolvedValue({
        data: { data: [{ batch_no: 'B-001', packaging: 'drum' }] },
      })
      shipmentService.getPreparationRecord.mockResolvedValue({
        data: { data: [{ prep_no: 'P-001' }, { prep_no: 'P-002' }] },
      })
      shipmentService.getDatSoAllocation.mockResolvedValue({
        data: { data: [{ so_item: '00010' }, { so_item: '00020' }] },
      })

      const store = useTraceBackwardStore()
      await store.fetchBatchPackaging({ batchNo: 'B-001' })

      expect(store.batchList).toEqual([{ batch_no: 'B-001', packaging: 'drum' }])
      expect(store.preparationRecords).toHaveLength(2)
      expect(store.sapAllocations).toHaveLength(2)
      expect(store.loadingBatch).toBe(false)
    })

    it('handles empty batch packaging result', async () => {
      shipmentService.getShipmentBatchPackaging.mockResolvedValue({ data: { data: [] } })
      shipmentService.getPreparationRecord.mockResolvedValue({ data: { data: [] } })
      shipmentService.getDatSoAllocation.mockResolvedValue({
        data: { data: [] },
      })

      const store = useTraceBackwardStore()
      await store.fetchBatchPackaging({ batchNo: 'X' })

      expect(store.batchList).toEqual([])
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
       expect(store.detail).toEqual({ initial: [], chain: [] })
       expect(store.shipmentData).toBeNull()
       expect(store.batchList).toEqual([])
       expect(store.preparationRecords).toEqual([])
       expect(store.sapAllocations).toEqual([])
     })
  })
})
