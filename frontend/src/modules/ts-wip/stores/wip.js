import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import wipApi from '../api/wip'
import { useToastStore } from '@/stores/toast'
import { usePlantSelectionStore } from '@/stores/plant'

// GAP #9: Renamed from useTsRawWipEntryStore to useTsWipEntryStore
export const useTsWipEntryStore = defineStore('transactionWipEntry', () => {
  const toastStore = useToastStore()
  const plantStore = usePlantSelectionStore()

  // State
  const loading = ref(false)
  const plants = ref([])
  const selectedPlant = ref(null)

  // Feed state per section
  const feedLatest = ref({})
  const feedLogs = ref({})
  const rundownLatest = ref({})
  const rundownLogs = ref({})
  const balanceData = ref([])

  // Dropdown data
  const activeTanksFeed = ref([])
  const activeTanksRundown = ref([])
  const activeSpecificTanks = ref([])

  // Form state
  const feedForm = ref({
    feedId: '',
    batchNo: '',
    lastQtf: 0,
    currQtf: 0,
    entryDate: '',
    tank: null,
    tankNo: [],
    feature: '',
  })
  const rundownForm = ref({
    rundownId: '',
    batchNo: '',
    lastQtf: 0,
    currQtf: 0,
    entryDate: '',
    tank: null,
    tankNo: [],
    feature: '',
  })

  // Getters
  const plantId = computed(() => plantStore.selectedPlantId)

  // Actions
  async function fetchIndex() {
    loading.value = true
    try {
      const res = await wipApi.getIndex({ id_plant: plantId.value })
      if (res.status === 1 && res.data) {
        plants.value = res.data.plants || []
        selectedPlant.value = res.data.selectedPlant
      }
      return res
    } catch (error) {
      toastStore.error('Failed to load WIP page:', error)
      return null
    } finally {
      loading.value = false
    }
  }

  async function fetchFeed(feedId, mode = 'LOG') {
    try {
      const res = await wipApi.getFeed(feedId, mode, { id_plant: plantId.value })
      const data = res.data || []
      feedLatest.value[feedId] = data
      feedLogs.value[feedId] = data
      return res
    } catch (error) {
      console.error('Fetch feed error:', error)
      return { data: [] }
    }
  }

  async function fetchRundown(rundownId, mode = 'LOG') {
    try {
      const res = await wipApi.getRundown(rundownId, mode, { id_plant: plantId.value })
      const data = res.data || []
      rundownLatest.value[rundownId] = data
      rundownLogs.value[rundownId] = data
      return res
    } catch (error) {
      console.error('Fetch rundown error:', error)
      return { data: [] }
    }
  }

  async function fetchBalance(rundownId, params = {}) {
    try {
      const res = await wipApi.getBalance(rundownId, { id_plant: plantId.value, ...params })
      balanceData.value = res.data || []
      return res
    } catch (error) {
      toastStore.error('Fetch balance error:', error)
      return { data: [] }
    }
  }

  async function fetchFeedNewBatchNumber(feedID) {
    try {
      const data = await wipApi.getFeedNewBatchNumber(feedID, { id_plant: plantId.value })
      return data
    } catch (error) {
      toastStore.error('Fetch feed batch number error:', error)
      return null
    }
  }

  async function fetchRundownNewBatchNumber(rundownID) {
    try {
      const data = await wipApi.getRundownNewBatchNumber(rundownID, { id_plant: plantId.value })
      return data
    } catch (error) {
      toastStore.error('Fetch rundown batch number error:', error)
      return null
    }
  }

  async function generateNewFeedNumber(feedId) {
    try {
      const data = await wipApi.getNewFeedNumber(feedId, { id_plant: plantId.value })
      return data
    } catch (error) {
      toastStore.error('Generate new feed number error')
      return null
    }
  }

  async function generateNewRundownNumber(rundownId, subgroup = null) {
    try {
      const data = await wipApi.getNewRundownNumber(rundownId, { id_plant: plantId.value, subgroup })
      return data
    } catch (error) {
      toastStore.error('Generate new rundown number error')
      return null
    }
  }


  async function fetchFeedLastBatch(feedID) {
    try {
      const data = await wipApi.getFeedLastBatch(feedID, { id_plant: plantId.value })
      return data
    } catch (error) {
      toastStore.error('Fetch feed last batch error:', error)
      return []
    }
  }

  async function fetchRundownLastBatch(rundownID) {
    try {
      const data = await wipApi.getRundownLastBatch(rundownID, { id_plant: plantId.value })
      return data
    } catch (error) {
      toastStore.error('Fetch rundown last batch error:', error)
      return []
    }
  }

  async function fetchActiveTanksFeed(feedID) {
    try {
      const data = await wipApi.getActiveTanksFeed(feedID, { id_plant: plantId.value })
      activeTanksFeed.value = Array.isArray(data) ? data : []
      return activeTanksFeed.value
    } catch (error) {
      toastStore.error('Fetch active tanks feed error:', error)
      return []
    }
  }

  async function fetchActiveTanksRundown(rundownID, params = {}) {
    try {
      const data = await wipApi.getActiveTanksRundown(rundownID, { id_plant: plantId.value, ...params })
      activeTanksRundown.value = Array.isArray(data) ? data : []
      return activeTanksRundown.value
    } catch (error) {
      toastStore.error('Fetch active tanks rundown error:', error)
      return []
    }
  }

  async function fetchActiveSpecificTanks(sloc) {
    try {
      const data = await wipApi.getActiveSpecificTanks(sloc)
      activeSpecificTanks.value = Array.isArray(data) ? data : []
      return activeSpecificTanks.value
    } catch (error) {
      toastStore.error('Fetch active specific tanks error:', error)
      return []
    }
  }

  // CRITICAL #10: DCS Quantifier Integration - Auto-fill from DCS flowmeter
  async function fetchQuantifierData(date, tagNumber) {
    try {
      const data = await wipApi.getQuantifierData(date, tagNumber)
      return data
    } catch (error) {
      toastStore.error('Fetch quantifier data error:', error)
      throw error
    }
  }

  // Auto-fill quantifier for feed entry
  async function autoFillQuantifier(feedId, entryDate) {
    try {
      // GAP C: Fixed DCS tag names - actual Airflow historian table names
      // These are the physical flow transmitter tag numbers from the original system
      const quantifierTags = {
        '101': '101_FT0113',     // CPKO Feed
        '102': '102_FT0129',     // DA-OIL Rundown
        '103': '103_FT0101',     // DA-OIL Feed
        '104': '104_FT0101',     // Crude-ME Feed
        '105': '105_FT0101',     // PKFAD Feed
        '111': '111_FT0113',     // Glycerine Feed
        '112': '112_FT0113',     // FA18/FA24 Feed
        '114': '114_FT0113',     // FA14/Ecorol Wax Feed
      }

      const tagNumber = quantifierTags[feedId]
      if (!tagNumber) {
        toastStore.info('No quantifier tag available for this feed')
        return null
      }

      const data = await fetchQuantifierData(entryDate, tagNumber)
      if (data && data.length > 0) {
        return {
          currQtf: parseFloat(data[0].value) || 0,
          timestamp: data[0].timestamp || entryDate
        }
      }
      return null
    } catch (error) {
      toastStore.error('Auto-fill quantifier error:', error)
      return null
    }
  }

  async function saveFeed(data) {
    loading.value = true
    try {
      const res = await wipApi.store({
        flag: 'post_materialFeed',
        ...data,
        id_plant: plantId.value,
        feature: data.feature || 'FEED',
      })
      if (res.status === 1) {
        toastStore.success(res.message || 'Feed saved successfully')
        await fetchFeed(data.feed_id, 'LOG')
      } else {
        toastStore.error('Failed to save feed:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to save feed:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function saveRundown(data) {
    loading.value = true
    try {
      const res = await wipApi.store({
        flag: 'post_materialRundown',
        ...data,
        id_plant: plantId.value,
        feature: data.feature || 'RUNDOWN',
      })
      if (res.status === 1) {
        toastStore.success(res.message || 'Rundown saved successfully')
        await fetchRundown(data.rundown_id, 'LOG')
      } else {
        toastStore.error('Failed to save rundown:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to save rundown:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function cancelFeed(traceNo, feedId) {
    loading.value = true
    try {
      const res = await wipApi.store({
        flag: 'post_cancelFeed',
        traceNo,
        id_plant: plantId.value,
      })
      if (res.status === 1) {
        toastStore.success(res.message || 'Feed cancelled')
        await fetchFeed(feedId, 'LOG')
      } else {
        toastStore.error('Failed to cancel feed:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to cancel feed:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function cancelRundown(traceNo, rundownId) {
    loading.value = true
    try {
      const res = await wipApi.store({
        flag: 'post_cancelRundown',
        traceNo,
        id_plant: plantId.value,
      })
      if (res.status === 1) {
        toastStore.success(res.message || 'Rundown cancelled')
        await fetchRundown(rundownId, 'LOG')
      } else {
        toastStore.error('Failed to cancel rundown:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to cancel rundown:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function saveMaterialDocument(mode, id, number) {
    try {
      const res = await wipApi.store({
        flag: 'post_matlDocNumber',
        mode,
        id,
        number,
      })
      if (res.status === 1) {
        toastStore.success(res.message || 'Material document saved')
      } else {
        toastStore.error('Failed to save material document:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to save material document:', error)
      throw error
    }
  }

  async function updateEntrySubTank(idHead, idTankTail) {
    try {
      const res = await wipApi.store({
        flag: 'post_updateEntrySubTank',
        idHead,
        idTankTail,
      })
      if (res.status === 1) {
        toastStore.success('Sub-tank updated')
      } else {
        toastStore.error('Failed to update sub-tank:', res.message)
      }
      return res
    } catch (error) {
      toastStore.error('Failed to update sub-tank:', error)
      throw error
    }
  }

  function resetFeedForm() {
    feedForm.value = {
      feedId: '',
      batchNo: '',
      lastQtf: 0,
      currQtf: 0,
      entryDate: '',
      tank: null,
      tankNo: [],
      feature: '',
    }
  }

  function resetRundownForm() {
    rundownForm.value = {
      rundownId: '',
      batchNo: '',
      lastQtf: 0,
      currQtf: 0,
      entryDate: '',
      tank: null,
      tankNo: [],
      feature: '',
    }
  }

  return {
    // State
    loading,
    plants,
    selectedPlant,
    feedLatest,
    feedLogs,
    rundownLatest,
    rundownLogs,
    balanceData,
    activeTanksFeed,
    activeTanksRundown,
    activeSpecificTanks,
    feedForm,
    rundownForm,

    // Getters
    plantId,

    // Actions
    fetchIndex,
    fetchFeed,
    fetchRundown,
    fetchBalance,
    fetchFeedNewBatchNumber,
    fetchRundownNewBatchNumber,
    generateNewFeedNumber,
    generateNewRundownNumber,
    fetchFeedLastBatch,
    fetchRundownLastBatch,
    fetchActiveTanksFeed,
    fetchActiveTanksRundown,
    fetchActiveSpecificTanks,
    fetchQuantifierData,
    autoFillQuantifier,
    saveFeed,
    saveRundown,
    cancelFeed,
    cancelRundown,
    saveMaterialDocument,
    updateEntrySubTank,
    resetFeedForm,
    resetRundownForm,
  }
})
