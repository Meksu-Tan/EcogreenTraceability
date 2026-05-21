import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div>Home</div>' } }],
})

describe('AppLayout', () => {
  it('renders without errors', () => {
    setActivePinia(createPinia())
    const wrapper = mount(AppLayout, {
      global: { plugins: [router] },
    })
    expect(wrapper.exists()).toBe(true)
  })
})
