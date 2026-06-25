import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMaterialStore } from '../materialStore'

vi.mock('@/modules/m-material/services/materialService', () => ({
  default: {
    getAll: vi.fn().mockResolvedValue({ data: { data: [] } }),
  },
}))

describe('materialStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initial state is empty', () => {
    const store = useMaterialStore()
    expect(store.items).toEqual([])
    expect(store.loading).toBe(false)
  })

  it('fetchAll loads items', async () => {
    const store = useMaterialStore()
    expect(store.loading).toBe(false)
    const promise = store.fetchAll()
    expect(store.loading).toBe(true)
    await promise
    expect(store.loading).toBe(false)
  })
})
