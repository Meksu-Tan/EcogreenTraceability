import api from '@/api/axios'

export const getSuppliers       = ()         => api.get('/api/v1/suppliers')
export const getActiveSuppliers = ()         => api.get('/api/v1/suppliers/active')
export const storeSupplier      = (data)     => api.post('/api/v1/suppliers', data)
export const updateSupplier     = (id, data) => api.put(`/api/v1/suppliers/${id}`, data)
export const deactivateSupplier = (id)       => api.delete(`/api/v1/suppliers/${id}?action=deactivate`)
export const activateSupplier   = (id)       => api.delete(`/api/v1/suppliers/${id}?action=activate`)
