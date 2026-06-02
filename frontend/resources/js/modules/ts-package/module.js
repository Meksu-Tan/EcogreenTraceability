export default {
  name: 'ts-package',
  routes: [
    {
      path: 'ts-package/package-entry',
      name: 'ts-package-package-entry',
      component: () => import('./views/PackageEntryView.vue'),
      meta: { title: 'Package Entry', requiresAuth: true },
    },
  ],
  stores: [],
}
