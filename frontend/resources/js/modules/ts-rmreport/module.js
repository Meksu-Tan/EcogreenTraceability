import { useRmReportStore } from './stores/rmReportStore'

export default {
  name: 'ts-rmreport',
  routes: [
    {
      path: 'ts-rmreport/rm-report',
      name: 'ts-rmreport-rm-report',
      component: () => import('./views/RmReportView.vue'),
      meta: { title: 'RM Report', requiresAuth: true },
    },
  ],
  stores: [useRmReportStore],
}
