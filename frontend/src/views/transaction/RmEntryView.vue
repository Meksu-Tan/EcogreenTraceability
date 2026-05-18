<template>
  <div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Raw Material Lists</h1>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Lokasi:</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200">
              <i class="fas fa-industry mr-1.5 opacity-70"></i>
              {{ plantSelectionStore.selectedPlantName }}
            </span>
          </div>
        </div>
        <div class="h-10 w-px bg-gray-200"></div>
        <PlantSelector @change="fetchData" />
      </div>

      <div class="flex items-center gap-3">
        <button
          type="button"
          @click="openCreateModal"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
        >
          <i class="fas fa-plus"></i>
          New RM Entry
        </button>
        <button
          type="button"
          @click="openTransferModal"
          class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
        >
          <i class="fas fa-arrow-right"></i>
          Transfer to Feed Tank
        </button>
        <div class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold flex items-center">
          QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
        </div>
      </div>
    </div>

    <!-- Storage Tank Log Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-bold text-slate-800">STORAGE TANK LOG</h2>
      </div>
      <div class="p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-slate-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider w-16">No</th>
              <th v-if="!plantSelectionStore.selectedPlantId" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Plant</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Action</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Trace No</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Entry Date</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Matl Doc</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">PurchO</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Material</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Sloc</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Init Material (MT)</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Init Supplier (MT)</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">On-Hand (MT)</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider w-1/4">Supplier / Batch SAP / Init Qty (MT) / Remark</th>
              <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Created at</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Created by</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading"><td :colspan="storageColumnCount" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
            <tr v-else-if="!hasEntries"><td :colspan="storageColumnCount" class="px-6 py-4 text-center text-gray-500">No RM entries found</td></tr>
            <tr v-else v-for="(entry, index) in paginatedStorageEntries" :key="entry.id_balance_head" class="hover:bg-gray-50 text-sm">
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ (currentPageStorage - 1) * itemsPerPage + index + 1 }}</td>
              <td v-if="!plantSelectionStore.selectedPlantId" class="px-4 py-3 whitespace-nowrap text-gray-600 font-semibold">{{ entry.plant_code || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex gap-2">
                  <button @click="deactivateEntry(entry.id_balance_head)" :disabled="!entry.traced || entry.traced.trim() === ''" class="text-red-600 hover:text-red-900 disabled:text-gray-400" :title="(!entry.traced || entry.traced.trim() === '') ? 'Entry has been used/transferred' : 'Delete Entry'">
                    <i class="fas fa-trash"></i>
                  </button>
                  <button @click="openUpdateModal(entry)" class="text-green-600 hover:text-green-900">
                    <i class="fas fa-edit"></i>
                  </button>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 text-center">{{ entry.trace_no }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ formatDate(entry.entry_date) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ entry.material_document || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ entry.po_so || '-' }}</td>
              <td class="px-4 py-3 text-gray-900">{{ entry.material }}</td>
              <td class="px-4 py-3 text-center">
                <a href="#" @click.prevent="openSlocEdit(entry)" class="text-slate-500 hover:underline">{{ entry.tf_number }}</a>
              </td>
              <td class="px-4 py-3 text-right font-medium" :class="entry.init_qty === entry.balance_supplier ? 'text-green-600' : 'text-red-600'">{{ entry.init_qty }}</td>
              <td class="px-4 py-3 text-right font-medium" :class="entry.init_qty === entry.balance_supplier ? 'text-green-600' : 'text-red-600'">{{ entry.balance_supplier }}</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900">{{ entry.qty }}</td>
              <td class="px-4 py-3 text-gray-900">
                <div class="max-h-20 overflow-y-auto whitespace-pre-wrap text-xs">
                  {{ formatSuppliers(entry.supplier) }}
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <i v-if="entry.status == 1" class="fas fa-check text-green-500" title="Active"></i>
                <i v-else class="fas fa-times text-red-500" title="Inactive"></i>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ entry.created_at }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ entry.created_by }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination Storage -->
      <div v-if="totalPagesStorage > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
        <div class="text-sm text-gray-600">
          Showing {{ (currentPageStorage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPageStorage * itemsPerPage, filteredEntries.length) }} of {{ filteredEntries.length }} entries
        </div>
        <div class="flex gap-2">
          <button 
            @click="currentPageStorage = 1" 
            :disabled="currentPageStorage === 1"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="First Page"
          >
            <i class="fas fa-angle-double-left"></i>
          </button>
          <button 
            @click="currentPageStorage--" 
            :disabled="currentPageStorage === 1"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Previous Page"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          <div class="flex gap-1">
            <button 
              v-for="page in visiblePagesStorage" 
              :key="page"
              @click="currentPageStorage = page"
              class="px-3 py-1 rounded transition-colors"
              :class="currentPageStorage === page ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-100 text-gray-700'"
            >
              {{ page }}
            </button>
          </div>
          <button 
            @click="currentPageStorage++" 
            :disabled="currentPageStorage === totalPagesStorage"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Next Page"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
          <button 
            @click="currentPageStorage = totalPagesStorage" 
            :disabled="currentPageStorage === totalPagesStorage"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Last Page"
          >
            <i class="fas fa-angle-double-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Feed Tank Log Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h2 class="text-lg font-bold text-slate-800">FEED TANK LOG</h2>
      </div>
      <div class="p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-slate-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider w-16">No</th>
              <th v-if="!plantSelectionStore.selectedPlantId" class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Plant</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Action</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">TraceNo (From >>> To)</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Entry Date</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Matl Doc</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Material</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Sloc</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Init Material (MT)</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Init Supplier (MT)</th>
              <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">On-Hand (MT)</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider w-1/4">Supplier / Batch SAP / Init Qty (MT) / Remark</th>
              <th class="px-4 py-3 text-center text-xs font-bold uppercase tracking-wider">Status</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Created at</th>
              <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider">Created by</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="transferStore.loading"><td :colspan="feedColumnCount" class="px-6 py-4 text-center text-gray-500">Loading...</td></tr>
            <tr v-else-if="feedLogsSafe.length === 0"><td :colspan="feedColumnCount" class="px-6 py-4 text-center text-gray-500">No feed logs found</td></tr>
            <tr v-for="(log, index) in paginatedFeedLogs" :key="log.id_trace_head" class="hover:bg-gray-50 text-sm">
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ (currentPageFeed - 1) * itemsPerPage + index + 1 }}</td>
              <td v-if="!plantSelectionStore.selectedPlantId" class="px-4 py-3 whitespace-nowrap text-gray-600 font-semibold">{{ log.plant_code || '-' }}</td>
              <td class="px-4 py-3 whitespace-nowrap">
                <button 
                  @click="deactivateTransfer(log.id_balance_head + '|' + log.id_trace_head)" 
                  :disabled="!log.traced || log.traced.trim() === ''"
                  class="text-red-600 hover:text-red-900 disabled:text-gray-400" 
                  :title="(!log.traced || log.traced.trim() === '') ? 'Transfer has been used' : 'Delete Transfer'"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </td>
              <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900 text-right">
                {{ log.trace_no || (log.from_trace_no ? log.from_trace_no + ' >>> ' + log.to_trace_no : log.trace_nos) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ formatDate(log.entry_date) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-900 text-center">{{ log.material_document || '-' }}</td>
              <td class="px-4 py-3 text-gray-900">{{ log.material }}</td>
              <td class="px-4 py-3 text-center text-gray-900">{{ log.tf_number }}</td>
              <td class="px-4 py-3 text-right font-medium" :class="log.init_qty === log.balance_supplier ? 'text-green-600' : 'text-red-600'">{{ log.init_qty }}</td>
              <td class="px-4 py-3 text-right font-medium" :class="log.init_qty === log.balance_supplier ? 'text-green-600' : 'text-red-600'">{{ log.balance_supplier }}</td>
              <td class="px-4 py-3 text-right font-bold text-gray-900">{{ log.qty }}</td>
              <td class="px-4 py-3 text-gray-900">
                <div class="max-h-20 overflow-y-auto whitespace-pre-wrap text-xs">
                  {{ formatSuppliers(log.supplier) }}
                </div>
              </td>
              <td class="px-4 py-3 text-center">
                <i v-if="log.traced === 'N/A'" class="fas fa-check text-green-500" title="Active / Not Transferred"></i>
                <i v-else class="fas fa-arrow-right text-blue-500" title="Transferred/Used"></i>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ log.created_at }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-xs">{{ log.created_by }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination Feed -->
      <div v-if="totalPagesFeed > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
        <div class="text-sm text-gray-600">
          Showing {{ (currentPageFeed - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPageFeed * itemsPerPage, feedLogsSafe.length) }} of {{ feedLogsSafe.length }} entries
        </div>
        <div class="flex gap-2">
          <button 
            @click="currentPageFeed = 1" 
            :disabled="currentPageFeed === 1"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="First Page"
          >
            <i class="fas fa-angle-double-left"></i>
          </button>
          <button 
            @click="currentPageFeed--" 
            :disabled="currentPageFeed === 1"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Previous Page"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          <div class="flex gap-1">
            <button 
              v-for="page in visiblePagesFeed" 
              :key="page"
              @click="currentPageFeed = page"
              class="px-3 py-1 rounded transition-colors"
              :class="currentPageFeed === page ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-100 text-gray-700'"
            >
              {{ page }}
            </button>
          </div>
          <button 
            @click="currentPageFeed++" 
            :disabled="currentPageFeed === totalPagesFeed"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Next Page"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
          <button 
            @click="currentPageFeed = totalPagesFeed" 
            :disabled="currentPageFeed === totalPagesFeed"
            class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            title="Last Page"
          >
            <i class="fas fa-angle-double-right"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Modals: always mounted (like Bootstrap modals in DOM) — avoids destroy/remount glitches; visibility via :is-open -->
    <RmEntryModal
      :is-open="isCreateModalOpen"
      :entry="selectedEntryForEdit"
      @close="isCreateModalOpen = false"
      @saved="fetchData"
    />
    <TransferModal
      :is-open="isTransferModalOpen"
      @close="isTransferModalOpen = false"
      @saved="fetchData"
    />
    
    <!-- Plant Selection Modal (Initial Popup) -->
    <PlantSelectionModal ref="plantSelectionModal" @selected="fetchData" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useTransactionRmEntryStore } from '@/stores/transactionRmEntry'
import { useTransactionTransferStore } from '@/stores/transactionTransfer'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useSetupPlantStore } from '@/stores/setupPlant'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import PlantSelectionModal from '@/components/shared/PlantSelectionModal.vue'
import RmEntryModal from '@/components/transaction/RmEntryModal.vue'
import TransferModal from '@/components/transaction/TransferModal.vue'
import Swal from 'sweetalert2'

const store = useTransactionRmEntryStore()
const transferStore = useTransactionTransferStore()
const plantSelectionStore = usePlantSelectionStore()

// State
const isCreateModalOpen = ref(false)
const isTransferModalOpen = ref(false)
const isSlocModalOpen = ref(false)
const selectedEntry = ref(null)
const selectedEntryForEdit = ref(null)

// Modal Reference
const plantSelectionModal = ref(null)

// Load data on mount
onMounted(async () => {
  if (!plantSelectionStore.hasSelectedPlant) {
    plantSelectionModal.value?.open()
  } else {
    await fetchData()
  }
})

// Pagination State
const itemsPerPage = 5
const currentPageStorage = ref(1)
const currentPageFeed = ref(1)
const maxVisiblePages = 5

// Computed
const loading = computed(() => store.loading)
const entries = computed(() => store.entries)
const filteredEntries = computed(() => Array.isArray(entries.value) ? entries.value : [])
const hasEntries = computed(() => filteredEntries.value.length > 0)
const isAllPlants = computed(() => !plantSelectionStore.selectedPlantId)
const storageColumnCount = computed(() => isAllPlants.value ? 16 : 15)
const feedColumnCount = computed(() => isAllPlants.value ? 15 : 14)

const paginatedStorageEntries = computed(() => {
  const start = (currentPageStorage.value - 1) * itemsPerPage
  return filteredEntries.value.slice(start, start + itemsPerPage)
})

const totalPagesStorage = computed(() => {
  return Math.ceil(filteredEntries.value.length / itemsPerPage)
})

const feedLogsSafe = computed(() => Array.isArray(transferStore.feedLogs) ? transferStore.feedLogs : [])

const paginatedFeedLogs = computed(() => {
  const start = (currentPageFeed.value - 1) * itemsPerPage
  return feedLogsSafe.value.slice(start, start + itemsPerPage)
})

const totalPagesFeed = computed(() => {
  return Math.ceil(feedLogsSafe.value.length / itemsPerPage)
})

// Computed page numbers for Storage
const visiblePagesStorage = computed(() => {
  const pages = []
  const startPage = Math.max(1, currentPageStorage.value - Math.floor(maxVisiblePages / 2))
  const endPage = Math.min(totalPagesStorage.value, startPage + maxVisiblePages - 1)
  
  // Adjust startPage if we're near the end
  const adjustedStart = Math.max(1, endPage - maxVisiblePages + 1)
  
  for (let i = adjustedStart; i <= endPage; i++) {
    pages.push(i)
  }
  return pages
})

// Computed page numbers for Feed
const visiblePagesFeed = computed(() => {
  const pages = []
  const startPage = Math.max(1, currentPageFeed.value - Math.floor(maxVisiblePages / 2))
  const endPage = Math.min(totalPagesFeed.value, startPage + maxVisiblePages - 1)
  
  // Adjust startPage if we're near the end
  const adjustedStart = Math.max(1, endPage - maxVisiblePages + 1)
  
  for (let i = adjustedStart; i <= endPage; i++) {
    pages.push(i)
  }
  return pages
})

// Methods
async function fetchData() {
  const params = { id_plant: plantSelectionStore.selectedPlantId || 0 }
  await Promise.all([
    store.fetchEntries(params),
    transferStore.fetchFeedLogs(params),
    // Prefetch for modals
    store.fetchTanks(),
    store.fetchMaterials(),
    store.searchSuppliers('')
  ])
}

function openCreateModal() {
  selectedEntryForEdit.value = null
  isTransferModalOpen.value = false
  isCreateModalOpen.value = true
}

function openTransferModal() {
  isCreateModalOpen.value = false
  isTransferModalOpen.value = true
}

function openUpdateModal(entry) {
  selectedEntryForEdit.value = entry
  isTransferModalOpen.value = false
  isCreateModalOpen.value = true
}

function openSlocEdit(entry) {
  selectedEntry.value = entry
  isSlocModalOpen.value = true
}

async function deactivateEntry(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'De-Activate this data',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes!'
  })

  if (result.isConfirmed) {
    try {
      await store.deactivateEntry(id)
      Swal.fire('Deactivated!', 'Entry has been deactivated.', 'success')
      fetchData()
    } catch (error) {
      Swal.fire('Error', error.message || 'Failed to deactivate', 'error')
    }
  }
}


async function deactivateTransfer(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'De-Activate this transfer?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#16a34a',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes!'
  })

  if (result.isConfirmed) {
    try {
      await transferStore.deleteTransfer(id)
      Swal.fire('Deactivated!', 'Transfer has been deactivated.', 'success')
      fetchData()
    } catch (error) {
      Swal.fire('Error', error.message || 'Failed to deactivate', 'error')
    }
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString()
}

function formatSuppliers(supplierString) {
  if (!supplierString) return '-'
  return supplierString.split(' | ').join('\n')
}

// Lifecycle
</script>
