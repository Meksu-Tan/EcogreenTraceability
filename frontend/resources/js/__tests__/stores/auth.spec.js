import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/modules/auth/services', () => ({
  login: vi.fn(),
  logout: vi.fn().mockResolvedValue({}),
  getAuthUser: vi.fn().mockRejectedValue(new Error('not authenticated')),
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('starts unauthenticated', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('hasRole and hasPermission return false when unauthenticated', () => {
    const store = useAuthStore()
    expect(store.hasRole('admin')).toBe(false)
    expect(store.hasPermission('edit')).toBe(false)
  })

  it('logout clears user data', async () => {
    const store = useAuthStore()
    localStorage.setItem('auth_token', 'test-token')
    await store.logout()
    expect(store.isAuthenticated).toBe(false)
    expect(localStorage.getItem('auth_token')).toBeNull()
  })
})
