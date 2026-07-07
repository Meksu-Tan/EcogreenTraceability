import api from '@/api/axios'

export const getTanks        = ()         => api.get('/api/v1/tanks')
export const storeTank       = (data)     => api.post('/api/v1/tanks', data)
export const updateTank      = (id, data) => api.put(`/api/v1/tanks/${id}`, data)
export const deactivateTank  = (id)       => api.delete(`/api/v1/tanks/${id}?action=deactivate`)
export const activateTank    = (id)       => api.delete(`/api/v1/tanks/${id}?action=activate`)
export const syncTanks       = (refresh = false) => api.post(`/api/v1/tanks/sync${refresh ? '?refresh=true' : ''}`)
export const getLastSync     = ()         => api.get('/api/v1/tanks/last-sync')

export const getWarehouses       = ()         => api.get('/api/v1/warehouses')
export const storeWarehouse      = (data)     => api.post('/api/v1/warehouses', data)
export const updateWarehouse     = (id, data) => api.put(`/api/v1/warehouses/${id}`, data)
export const deactivateWarehouse = (id)       => api.delete(`/api/v1/warehouses/${id}?action=deactivate`)
export const activateWarehouse   = (id)       => api.delete(`/api/v1/warehouses/${id}?action=activate`)

