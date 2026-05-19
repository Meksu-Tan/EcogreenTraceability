import api from './axios'


export const login = (credentials) =>
  api.post('/api/login', credentials)

export const logout = () =>
  api.post('/api/logout')

export const getAuthUser = () =>
  api.get('/api/user')
