import { useSetupManufacturerStore } from './stores'

export default {
  name: 'manufacturer',
  routes: [
    {
      path: 'setup/manufacturer',
      name: 'setup.manufacturer',
      component: () => import('./views/ManufacturerIndex.vue'),
      meta: { title: 'Setup Manufacturer', requiresAuth: true },
    },
  ],
  stores: [useSetupManufacturerStore],
}
