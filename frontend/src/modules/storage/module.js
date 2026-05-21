import { useSetupStorageStore } from './stores'

export default {
  name: 'storage',
  routes: [
    {
      path: 'setup/storage',
      name: 'setup.storage',
      component: () => import('./views/StorageIndex.vue'),
      meta: { title: 'Setup Storage', requiresAuth: true },
    },
    {
      path: 'setup/storage/:id/details',
      name: 'setup.storage.detail',
      component: () => import('./views/StorageTankDetailView.vue'),
      meta: { title: 'Storage Tank Detail', requiresAuth: true },
      props: true,
    },
  ],
  stores: [useSetupStorageStore],
}
