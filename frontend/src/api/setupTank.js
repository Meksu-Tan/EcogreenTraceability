import api from './axios'

export const getTanks        = ()         => api.get('/api/v1/tanks')
export const storeTank       = (data)     => api.post('/api/v1/tanks', data)
export const updateTank      = (id, data) => api.put(`/api/v1/tanks/${id}`, data)
export const deactivateTank  = (id)       => api.delete(`/api/v1/tanks/${id}?action=deactivate`)
export const activateTank    = (id)       => api.delete(`/api/v1/tanks/${id}?action=activate`)
