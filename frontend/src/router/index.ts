import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
    },

    // ============================================================
    // Main Dashboard Routes
    // ============================================================
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/forward-trace',
      name: 'forward-trace',
      component: () => import('../views/dashboard/ForwardTraceView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/backward-trace',
      name: 'backward-trace',
      component: () => import('../views/dashboard/BackwardTraceView.vue'),
      meta: { requiresAuth: true }
    },

    // ============================================================
    // TS Transaction Routes
    // ============================================================
    {
      path: '/transaction/rm-entry',
      name: 'transaction-rm-entry',
      component: () => import('../views/transaction/RmEntryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/transaction/wip-entry',
      name: 'transaction-wip-entry',
      component: () => import('../views/transaction/WipEntryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/transaction/blending',
      name: 'transaction-blending',
      component: () => import('../views/transaction/BlendingView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/transaction/package-entry',
      name: 'transaction-package-entry',
      component: () => import('../views/transaction/PackageEntryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/transaction/shipment-entry',
      name: 'transaction-shipment-entry',
      component: () => import('../views/transaction/ShipmentEntryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/transaction/transfer',
      name: 'transaction-transfer',
      component: () => import('../views/transaction/TransferView.vue'),
      meta: { requiresAuth: true }
    },

    // ============================================================
    // TS Inquiry Routes
    // ============================================================
    {
      path: '/inquiry/stock',
      name: 'inquiry-stock',
      component: () => import('../views/inquiry/StockInquiryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/inquiry/ts-report',
      name: 'inquiry-ts-report',
      component: () => import('../views/inquiry/TsReportView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/inquiry/rm-report',
      name: 'inquiry-rm-report',
      component: () => import('../views/inquiry/RmReportView.vue'),
      meta: { requiresAuth: true }
    },

    // ============================================================
    // TS Setup Routes
    // ============================================================
    {
      path: '/setup/material',
      name: 'setup-material',
      component: () => import('../views/setup/material/MaterialSetupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/setup/storage',
      name: 'setup-storage',
      component: () => import('../views/setup/storage/StorageSetupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/setup/supplier',
      name: 'setup-supplier',
      component: () => import('../views/setup/supplier/SupplierSetupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/setup/adjustment',
      name: 'setup-adjustment',
      component: () => import('../views/setup/AdjustmentSetupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/setup/quantifier',
      name: 'setup-quantifier',
      component: () => import('../views/setup/QuantifierSetupView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/setup/plant',
      name: 'setup-plant',
      component: () => import('../views/setup/PlantSetupView.vue'),
      meta: { requiresAuth: true }
    },

    // ============================================================
    // Admin Setup Routes
    // ============================================================
    {
      path: '/admin/user-management',
      name: 'admin-user-management',
      component: () => import('../views/admin/UserManagementView.vue'),
      meta: { requiresAuth: true }
    },
  ],
})

export default router
