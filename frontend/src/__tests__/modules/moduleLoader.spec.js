import { describe, it, expect } from 'vitest'
import { MODULE_NAMES } from '@/modules'

describe('Module Registry', () => {
  it('exports known module names', () => {
    expect(MODULE_NAMES).toContain('auth')
    expect(MODULE_NAMES).toContain('material')
    expect(MODULE_NAMES).toContain('storage')
    expect(MODULE_NAMES).toContain('transaction')
    expect(MODULE_NAMES).toContain('dashboard')
    expect(MODULE_NAMES).toContain('admin')
  })

  it('has the correct number of modules', () => {
    expect(MODULE_NAMES.length).toBeGreaterThanOrEqual(10)
  })
})
