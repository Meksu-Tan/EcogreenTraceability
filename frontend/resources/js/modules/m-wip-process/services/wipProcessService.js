import api from '@/api/axios'

const BASE = '/api/v1/wip-process'

export const getSections        = (params)   => api.get(`${BASE}/sections`, { params })
export const storeSection       = (data)     => api.post(`${BASE}/sections`, data)
export const updateSection      = (id, data) => api.put(`${BASE}/sections/${id}`, data)
export const deleteSection      = (id)       => api.delete(`${BASE}/sections/${id}`)
export const reorderSections    = (items)    => api.put(`${BASE}/sections/reorder`, { items })

export const storeStep    = (data)     => api.post(`${BASE}/steps`, data)
export const updateStep   = (id, data) => api.put(`${BASE}/steps/${id}`, data)
export const deleteStep   = (id)       => api.delete(`${BASE}/steps/${id}`)
export const reorderSteps = (items)    => api.put(`${BASE}/steps/reorder`, { items })
