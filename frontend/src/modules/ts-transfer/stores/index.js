import { defineStore } from 'pinia'
import transferApi from '../api'

export const useTsTransferStore = defineStore('transactionTransfer', {
  state: () => ({
    transferList: [],
    specificTanks: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchTransferList(plantId = 0) {
      this.loading = true
      this.error = null
      try {
        const response = await transferApi.getTransferList(plantId)
        const rows = response?.data
        this.transferList = Array.isArray(rows) ? rows : []
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        this.transferList = []
      } finally {
        this.loading = false
      }
    },

    async fetchSpecificTankRundown(sloc) {
      try {
        const response = await transferApi.getSpecificTankRundown(sloc)
        this.specificTanks = response?.data || []
      } catch (error) {
        console.error('Fetch specific tank rundown error:', error)
        this.specificTanks = []
      }
    },

    async submitMatlDocNumber(mode, id, number) {
      const response = await transferApi.postMatlDocNumber(mode, id, number)
      return response
    },

    async submitUpdateEntrySubTank(idHead, idTankTail) {
      const response = await transferApi.postUpdateEntrySubTank(idHead, idTankTail)
      return response
    },

    async deleteTransfer(id) {
      this.loading = true
      try {
        const response = await transferApi.deactivateTransfer(id)
        return response
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})

// Backward-compatible alias for modules still using old name
export const useTsRawTransferStore = useTsTransferStore
