import { describe, it, expect, vi } from 'vitest'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn(),
  }
}))

import { getDashboardStats } from '@/api/dashboard'
import api from '@/api/axios'

describe('Dashboard API', () => {
  it('getDashboardStats calls GET /api/v1/dashboard/stats', () => {
    getDashboardStats()
    expect(api.get).toHaveBeenCalledWith('/api/v1/dashboard/stats')
  })
})
