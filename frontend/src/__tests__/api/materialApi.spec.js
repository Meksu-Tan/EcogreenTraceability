import { describe, it, expect, vi } from 'vitest'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  }
}))

import * as materialApi from '@/api/setupMaterial'
import api from '@/api/axios'

describe('Material API', () => {
  it('getMaterials calls GET /api/v1/materials', () => {
    materialApi.getMaterials()
    expect(api.get).toHaveBeenCalledWith('/api/v1/materials')
  })

  it('storeMaterial calls POST /api/v1/materials', () => {
    materialApi.storeMaterial({ code: 'MAT-001' })
    expect(api.post).toHaveBeenCalledWith('/api/v1/materials', { code: 'MAT-001' })
  })

  it('updateMaterial calls PUT /api/v1/materials/{id}', () => {
    materialApi.updateMaterial(1, { code: 'MAT-001' })
    expect(api.put).toHaveBeenCalledWith('/api/v1/materials/1', { code: 'MAT-001' })
  })

  it('deactivateMaterial calls DELETE with action=deactivate', () => {
    materialApi.deactivateMaterial(1)
    expect(api.delete).toHaveBeenCalledWith('/api/v1/materials/1?action=deactivate')
  })
})
