import axios from 'axios'

const apiBaseURL = import.meta.env.VITE_API_URL

if (!apiBaseURL) {
  throw new Error('VITE_API_URL environment variable is not set')
}

const api = axios.create({
  baseURL: apiBaseURL,
  withCredentials: true,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')

  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
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

    if (error.response?.status === 401 && window.location.pathname !== '/login') {
      localStorage.removeItem('auth_token')
      import('@/router')
        .then(({ default: router }) => {
          if (router.currentRoute.value.name !== 'login') {
            router.replace({ name: 'login' })
          }
        })
        .catch(() => {
          window.location.assign('/login')
        })
    }
    return Promise.reject(error)
  }
)

export default api
