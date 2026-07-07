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
    {
      path: 'adjustment/approval',
      name: 'setup-adjustment-approval',
      component: () => import('./views/ApprovalView.vue'),
      meta: { title: 'Adjustment Approval', requiresAuth: true },
    },
  ],
  stores: [useAdjustmentStore],
}
