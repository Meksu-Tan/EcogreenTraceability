import api from '@/api/axios'
const BASE_URL = '/api/v1/transactions/ts-report'
export default {
  getTsReport(params = {}) { return api.get(BASE_URL, { params }) },
  getAllSections(params = {}) { return api.get(`${BASE_URL}/all`, { params }) },
  getRmSection(params = {}) { return api.get(`${BASE_URL}/rm`, { params }) },
  getWipSection(params = {}) { return api.get(`${BASE_URL}/wip`, { params }) },
  getPckSection(params = {}) { return api.get(`${BASE_URL}/pck`, { params }) },
  getShipmentSection(params = {}) { return api.get(`${BASE_URL}/shipment`, { params }) },
  getTransferSection(params = {}) { return api.get(`${BASE_URL}/transfer`, { params }) },
}
