import { useStockStore } from './stores/stockStore'

export default {
  name: 'ts-stock',
  routes: [
    {
      path: 'ts-stock/stock',
      name: 'ts-stock-stock',
      component: () => import('./views/StockInquiryView.vue'),
      meta: { title: 'Stock Inquiry', requiresAuth: true },
    },
  ],
  stores: [useStockStore],
}
