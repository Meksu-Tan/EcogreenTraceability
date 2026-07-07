import { useSetupTankStore, useSetupWarehouseStore } from './stores'

export default {
  name: 'm-tank',
  routes: [
    {
      path: 'setup/tank',
      name: 'setup.tank',
      component: () => import('./views/TankSetupView.vue'),
      meta: { title: 'Setup Tank', requiresAuth: true },
    },
    {
      path: 'setup/warehouse',
      name: 'setup.warehouse',
      component: () => import('./views/WarehouseSetupView.vue'),
      meta: { title: 'Setup Warehouse', requiresAuth: true },
    },
  ],
  stores: [useSetupTankStore, useSetupWarehouseStore],
}

