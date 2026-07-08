import { useAcknowledgeStore } from './stores/acknowledgeStore.js'

export default {
  name: 'ts-acknowledge',
  routes: [
    {
      path: 'ts-acknowledge',
      name: 'ts-acknowledge.dashboard',
      component: () => import('./views/AcknowledgeDashboardView.vue'),
      meta: { title: 'Acknowledge Dashboard', requiresAuth: true },
    },
  ],
  stores: [useAcknowledgeStore],
}
