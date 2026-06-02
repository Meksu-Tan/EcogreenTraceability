import { useSetupMaterialStore } from './stores'

export default {
  name: 'm-material',
  routes: [
    {
      path: 'setup/material',
      name: 'setup.material',
      component: () => import('./views/MaterialIndex.vue'),
      meta: { title: 'Setup Material', requiresAuth: true },
    },
  ],
  stores: [useSetupMaterialStore],
}
