import { useTsWipEntryStore } from './stores/wip'

export default {
  name: 'ts-wip',
  prefix: 'ts-wip',
  routes: [
    {
      path: '/ts-wip/wip-entry',
      name: 'ts-wip-wip-entry',
      component: () => import('./views/WipEntryView.vue'),
      meta: { title: 'WIP Entry', requiresAuth: true },
    },
  ],
  stores: [useTsWipEntryStore],
  // G4: dashboard_url for WIP module
  dashboard_url: '/ts-wip/wip-entry',
}
