import api from '@/api/axios'
const BASE_URL = '/api/v1/master/quantifier'
export default {
  getQuantifierList(params = {}) { return api.get(BASE_URL, { params }) },
  getFlowmeters() { return api.get(`${BASE_URL}/flowmeters`) },
  getQuantifierDetail(id) { return api.get(`${BASE_URL}/${id}`) },
  storeQuantifier(payload) { return api.post(BASE_URL, payload) },
  activateQuantifier(id) { return api.post(`${BASE_URL}/${id}/activate`) },
  deactivateQuantifier(id) { return api.post(`${BASE_URL}/${id}/deactivate`) },
}
