import { ref, computed } from 'vue'

const stats = ref(null)
const loading = ref(false)
const error = ref(null)

export const useDashboardStore = () => {
  const fetchStats = async () => {
    // Disabled - stats UI removed from dashboard
    return
  }

  const materialCount      = computed(() => 0)
  const storageCount      = computed(() => 0)
  const supplierCount     = computed(() => 0)
  const tankCount         = computed(() => 0)
  const manufacturerCount = computed(() => 0)
  const userCount         = computed(() => 0)

  return {
    stats,
    loading,
    error,
    materialCount,
    storageCount,
    supplierCount,
    tankCount,
    manufacturerCount,
    userCount,
    fetchStats
  }
}
