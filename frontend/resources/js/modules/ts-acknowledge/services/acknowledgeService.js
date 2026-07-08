import api from '@/api/axios'

const BASE = '/api/v1/ts-acknowledge'

export const getDashboard = (params) => api.get(`${BASE}/dashboard`, { params })
export const fetchDcs     = (data)   => api.post(`${BASE}/fetch-dcs`, data)
export const syncDcs      = (data)   => api.post(`${BASE}/sync-dcs`, data)
export const saveAcknowledge = (data) => api.post(`${BASE}/save`, data)
