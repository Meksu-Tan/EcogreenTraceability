import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

vi.mock('@/api/axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: { data: [] } }),
    post: vi.fn().mockResolvedValue({ data: {} }),
  }
}))

vi.mock('/logo-stacked.jpg', () => ({
  default: ''
}))

vi.mock('@/layouts/components/NavItems.vue', () => ({
  default: { template: '<div data-test="nav-items" />' },
}))

vi.mock('@/layouts/components/ThemeSwitcher.vue', () => ({
  default: { template: '<button data-test="theme-switcher" />' },
}))

vi.mock('@/layouts/components/UserProfile.vue', () => ({
  default: { template: '<div data-test="user-profile" />' },
}))

vi.mock('@/modules/shared/components/AppToast.vue', () => ({
  default: { template: '<div data-test="app-toast" />' },
}))

vi.mock('@/modules/shared/components/ConfirmDialog.vue', () => ({
  default: { template: '<div data-test="confirm-dialog" />' },
}))

import AppLayout from '@/layouts/AppLayout.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div>Home</div>' } }],
})

describe('AppLayout', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders without errors', () => {
    const wrapper = mount(AppLayout, {
      global: {
        plugins: [router],
        stubs: [
          'VApp',
          'VNavigationDrawer',
          'VDivider',
          'VBtn',
          'VAppBar',
          'VAppBarNavIcon',
          'VToolbarTitle',
          'VSpacer',
          'VMain',
          'RouterView',
        ],
      },
    })
    expect(wrapper.exists()).toBe(true)
  })
})
