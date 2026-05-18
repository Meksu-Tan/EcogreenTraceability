import { defineStore } from 'pinia'
import api from '@/api/axios'

export const useTransactionTransferStore = defineStore('transactionTransfer', {
  state: () => ({
    storageLogs: [],
    feedLogs: [],
    sourceEntries: [],
    destTanks: [],
    tankDetails: [],
    loading: false,
    error: null
  }),

  actions: {
    async fetchStorageLogs(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/storage-log', { params })
        this.storageLogs = Array.isArray(response.data?.data) ? response.data.data : []
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchFeedLogs(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/feed-log', { params })
        const rows = response.data?.data
        this.feedLogs = Array.isArray(rows) ? rows : []
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchSourceEntries(params = {}) {
      try {
        const response = await api.get('/api/v1/transactions/transfers/source-entries', { params })
        this.sourceEntries = response.data.data
      } catch (error) {
        console.error('Fetch source entries error:', error)
      }
    },

    async fetchDestTanks(params = {}) {
      try {
        const response = await api.get('/api/v1/transactions/transfers/dest-tanks', { params })
        this.destTanks = response.data.data
      } catch (error) {
        console.error('Fetch dest tanks error:', error)
      }
    },

    async fetchTankDetails(tankId) {
      try {
        const response = await api.get(`/api/v1/transactions/rm-entries/tanks/${tankId}/details`)
        this.tankDetails = response.data.data
      } catch (error) {
        console.error('Fetch tank details error:', error)
      }
    },

    async performTransfer(data) {
      this.loading = true
      try {
        const response = await api.post('/api/v1/transactions/transfers', data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Delete Feed Tank entry.
     * id must be composite format: "idHead|idTraceHead"
     * Backend algorithm: Transfer::transfer_destroy (monorepo)
     */
    async deleteTransfer(id) {
      this.loading = true
      try {
        const response = await api.delete(`/api/v1/transactions/transfers/${encodeURIComponent(id)}`)
        if (!response.data?.success) {
          throw new Error(response.data?.message || 'Failed to deactivate transfer')
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || error.message
        throw error
      } finally {
        this.loading = false
      }
    }
  }
})
