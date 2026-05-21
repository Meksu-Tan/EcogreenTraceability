import { ref, computed } from 'vue'
import { getDashboardStats } from '../api'

const stats = ref(null)
const loading = ref(false)
const error = ref(null)

export const useDashboardStore = () => {
  const fetchStats = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await getDashboardStats()
      if (response.status === 1) {
        stats.value = response.data
      }
    } catch (err) {
      error.value = 'Failed to fetch dashboard stats'
      console.error('Dashboard stats error:', err)
    } finally {
      loading.value = false
    }
  }

  const materialCount = computed(() => stats.value?.material_count ?? 0)
  const storageCount = computed(() => stats.value?.storage_count ?? 0)
  const supplierCount = computed(() => stats.value?.supplier_count ?? 0)
  const userCount = computed(() => stats.value?.user_count ?? 0)

  return {
    stats,
    loading,
    error,
    materialCount,
    storageCount,
    supplierCount,
    userCount,
    fetchStats
  }
}
