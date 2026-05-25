import { useTsRawTransferStore } from './stores'

export default {
  name: 'ts-transfer',
  routes: [
    {
      path: 'ts-transfer/transfer',
      name: 'ts-transfer-transfer',
      component: () => import('./views/TransferView.vue'),
      meta: { title: 'Transfer', requiresAuth: true },
    },
  ],
  stores: [useTsRawTransferStore],
}
