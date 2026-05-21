import { useAuthStore } from './stores'

export default {
  name: 'auth',
  routes: [
    {
      path: 'auth/login',
      name: 'auth-login',
      component: () => import('./views/LoginView.vue'),
      meta: { title: 'Login', requiresGuest: true },
    },
    {
      path: 'auth/plant-selection',
      name: 'plant-selection',
      component: () => import('./views/PlantSelectionView.vue'),
      meta: { title: 'Select Plant', requiresAuth: true },
    },
  ],
  stores: [useAuthStore],
}
