import api from './axios'

export const getManufacturers       = ()         => api.get('/api/v1/manufacturers')
export const getActiveManufacturers = ()         => api.get('/api/v1/manufacturers/active')
export const storeManufacturer      = (data)     => api.post('/api/v1/manufacturers', data)
export const updateManufacturer     = (id, data) => api.put(`/api/v1/manufacturers/${id}`, data)
export const deactivateManufacturer = (id)       => api.delete(`/api/v1/manufacturers/${id}?action=deactivate`)
export const activateManufacturer   = (id)       => api.delete(`/api/v1/manufacturers/${id}?action=activate`)
