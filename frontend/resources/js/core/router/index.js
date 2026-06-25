import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { usePlantSelectionStore } from '@/stores/plant.js'

const baseRoutes = [
  {
    path: '/auth',
    component: () => import('@/layouts/AuthLayout.vue'),
    meta: { requiresGuest: true },
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/modules/auth/views/LoginView.vue'),
      },
      {
        path: 'plant-selection',
        name: 'plant-selection',
        component: () => import('@/modules/auth/views/PlantSelectionView.vue'),
      },
    ],
  },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

let router = null

export function createAppRouter(moduleRoutes = []) {
  const appChildren = baseRoutes[1].children

  for (const route of moduleRoutes) {
    const path = route.path.startsWith('/') ? route.path.slice(1) : route.path
    appChildren.push({ ...route, path })
  }

  router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: baseRoutes,
    scrollBehavior() {
      return { top: 0, left: 0 }
    },
  })

  router.beforeEach(async (to) => {
    const authStore = useAuthStore()
    const plantSelectionStore = usePlantSelectionStore()

    const hasToken = localStorage.getItem('auth_token')

    if (to.meta.requiresAuth && !hasToken) {
      return { name: 'login' }
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated && hasToken) {
      await authStore.initialize()
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return { name: 'login' }
    }

    if (to.name === 'login' && authStore.isAuthenticated) {
      return { name: 'dashboard' }
    }

    if (to.name === 'plant-selection') {
      plantSelectionStore.clearPlant()
    }

    return true
  })

  return router
}

export function getRouter() {
  return router
}
