import { useSetupTankStore } from './stores'

export default {
  name: 'm-tank',
  routes: [
    {
      path: 'setup/tank',
      name: 'setup.tank',
      component: () => import('./views/TankSetupView.vue'),
      meta: { title: 'Setup Tank', requiresAuth: true },
    },
  ],
  stores: [useSetupTankStore],
}
