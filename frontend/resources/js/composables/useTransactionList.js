import { ref, computed } from 'vue'

/**
 * Reusable composable for standard paginated lists in EODS transaction modules.
 * Handles state management, pagination, caching, and generic fetching logic.
 *
 * @param {Function} fetchCallback - A function that returns a Promise resolving to the list response (e.g. `api.getList`).
 * @param {Object} options - Configuration options.
 * @returns {Object} State and methods for managing the list.
 */
export function useTransactionList(fetchCallback, options = {}) {
  const {
    defaultPerPage = 5,
    staleTime = 30 * 1000,
    listKey = 'list' // cache key identifier
  } = options

  const list = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({ 
    currentPage: 1, 
    perPage: defaultPerPage, 
    total: 0, 
    lastPage: 1 
  })

  // Simple in-memory cache tracking
  const _cache = {}

  function getCacheKey(params, key = listKey) {
    return `${key}_${JSON.stringify(params)}`
  }

  function isFresh(params = {}, key = listKey) {
    const cacheKey = getCacheKey(params, key)
    return Date.now() - (_cache[cacheKey] || 0) < staleTime
  }

  function touch(params = {}, key = listKey) {
    const cacheKey = getCacheKey(params, key)
    _cache[cacheKey] = Date.now()
  }

  function resetCache() {
    Object.keys(_cache).forEach(k => { _cache[k] = 0 })
    // Do not clear the reactive list here immediately, 
    // to prevent UI flicker before a fetch, 
    // just invalidate the cache timestamp.
  }

  function setPage(page) {
    pagination.value = { ...pagination.value, currentPage: page }
  }

  /**
   * Fetches the list data using the provided callback.
   * @param {Object} params - Query parameters to pass to the API (e.g., id_plant).
   * @param {Boolean} force - Whether to bypass cache and force a fetch.
   */
  async function fetchList(params = {}, force = false) {
    const queryParams = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      ...params
    }

    if (!force && isFresh(queryParams) && list.value.length > 0) {
      return list.value
    }

    loading.value = true
    error.value = null

    try {
      const response = await fetchCallback(queryParams)
      
      // Handle Laravel paginated response structure
      if (response && typeof response === 'object' && response.current_page !== undefined) {
        list.value = response.data || []
        pagination.value = {
          currentPage: response.current_page || 1,
          perPage: response.per_page || defaultPerPage,
          total: response.total || 0,
          lastPage: response.last_page || 1
        }
      } else if (response && typeof response === 'object' && response.data && response.data.current_page !== undefined) {
        // Some APIs nest the paginated response under a secondary `data` object 
        // depending on the interceptor structure.
        const pgData = response.data
        list.value = pgData.data || []
        pagination.value = {
          currentPage: pgData.current_page || 1,
          perPage: pgData.per_page || defaultPerPage,
          total: pgData.total || 0,
          lastPage: pgData.last_page || 1
        }
      } else {
        // Handle unpaginated arrays fallback
        const rawData = response?.data !== undefined ? response.data : response
        if (Array.isArray(rawData)) {
          list.value = rawData
        } else if (rawData && typeof rawData === 'object' && Array.isArray(rawData.data)) {
          list.value = rawData.data
        } else {
          list.value = Array.isArray(response) ? response : (response?.data || [])
        }
      }

      touch(queryParams)
      return response
    } catch (err) {
      error.value = err?.response?.data?.message || err.message || 'Failed to fetch data'
      throw err
    } finally {
      loading.value = false
    }
  }

  const hasEntries = computed(() => list.value.length > 0)
  const entriesCount = computed(() => list.value.length)

  return {
    list,
    loading,
    error,
    pagination,
    hasEntries,
    entriesCount,
    setPage,
    fetchList,
    resetCache,
    isFresh,
    touch
  }
}
