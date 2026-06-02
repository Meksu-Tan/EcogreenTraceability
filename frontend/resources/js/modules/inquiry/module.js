export default {
  name: 'inquiry',
  routes: [
    {
      path: 'inquiry/stock',
      name: 'inquiry-stock',
      component: () => import('./views/StockInquiryView.vue'),
      meta: { title: 'Stock Inquiry', requiresAuth: true },
    },
    {
      path: 'inquiry/ts-report',
      name: 'inquiry-ts-report',
      component: () => import('./views/TsReportView.vue'),
      meta: { title: 'TS Report', requiresAuth: true },
    },
    {
      path: 'inquiry/rm-report',
      name: 'inquiry-rm-report',
      component: () => import('./views/RmReportView.vue'),
      meta: { title: 'RM Report', requiresAuth: true },
    },
  ],
  stores: [],
}
