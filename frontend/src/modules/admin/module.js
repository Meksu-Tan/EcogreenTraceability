import { useAdminUsersStore } from './stores'

export default {
  name: 'admin',
  routes: [
    {
      path: 'admin/user-management',
      name: 'admin-user-management',
      component: () => import('./views/UserManagementView.vue'),
      meta: { title: 'User Management', requiresAuth: true },
    },
  ],
  stores: [useAdminUsersStore],
}
