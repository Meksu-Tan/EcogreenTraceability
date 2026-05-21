import { describe, it, expect, vi } from 'vitest'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  }
}))

import { login, logout, getAuthUser } from '@/api/auth'
import api from '@/api/axios'

describe('Auth API', () => {
  it('login calls POST /api/v1/login with credentials', () => {
    login({ email: 'test@test.com', password: 'secret' })
    expect(api.post).toHaveBeenCalledWith('/api/v1/login', { email: 'test@test.com', password: 'secret' })
  })

  it('logout calls POST /api/v1/logout', () => {
    logout()
    expect(api.post).toHaveBeenCalledWith('/api/v1/logout')
  })

  it('getAuthUser calls GET /api/v1/user', () => {
    getAuthUser()
    expect(api.get).toHaveBeenCalledWith('/api/v1/user')
  })
})
