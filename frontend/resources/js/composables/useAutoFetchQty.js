import { ref, computed, onBeforeUnmount } from 'vue'

/**
 * Reusable composable for auto-fetch timer functionality in modal forms.
 * Provides countdown display, scheduled sync, and manual fetch.
 *
 * @param {Function} fetchFn - Async function to call for fetching data.
 * @param {Object} options
 * @param {number[]} options.syncHours - Hours at which to sync (default [0, 8, 16]).
 * @returns {Object} Reactive state and control methods.
 */
export function useAutoFetchQty(fetchFn, options = {}) {
  const {
    syncHours = [0, 8, 16]
  } = options

  const countdownSeconds = ref(0)
  const lastSyncAt = ref(null)
  const lastSyncUser = ref(null)
  const syncing = ref(false)

  let countdownTimer = null
  let autoSyncTimer = null

  const countdownDisplay = computed(() => {
    if (countdownSeconds.value <= 0) return 'now'
    const h = Math.floor(countdownSeconds.value / 3600)
    const m = Math.floor((countdownSeconds.value % 3600) / 60)
    const s = countdownSeconds.value % 60
    if (h > 0) return `${h}h ${m}m`
    if (m > 0) return `${m}m ${s}s`
    return `${s}s`
  })

  const nextSyncLabel = computed(() => {
    const next = getNextSyncTime()
    return next.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  })

  function getNextSyncTime() {
    const now = new Date()
    const currentHour = now.getHours()
    for (const h of syncHours) {
      if (currentHour < h) {
        const next = new Date(now)
        next.setHours(h, 0, 0, 0)
        return next
      }
    }
    const tomorrow = new Date(now)
    tomorrow.setDate(tomorrow.getDate() + 1)
    tomorrow.setHours(syncHours[0], 0, 0, 0)
    return tomorrow
  }

  function startCountdown() {
    if (countdownTimer) clearInterval(countdownTimer)
    const nextSync = getNextSyncTime()
    countdownSeconds.value = Math.max(0, Math.floor((nextSync.getTime() - Date.now()) / 1000))

    countdownTimer = setInterval(() => {
      countdownSeconds.value = Math.max(0, countdownSeconds.value - 1)
      if (countdownSeconds.value <= 0) {
        manualFetch()
        setTimeout(startCountdown, 2000)
      }
    }, 1000)
  }

  function stopCountdown() {
    if (countdownTimer) {
      clearInterval(countdownTimer)
      countdownTimer = null
    }
  }

  function startAutoSync() {
    if (autoSyncTimer) clearTimeout(autoSyncTimer)
    function scheduleNext() {
      const next = getNextSyncTime()
      const delay = Math.max(1000, next.getTime() - Date.now())
      autoSyncTimer = setTimeout(() => {
        manualFetch()
        scheduleNext()
      }, delay)
    }
    scheduleNext()
  }

  function stopAutoSync() {
    if (autoSyncTimer) {
      clearTimeout(autoSyncTimer)
      autoSyncTimer = null
    }
  }

  async function manualFetch() {
    syncing.value = true
    try {
      const result = await fetchFn()
      lastSyncAt.value = new Date().toISOString()
      if (result && result.lastSyncUser) {
        lastSyncUser.value = result.lastSyncUser
      }
      startCountdown()
    } catch {
      // Error handled by caller
    } finally {
      syncing.value = false
    }
  }

  onBeforeUnmount(() => {
    stopCountdown()
    stopAutoSync()
  })

  return {
    countdownSeconds,
    lastSyncAt,
    lastSyncUser,
    syncing,
    countdownDisplay,
    nextSyncLabel,
    startCountdown,
    stopCountdown,
    manualFetch,
    startAutoSync,
    stopAutoSync
  }
}
