import api from './axios'

export default {
  /**
   * Fetch all users
   */
  getUsers() {
    return api.get('/api/v1/admin/users')
  },

  /**
   * Create a new user
   */
  createUser(data) {
    return api.post('/api/v1/admin/users', data)
  },

  /**
   * Update an existing user
   */
  updateUser(id, data) {
    return api.put(`/api/v1/admin/users/${id}`, data)
  },

  /**
   * Delete a user
   */
  deleteUser(id) {
    return api.delete(`/api/v1/admin/users/${id}`)
  },

  /**
   * Fetch all available roles
   */
  getRoles() {
    return api.get('/api/roles')
  }
}
