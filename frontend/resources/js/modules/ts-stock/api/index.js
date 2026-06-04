import api from '@/api/axios'
const BASE_URL = '/api/v1/transactions/stock'
export default {
  getStock(params = {}) { return api.get(BASE_URL, { params }) },
  getStockById(id) { return api.get(`${BASE_URL}/${id}`) },
  getActiveMaterials(params = {}) { return api.get(`${BASE_URL}/active-materials`, { params }) },
  getActiveSlocs() { return api.get(`${BASE_URL}/active-slocs`) },
  getMovements(params = {}) { return api.get(`${BASE_URL}/movements`, { params }) },
}
