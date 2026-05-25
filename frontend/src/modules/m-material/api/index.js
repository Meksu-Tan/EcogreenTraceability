import api from '@/api/axios'

export const getMaterials        = ()        => api.get('/api/v1/materials')
export const storeMaterial       = (data)    => api.post('/api/v1/materials', data)
export const updateMaterial      = (id, data)=> api.put(`/api/v1/materials/${id}`, data)
export const deactivateMaterial  = (id)      => api.delete(`/api/v1/materials/${id}?action=deactivate`)
export const activateMaterial    = (id)      => api.delete(`/api/v1/materials/${id}?action=activate`)

export const getPackagings           = ()        => api.get('/api/v1/material-packagings')
export const getSourceProducts       = ()        => api.get('/api/v1/material-packagings/source-products')
export const storePackaging          = (data)    => api.post('/api/v1/material-packagings', data)
export const updatePackaging         = (id, data)=> api.put(`/api/v1/material-packagings/${id}`, data)
export const deactivatePackaging     = (id)      => api.delete(`/api/v1/material-packagings/${id}?action=deactivate`)
export const activatePackaging       = (id)      => api.delete(`/api/v1/material-packagings/${id}?action=activate`)
