import { useTraceBackwardStore } from './stores/traceBackwardStore'

export default {
  name: 'trace-backward',
  routes: [
    {
      path: 'backward-trace',
      name: 'backward-trace',
      component: () => import('./views/BackwardTraceView.vue'),
      meta: { title: 'Backward Trace', requiresAuth: true },
    },
  ],
  stores: [useTraceBackwardStore],
}
