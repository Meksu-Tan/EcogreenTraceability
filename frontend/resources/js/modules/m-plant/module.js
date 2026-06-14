import { useSetupPlantStore, usePlantSelectionStore } from '@/stores/plant.js'

export default {
  name: 'm-plant',
  routes: [
    {
      path: 'setup/plant',
      name: 'setup.plant',
      component: () => import('./views/PlantSetupView.vue'),
      meta: { title: 'Plant Setup', requiresAuth: true },
    },
  ],
  stores: [useSetupPlantStore, usePlantSelectionStore],
}
