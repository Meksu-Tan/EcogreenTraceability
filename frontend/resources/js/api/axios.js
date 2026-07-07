import axios from 'axios'
import { getRouter } from '@/core/router/index.js'

const apiBaseURL = import.meta.env.VITE_API_URL

if (!apiBaseURL) {
  throw new Error('VITE_API_URL environment variable is not set')
}

const api = axios.create({
  baseURL: apiBaseURL,

  timeout: 60000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.request.use(async (config) => {
  const token = localStorage.getItem('auth_token')

  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }

  try {
    const { usePlantSelectionStore } = await import('@/stores/plant.js')
    const plantStore = usePlantSelectionStore()
    if (plantStore.selectedPlantId) {
      config.headers['X-Plant-Id'] = plantStore.selectedPlantId
    }
  } catch (e) {
    // Pinia not initialized yet or store unavailable
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle network errors (no response)
    if (!error.response) {
      const message = error.code === 'ECONNABORTED'
        ? 'Request timeout. Please check your connection.'
        : 'Network error. Please check your connection and try again.'
      // Reject with structured error for callers to handle
      return Promise.reject({ message, networkError: true })
    }

    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      const router = getRouter()
      if (router && router.currentRoute.value.name !== 'login') {
        router.replace({ name: 'login' })
      }
    }
    return Promise.reject(error)
  }
)

export default api
