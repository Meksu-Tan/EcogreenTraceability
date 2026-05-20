import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { usePlantSelectionStore } from '@/stores/plantSelection'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ──────────────────────────────────────────────────────
    // Auth routes (no sidebar)
    // ──────────────────────────────────────────────────────
    {
      path: '/auth',
      component: () => import('@/layouts/AuthLayout.vue'),
      meta: { requiresGuest: true },
      children: [
        {
          path: '/login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
        },
      ]
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
        {
          path: 'setup/storage/:id/details',
          name: 'setup.storage.detail',
          component: () => import('@/views/setup/storage/StorageTankDetailView.vue'),
          meta: { title: 'Storage Tank Detail', requiresAuth: true },
          props: true
        },

        // Setup Supplier
        {
          path: 'setup/supplier',
          name: 'setup.supplier',
          component: () => import('@/views/setup/supplier/SupplierIndex.vue'),
          meta: { title: 'Setup Supplier', requiresAuth: true },
        },

        // Setup Tank
        {
          path: 'setup/tank',
          name: 'setup.tank',
          component: () => import('@/views/setup/TankSetupView.vue'),
          meta: { title: 'Setup Tank', requiresAuth: true },
        },

        // Setup Manufacturer
        {
          path: 'setup/manufacturer',
          name: 'setup.manufacturer',
          component: () => import('@/views/setup/manufacturer/ManufacturerIndex.vue'),
          meta: { title: 'Setup Manufacturer', requiresAuth: true },
        },

        // Dashboard Traces
        {
          path: 'forward-trace',
          name: 'forward-trace',
          component: () => import('@/views/dashboard/ForwardTraceView.vue'),
          meta: { title: 'Forward Trace', requiresAuth: true },
        },
        {
          path: 'backward-trace',
          name: 'backward-trace',
          component: () => import('@/views/dashboard/BackwardTraceView.vue'),
          meta: { title: 'Backward Trace', requiresAuth: true },
        },

        // Transaction Routes
        {
          path: 'transaction/rm-entry',
          name: 'transaction-rm-entry',
          component: () => import('@/views/transaction/RmEntryView.vue'),
          meta: { title: 'RM Entry', requiresAuth: true },
        },
        {
          path: 'transaction/wip-entry',
          name: 'transaction-wip-entry',
          component: () => import('@/views/transaction/WipEntryView.vue'),
          meta: { title: 'WIP Entry', requiresAuth: true },
        },
        {
          path: 'transaction/blending',
          name: 'transaction-blending',
          component: () => import('@/views/transaction/BlendingView.vue'),
          meta: { title: 'Blending', requiresAuth: true },
        },
        {
          path: 'transaction/package-entry',
          name: 'transaction-package-entry',
          component: () => import('@/views/transaction/PackageEntryView.vue'),
          meta: { title: 'Package Entry', requiresAuth: true },
        },
        {
          path: 'transaction/shipment-entry',
          name: 'transaction-shipment-entry',
          component: () => import('@/views/transaction/ShipmentEntryView.vue'),
          meta: { title: 'Shipment Entry', requiresAuth: true },
        },
        {
          path: 'transaction/transfer',
          name: 'transaction-transfer',
          component: () => import('@/views/transaction/TransferView.vue'),
          meta: { title: 'Transfer', requiresAuth: true },
        },

        // Inquiry Routes
        {
          path: 'inquiry/stock',
          name: 'inquiry-stock',
          component: () => import('@/views/inquiry/StockInquiryView.vue'),
          meta: { title: 'Stock Inquiry', requiresAuth: true },
        },
        {
          path: 'inquiry/ts-report',
          name: 'inquiry-ts-report',
          component: () => import('@/views/inquiry/TsReportView.vue'),
          meta: { title: 'TS Report', requiresAuth: true },
        },
        {
          path: 'inquiry/rm-report',
          name: 'inquiry-rm-report',
          component: () => import('@/views/inquiry/RmReportView.vue'),
          meta: { title: 'RM Report', requiresAuth: true },
        },

        // Additional Setup Routes
        {
          path: 'setup/adjustment',
          name: 'setup-adjustment',
          component: () => import('@/views/setup/AdjustmentSetupView.vue'),
          meta: { title: 'Adjustment Setup', requiresAuth: true },
        },
        {
          path: 'setup/quantifier',
          name: 'setup-quantifier',
          component: () => import('@/views/setup/QuantifierSetupView.vue'),
          meta: { title: 'Quantifier Setup', requiresAuth: true },
        },
        {
          path: 'setup/plant',
          name: 'setup-plant',
          component: () => import('@/views/setup/PlantSetupView.vue'),
          meta: { title: 'Plant Setup', requiresAuth: true },
        },

        // Admin Routes
        {
          path: 'admin/user-management',
          name: 'admin-user-management',
          component: () => import('@/views/admin/UserManagementView.vue'),
          meta: { title: 'User Management', requiresAuth: true },
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
router.beforeEach(async (to) => {
  const authStore = useAuthStore()
  const plantSelectionStore = usePlantSelectionStore()
  
  // Initialize user if not already authenticated (e.g. on page refresh)
  if (!authStore.isAuthenticated && to.meta.requiresAuth) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  } 
  
  if (to.path === '/login' && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  // Clear plant selection when going back to key pages (as requested)
  const resetRoutes = ['dashboard', 'login', 'setup', 'admin']
  if (resetRoutes.some(r => to.path.includes(r))) {
    plantSelectionStore.clearPlant()
  }

  return true
})

export default router
