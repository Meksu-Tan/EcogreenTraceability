export default {
  name: 'ts-blending',
  routes: [
    {
      path: 'ts-blending/blending',
      name: 'ts-blending-blending',
      component: () => import('./views/BlendingView.vue'),
      meta: { title: 'Blending', requiresAuth: true },
    },
  ],
  stores: [],
}
