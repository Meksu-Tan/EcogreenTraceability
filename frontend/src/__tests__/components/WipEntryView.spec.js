import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: { data: [] } }),
    post: vi.fn().mockResolvedValue({ data: {} }),
  }
}))

vi.mock('@/modules/m-plant/api', () => ({
  getPlants: vi.fn().mockResolvedValue({ data: { data: [] } }),
}))

import WipEntryView from '@/modules/ts-wip/views/WipEntryView.vue'

describe('WipEntryView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders without errors', () => {
    const wrapper = mount(WipEntryView, {
      global: {
        stubs: {
          PlantSelector: true,
          BaseModal: true,
          WipMiniTable: true,
        }
      }
    })
    expect(wrapper.exists()).toBe(true)
  })
})
