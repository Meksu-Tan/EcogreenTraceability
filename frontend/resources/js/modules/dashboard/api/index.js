import api from '@/api/axios'

export const getDashboardStats = () => api.get('/api/v1/dashboard/stats')
