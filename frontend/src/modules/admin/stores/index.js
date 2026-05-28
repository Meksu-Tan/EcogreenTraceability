import { defineStore } from 'pinia'
import adminUsersApi from '../api'
import { useToastStore } from '@/stores/toast'

export const useAdminUsersStore = defineStore('adminUsers', {
  state: () => ({
    users: [],
    roles: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchUsers() {
      this.loading = true
      this.error = null
      try {
        const response = await adminUsersApi.getUsers()
        if (response.data.status === 1) {
          this.users = response.data.data
        }
      } catch (err) {
        this.error = err.response?.data?.message || err.message
        const toastStore = useToastStore()
        toastStore.error('Error fetching users')
      } finally {
        this.loading = false
      }
    },
    async fetchRoles() {
      try {
        const response = await adminUsersApi.getRoles()
        if (response.data.status === 1) {
          this.roles = response.data.data
        }
      } catch (err) {
        const toastStore = useToastStore()
        toastStore.error('Error fetching roles')
      }
    },
    async createUser(data) {
      try {
        const response = await adminUsersApi.createUser(data)
        if (response.data.status === 1) {
          this.users.push(response.data.data)
          return { status: 1, message: response.data.message }
        }
        return { status: 0, message: 'Gagal menambahkan user' }
      } catch (err) {
        return {
          status: 0,
          message: err.response?.data?.message || 'Terjadi kesalahan saat menambahkan user',
          errors: err.response?.data?.errors
        }
      }
    },
    async updateUser(id, data) {
      try {
        const response = await adminUsersApi.updateUser(id, data)
        if (response.data.status === 1) {
          const index = this.users.findIndex(u => u.id === id)
          if (index !== -1) {
            this.users.splice(index, 1, response.data.data)
          }
          return { status: 1, message: response.data.message }
        }
        return { status: 0, message: 'Gagal memperbarui user' }
      } catch (err) {
        return {
          status: 0,
          message: err.response?.data?.message || 'Terjadi kesalahan saat memperbarui user',
          errors: err.response?.data?.errors
        }
      }
    },
    async deleteUser(id) {
      try {
        const response = await adminUsersApi.deleteUser(id)
        if (response.data.status === 1) {
          this.users = this.users.filter(u => u.id !== id)
          return { status: 1, message: response.data.message }
        }
        return { status: 0, message: 'Gagal menghapus user' }
      } catch (err) {
        return {
          status: 0,
          message: err.response?.data?.message || 'Terjadi kesalahan saat menghapus user'
        }
      }
    }
  }
})
