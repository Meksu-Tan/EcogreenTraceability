import { useWipProcessStore } from './stores/wipProcessStore.js'

export default {
  name: 'm-wip-process',
  routes: [
    {
      path: 'setup/wip-process',
      name: 'setup.wip-process',
      component: () => import('./views/WipProcessSetupView.vue'),
      meta: { title: 'Setup WIP Process', requiresAuth: true },
    },
  ],
  stores: [useWipProcessStore],
}
