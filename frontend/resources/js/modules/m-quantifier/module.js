import { useQuantifierStore } from './stores/quantifierStore'

export default {
  name: 'm-quantifier',
  routes: [
    {
      path: 'qtfsetup',
      name: 'setup-quantifier',
      component: () => import('./views/QuantifierView.vue'),
      meta: { title: 'Quantifier', requiresAuth: true },
    },
  ],
  stores: [useQuantifierStore],
}
