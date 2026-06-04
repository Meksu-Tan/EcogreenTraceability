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
    // Trace routes have been moved to dedicated modules:
    // - trace-forward: /forward-trace
    // - trace-backward: /backward-trace
  ],
  stores: [useDashboardStore],
}
