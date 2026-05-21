import { useTransactionRmEntryStore, useTransactionTransferStore } from './stores'

export default {
  name: 'transaction',
  routes: [
    {
      path: 'transaction/rm-entry',
      name: 'transaction-rm-entry',
      component: () => import('./views/RmEntryView.vue'),
      meta: { title: 'RM Entry', requiresAuth: true },
    },
    {
      path: 'transaction/wip-entry',
      name: 'transaction-wip-entry',
      component: () => import('./views/WipEntryView.vue'),
      meta: { title: 'WIP Entry', requiresAuth: true },
    },
    {
      path: 'transaction/blending',
      name: 'transaction-blending',
      component: () => import('./views/BlendingView.vue'),
      meta: { title: 'Blending', requiresAuth: true },
    },
    {
      path: 'transaction/package-entry',
      name: 'transaction-package-entry',
      component: () => import('./views/PackageEntryView.vue'),
      meta: { title: 'Package Entry', requiresAuth: true },
    },
    {
      path: 'transaction/shipment-entry',
      name: 'transaction-shipment-entry',
      component: () => import('./views/ShipmentEntryView.vue'),
      meta: { title: 'Shipment Entry', requiresAuth: true },
    },
    {
      path: 'transaction/transfer',
      name: 'transaction-transfer',
      component: () => import('./views/TransferView.vue'),
      meta: { title: 'Transfer', requiresAuth: true },
    },
  ],
  stores: [useTransactionRmEntryStore, useTransactionTransferStore],
}
