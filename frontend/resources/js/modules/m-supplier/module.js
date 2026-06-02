import { useSetupSupplierStore } from './stores'

export default {
  name: 'm-supplier',
  routes: [
    {
      path: 'setup/supplier',
      name: 'setup.supplier',
      component: () => import('./views/SupplierIndex.vue'),
      meta: { title: 'Setup Supplier', requiresAuth: true },
    },
  ],
  stores: [useSetupSupplierStore],
}
