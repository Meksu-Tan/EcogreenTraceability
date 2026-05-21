<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Stock Transfer</h1>
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
      <button
        @click="openTransferModal"
        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
      >
        <i class="fas fa-plus"></i>
        Transfer Baru
      </button>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          @click="activeTab = 'storage'"
          :class="[
            activeTab === 'storage'
              ? 'border-green-500 text-green-600'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
          ]"
        >
          <i class="fas fa-warehouse mr-2"></i>
          Storage Log
          <span
            v-if="transferStore.storageLogs.length > 0"
            class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600"
          >
            {{ transferStore.storageLogs.length }}
          </span>
        </button>
        <button
          @click="activeTab = 'feed'"
          :class="[
            activeTab === 'feed'
              ? 'border-green-500 text-green-600'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
          ]"
        >
          <i class="fas fa-oil-can mr-2"></i>
          Feed Log
          <span
            v-if="transferStore.feedLogs.length > 0"
            class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600"
          >
            {{ transferStore.feedLogs.length }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="transferStore.loading" class="flex items-center justify-center py-12">
      <svg class="h-8 w-8 animate-spin text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
      <span class="ml-3 text-sm text-gray-600">Memuat data...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="transferStore.error" class="rounded-lg border border-red-200 bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-circle text-red-400"></i>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading data</h3>
          <p class="mt-2 text-sm text-red-700">{{ transferStore.error }}</p>
          <button
            @click="fetchData"
            class="mt-3 rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200"
          >
            Coba lagi
          </button>
        </div>
      </div>
    </div>

    <!-- Storage Log Tab -->
    <div v-else-if="activeTab === 'storage'" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tank</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From/To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Out Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doc/PO</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(log, index) in transferStore.storageLogs" :key="log.id_trace_head" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ index + 1 }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(log.entry_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.material_code }}<br><span class="text-xs text-gray-500">{{ log.material_name }}</span></td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ log.tank_name }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="text-xs">
                  <div><span class="text-gray-500">From:</span> {{ log.from_trace_no || '-' }}</div>
                  <div><span class="text-gray-500">To:</span> {{ log.to_trace_no || '-' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.in_qty }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.out_qty }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ log.material_document || '-' }}<br>
                <span class="text-xs">{{ log.po_so || '-' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.created_by }}</td>
            </tr>
            <tr v-if="transferStore.storageLogs.length === 0">
              <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                <p>Belum ada data storage log</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Feed Log Tab -->
    <div v-else-if="activeTab === 'feed'" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feed Tank</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From/To</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">In Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Out Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doc/PO</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(log, index) in transferStore.feedLogs" :key="log.id_trace_head" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ index + 1 }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(log.entry_date) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.material_code }}<br><span class="text-xs text-gray-500">{{ log.material_name }}</span></td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ log.tank_name || log.tank_description }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div class="text-xs">
                  <div><span class="text-gray-500">From:</span> {{ log.from_trace_no || '-' }}</div>
                  <div><span class="text-gray-500">To:</span> {{ log.to_trace_no || '-' }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.in_qty }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.out_qty }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ log.material_document || '-' }}<br>
                <span class="text-xs">{{ log.po_so || '-' }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.created_by }}</td>
            </tr>
            <tr v-if="transferStore.feedLogs.length === 0">
              <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                <p>Belum ada data feed log</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Transfer Modal -->
    <TransferModal v-model:is-open="isModalOpen" @success="onTransferSuccess" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useTransactionTransferStore } from '@/stores/transactionTransfer'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import TransferModal from '@/components/transaction/TransferModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const transferStore = useTransactionTransferStore()

const activeTab = ref('storage')
const isModalOpen = ref(false)

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

async function fetchData() {
  const plantId = plantSelectionStore.selectedPlantId
  await Promise.all([
    transferStore.fetchStorageLogs({ id_plant: plantId }),
    transferStore.fetchFeedLogs({ id_plant: plantId })
  ])
}

function openTransferModal() {
  isModalOpen.value = true
}

function onTransferSuccess() {
  fetchData()
}

// Reload data when plant changes
watch(() => plantSelectionStore.selectedPlantId, () => {
  fetchData()
})

// Reload data when switching to a tab (in case plant changed while on other tab)
watch(activeTab, () => {
  fetchData()
})

onMounted(() => {
  fetchData()
})
</script>
