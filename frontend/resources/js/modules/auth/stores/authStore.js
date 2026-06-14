import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as apiLogin, logout as apiLogout, getAuthUser } from '@/modules/auth/services/index.js'

export const useAuthStore = defineStore('auth', () => {
  const user        = ref(null)
  const roles       = ref([])
  const permissions = ref([])
  const loading     = ref(false)

  const isAuthenticated = computed(() => !!user.value)

  function hasRole(role) {
    return roles.value.includes(role)
  }

  function hasPermission(permission) {
    return permissions.value.includes(permission)
  }

  function hasAnyRole(roleList) {
    return roleList.some(r => roles.value.includes(r))
  }

  function setAuthData(data) {
    user.value        = data
    roles.value       = data.roles       || []
    permissions.value = data.permissions || []
  }

  async function login(credentials) {
    loading.value = true
    try {
      const response = await apiLogin(credentials)
      if (response.data.status === 1) {
        localStorage.setItem('auth_token', response.data.token)
        setAuthData(response.data.data)
        return { success: true }
      }
      return { success: false, message: response.data.message }
    } catch (error) {
      const message = error.response?.data?.message || 'Login failed. Please try again.'
      return { success: false, message }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await apiLogout()
    } finally {
      localStorage.removeItem('auth_token')
      user.value        = null
      roles.value       = []
      permissions.value = []
    }
  }

  async function fetchUser() {
    try {
      if (!localStorage.getItem('auth_token')) return false

      const response = await getAuthUser()
      if (response.data.status === 1) {
        setAuthData(response.data.data)
        return true
      }
    } catch {
      localStorage.removeItem('auth_token')
      user.value        = null
      roles.value       = []
      permissions.value = []
    }
    return false
  }

  return {
    user, roles, permissions, loading,
    isAuthenticated,
    hasRole, hasPermission, hasAnyRole,
    login, logout, fetchUser,
  }
})
