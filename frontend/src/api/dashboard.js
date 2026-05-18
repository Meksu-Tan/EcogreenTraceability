import api from './axios'

export const getDashboardSummary = () => api.get('/api/v1/dashboard/summary')
