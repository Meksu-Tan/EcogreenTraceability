import { useSetupPlantStore, usePlantSelectionStore } from './stores'

export default {
  name: 'plant',
  routes: [
    {
      path: 'setup/plant',
      name: 'setup-plant',
      component: () => import('./views/PlantSetupView.vue'),
      meta: { title: 'Plant Setup', requiresAuth: true },
    },
    {
      path: 'setup/adjustment',
      name: 'setup.adjustment',
      component: () => import('./views/AdjustmentSetupView.vue'),
      meta: { title: 'Adjustment Setup', requiresAuth: true },
    },
    {
      path: 'setup/quantifier',
      name: 'setup.quantifier',
      component: () => import('./views/QuantifierSetupView.vue'),
      meta: { title: 'Quantifier Setup', requiresAuth: true },
    },
  ],
  stores: [useSetupPlantStore, usePlantSelectionStore],
}
