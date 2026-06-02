import { useTsRawRmEntryStore } from './stores'

export default {
  name: 'ts-raw',
  routes: [
    {
      path: 'ts-raw/rm-entry',
      name: 'ts-raw-rm-entry',
      component: () => import('./views/RmEntryView.vue'),
      meta: { title: 'RM Entry', requiresAuth: true },
    },
  ],
  stores: [useTsRawRmEntryStore],
}
