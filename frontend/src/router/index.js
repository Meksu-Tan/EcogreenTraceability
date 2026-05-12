import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ──────────────────────────────────────────────────────
    // Auth routes (no sidebar)
    // ──────────────────────────────────────────────────────
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { requiresGuest: true },
    },

    // ──────────────────────────────────────────────────────
    // App routes (with sidebar layout)
    // ──────────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard',
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('@/views/dashboard/DashboardView.vue'),
          meta: { title: 'Dashboard', requiresAuth: true },
        },

        // Setup Material
        {
          path: 'setup/material',
          name: 'setup.material',
          component: () => import('@/views/setup/material/MaterialIndex.vue'),
          meta: { title: 'Setup Material', requiresAuth: true },
        },

        // Setup Storage
        {
          path: 'setup/storage',
          name: 'setup.storage',
          component: () => import('@/views/setup/storage/StorageIndex.vue'),
          meta: { title: 'Setup Storage', requiresAuth: true },
        },

        // Setup Supplier
        {
          path: 'setup/supplier',
          name: 'setup.supplier',
          component: () => import('@/views/setup/supplier/SupplierIndex.vue'),
          meta: { title: 'Setup Supplier', requiresAuth: true },
        },
      ],
    },

    // 404 fallback
    {
      path: '/:pathMatch(.*)*',
      redirect: '/dashboard',
    },
  ],
})

// ──────────────────────────────────────────────────────────
// Navigation Guard
// ──────────────────────────────────────────────────────────
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // If authenticated state is unknown, try fetching from server
  if (!authStore.isAuthenticated && to.meta.requiresAuth) {
    const ok = await authStore.fetchUser()
    if (!ok) {
      return next({ name: 'login' })
    }
  }

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' })
  }

  next()
})

export default router
