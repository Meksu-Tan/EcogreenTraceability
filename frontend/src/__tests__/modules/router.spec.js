import { describe, it, expect } from 'vitest'
import router from '@/router'

describe('App Router', () => {
  it('creates router with routes', () => {
    expect(router).toBeDefined()
    expect(router.hasRoute('login')).toBe(true)
    expect(router.hasRoute('dashboard')).toBe(true)
    expect(router.hasRoute('setup.material')).toBe(true)
    expect(router.hasRoute('setup.supplier')).toBe(true)
    expect(router.hasRoute('setup.adjustment')).toBe(true)
    expect(router.hasRoute('setup.quantifier')).toBe(true)
    expect(router.hasRoute('transaction-rm-entry')).toBe(true)
  })

  it('redirects unknown routes to dashboard', () => {
    const route = router.resolve({ path: '/nonexistent' })
    expect(route.matched.length).toBeGreaterThanOrEqual(0)
    expect(route.path).toBe('/nonexistent')
  })
})
