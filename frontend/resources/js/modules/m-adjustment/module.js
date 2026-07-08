import { useAdjustmentStore } from './stores/adjustmentStore'

export default {
  name: 'm-adjustment',
  routes: [
    {
      path: 'adjustment',
      name: 'setup-adjustment',
      component: () => import('./views/AdjustmentView.vue'),
      meta: { title: 'Adjustment', requiresAuth: true },
    },

  ],
  stores: [useAdjustmentStore],
}
