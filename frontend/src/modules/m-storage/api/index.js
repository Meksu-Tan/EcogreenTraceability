import api from '@/api/axios'

export const getTanks          = ()         => api.get('/api/v1/storage-tanks')
export const storeTank         = (data)     => api.post('/api/v1/storage-tanks', data)
export const updateTank        = (id, data) => api.put(`/api/v1/storage-tanks/${id}`, data)
export const deactivateTank    = (id)       => api.delete(`/api/v1/storage-tanks/${id}?action=deactivate`)
export const activateTank      = (id)       => api.delete(`/api/v1/storage-tanks/${id}?action=activate`)

export const getDetails        = (tankId)   => api.get(`/api/v1/storage-details?id_tank=${tankId}`)
export const storeDetail       = (data)     => api.post('/api/v1/storage-details', data)
export const updateDetail      = (id, data) => api.put(`/api/v1/storage-details/${id}`, data)
export const deactivateDetail  = (id)       => api.delete(`/api/v1/storage-details/${id}?action=deactivate`)
export const activateDetail    = (id)       => api.delete(`/api/v1/storage-details/${id}?action=activate`)

export const getWarehouses     = ()         => api.get('/api/v1/warehouses')
export const storeWarehouse    = (data)     => api.post('/api/v1/warehouses', data)
export const updateWarehouse   = (id, data) => api.put(`/api/v1/warehouses/${id}`, data)
export const deactivateWarehouse = (id)     => api.delete(`/api/v1/warehouses/${id}?action=deactivate`)
export const activateWarehouse   = (id)     => api.delete(`/api/v1/warehouses/${id}?action=activate`)
