import api from '@/api/axios'

export const getPlants        = ()         => api.get('/api/v1/plants')
export const storePlant       = (data)     => api.post('/api/v1/plants', data)
export const updatePlant      = (id, data) => api.put(`/api/v1/plants/${id}`, data)
export const deactivatePlant  = (id)       => api.delete(`/api/v1/plants/${id}?action=deactivate`)
export const activatePlant    = (id)       => api.delete(`/api/v1/plants/${id}?action=activate`)
