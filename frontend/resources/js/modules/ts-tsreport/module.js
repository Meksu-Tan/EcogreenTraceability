import { useTsReportStore } from './stores/tsReportStore'

export default {
  name: 'ts-tsreport',
  routes: [
    {
      path: 'ts-tsreport/ts-report',
      name: 'ts-tsreport-ts-report',
      component: () => import('./views/TsReportView.vue'),
      meta: { title: 'TS Report', requiresAuth: true },
    },
  ],
  stores: [useTsReportStore],
}
