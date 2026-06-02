import { useDashboardStore } from './stores'

export default {
  name: 'dashboard',
  routes: [
    {
      path: 'dashboard',
      name: 'dashboard',
      component: () => import('./views/DashboardView.vue'),
      meta: { title: 'Dashboard', requiresAuth: true },
    },
    {
      path: 'forward-trace',
      name: 'forward-trace',
      component: () => import('./views/ForwardTraceView.vue'),
      meta: { title: 'Forward Trace', requiresAuth: true },
    },
    {
      path: 'backward-trace',
      name: 'backward-trace',
      component: () => import('./views/BackwardTraceView.vue'),
      meta: { title: 'Backward Trace', requiresAuth: true },
    },
  ],
  stores: [useDashboardStore],
}
