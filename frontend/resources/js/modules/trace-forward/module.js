import { useTraceForwardStore } from './stores/traceForwardStore'

export default {
  name: 'trace-forward',
  routes: [
    {
      path: 'forward-trace',
      name: 'forward-trace',
      component: () => import('./views/ForwardTraceView.vue'),
      meta: { title: 'Forward Trace', requiresAuth: true },
    },
  ],
  stores: [useTraceForwardStore],
}
