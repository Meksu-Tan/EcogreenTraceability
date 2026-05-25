import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import WipEntryView from '@/views/ts-wip/WipEntryView.vue'

describe('WipEntryView', () => {
  it('renders without errors', () => {
    setActivePinia(createPinia())
    const wrapper = mount(WipEntryView)
    expect(wrapper.exists()).toBe(true)
  })
})
