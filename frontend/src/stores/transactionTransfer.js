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
    async fetchStorageLogs() {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/storage-log')
        this.storageLogs = response.data.data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchFeedLogs() {
      this.loading = true
      try {
        const response = await api.get('/api/v1/transactions/transfers/feed-log')
        this.feedLogs = response.data.data
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async fetchSourceEntries() {
      try {
        const response = await api.get('/v1/transactions/transfers/source-entries')
        this.sourceEntries = response.data.data
      } catch (error) {
        console.error('Fetch source entries error:', error)
      }
    },

    async fetchDestTanks() {
      try {
        const response = await api.get('/v1/transactions/transfers/dest-tanks')
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

    async deleteTransfer(id) {
      this.loading = true
      try {
        const response = await api.delete(`/api/v1/transactions/transfers/${id}`)
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
