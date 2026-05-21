import api from './axios'


export const login = (credentials) =>
  api.post('/api/v1/login', credentials)

export const logout = () =>
  api.post('/api/v1/logout')

export const getAuthUser = () =>
  api.get('/api/v1/user')
