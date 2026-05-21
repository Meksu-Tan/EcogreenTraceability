import api from '@/api/axios'

export default {
  getUsers() {
    return api.get('/api/v1/admin/users')
  },

  createUser(data) {
    return api.post('/api/v1/admin/users', data)
  },

  updateUser(id, data) {
    return api.put(`/api/v1/admin/users/${id}`, data)
  },

  deleteUser(id) {
    return api.delete(`/api/v1/admin/users/${id}`)
  },

  getRoles() {
    return api.get('/api/roles')
  }
}
