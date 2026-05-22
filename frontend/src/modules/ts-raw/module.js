import { useTsRawRmEntryStore, useTsRawTransferStore } from './stores'

export default {
  name: 'ts-raw',
  routes: [
    {
      path: 'ts-raw/rm-entry',
      name: 'ts-raw-rm-entry',
      component: () => import('./views/RmEntryView.vue'),
      meta: { title: 'RM Entry', requiresAuth: true },
    },
    {
      path: 'ts-raw/wip-entry',
      name: 'ts-raw-wip-entry',
      component: () => import('./views/WipEntryView.vue'),
      meta: { title: 'WIP Entry', requiresAuth: true },
    },
    {
      path: 'ts-raw/blending',
      name: 'ts-raw-blending',
      component: () => import('./views/BlendingView.vue'),
      meta: { title: 'Blending', requiresAuth: true },
    },
    {
      path: 'ts-raw/package-entry',
      name: 'ts-raw-package-entry',
      component: () => import('./views/PackageEntryView.vue'),
      meta: { title: 'Package Entry', requiresAuth: true },
    },
    {
      path: 'ts-raw/shipment-entry',
      name: 'ts-raw-shipment-entry',
      component: () => import('./views/ShipmentEntryView.vue'),
      meta: { title: 'Shipment Entry', requiresAuth: true },
    },
    {
      path: 'ts-raw/transfer',
      name: 'ts-raw-transfer',
      component: () => import('./views/TransferView.vue'),
      meta: { title: 'Transfer', requiresAuth: true },
    },
  ],
  stores: [useTsRawRmEntryStore, useTsRawTransferStore],
}
