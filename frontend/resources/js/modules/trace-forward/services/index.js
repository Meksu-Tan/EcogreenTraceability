import api from '@/api/axios'

export default {
  getForwardList(params = {}) {
    return api.get('/api/v1/trace/forward', { params })
  },

  forwardTrace(traceNo, params = {}) {
    return api.get(`/api/v1/trace/forward/${traceNo}`, { params })
  },

  searchTraces(params = {}) {
    return api.get('/api/v1/trace/forward/search', { params })
  },

  getTraceDetail(params = {}) {
    return api.get('/api/v1/trace/forward/detail', { params })
  },
}
