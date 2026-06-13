import api from '@/api/axios'

export default {
  getBackwardList(params = {}) { return api.get('/api/v1/trace/backward', { params }) },
  backwardTrace(traceNo, params = {}) { return api.get(`/api/v1/trace/backward/${traceNo}`, { params }) },
  getTraceDetail(params = {}) { return api.get('/api/v1/trace/backward/detail', { params }) },
  searchTraces(params = {}) { return api.get('/api/v1/trace/backward/search', { params }) },
}
