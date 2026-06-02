import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMaterialStore } from '@/modules/m-material/stores/materialStore'

vi.mock('@/modules/m-material/api', () => ({
  getMaterials: vi.fn(),
  storeMaterial: vi.fn(),
  updateMaterial: vi.fn(),
}))

import * as MaterialApi from '@/modules/m-material/api'

describe('materialStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('starts with empty items and not loading', () => {
    const store = useMaterialStore()
    expect(store.items).toEqual([])
    expect(store.loading).toBe(false)
  })

  it('fetchAll populates items', async () => {
    MaterialApi.getMaterials.mockResolvedValue({ data: [{ id: 1, code: 'MAT-001' }] })
    const store = useMaterialStore()
    await store.fetchAll()
    expect(store.items).toEqual([{ id: 1, code: 'MAT-001' }])
    expect(store.loading).toBe(false)
  })

  it('create calls storeMaterial and refetches', async () => {
    MaterialApi.storeMaterial.mockResolvedValue({ data: { status: 1 } })
    MaterialApi.getMaterials.mockResolvedValue({ data: [{ id: 2, code: 'MAT-002' }] })
    const store = useMaterialStore()
    await store.create({ code: 'MAT-002' })
    expect(MaterialApi.storeMaterial).toHaveBeenCalledWith({ code: 'MAT-002' })
    expect(store.items).toEqual([{ id: 2, code: 'MAT-002' }])
  })

  it('update calls updateMaterial and refetches', async () => {
    MaterialApi.updateMaterial.mockResolvedValue({ data: { status: 1 } })
    MaterialApi.getMaterials.mockResolvedValue({ data: [{ id: 1, code: 'MAT-001-UPDATED' }] })
    const store = useMaterialStore()
    await store.update(1, { code: 'MAT-001-UPDATED' })
    expect(MaterialApi.updateMaterial).toHaveBeenCalledWith(1, { code: 'MAT-001-UPDATED' })
    expect(store.items).toEqual([{ id: 1, code: 'MAT-001-UPDATED' }])
  })
})
