<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Transfer List</h1>
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
        <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-400"></i></div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading data</h3>
          <p class="mt-2 text-sm text-red-700">{{ transferStore.error }}</p>
          <button @click="fetchData" class="mt-3 rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200">Coba lagi</button>
        </div>
      </div>
    </div>

    <!-- Transfer Table -->
    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
              <th v-if="!plantSelectionStore.selectedPlantId" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plant</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trace No</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sloc</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Trace</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matl Doc</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(trf, index) in transferStore.transferList" :key="trf.id_balance_head" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ index + 1 }}</td>
              <td v-if="!plantSelectionStore.selectedPlantId" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ trf.plant_code || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ trf.trace_no }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(trf.entry_date) }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ trf.material }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ trf.sloc_description || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">{{ trf.qty }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ trf.from_trace_no || '-' }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button @click="openMatlDocModal(trf)" class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100">
                  <i class="fas fa-file-alt"></i>
                  {{ trf.material_document ? trf.material_document : 'Add' }}
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ trf.created_by }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                <button
                  @click="deactivateTransfer(trf)"
                  :disabled="deactivatingId === trf.id_balance_head"
                  class="inline-flex items-center gap-1 rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-50"
                >
                  <i v-if="deactivatingId !== trf.id_balance_head" class="fas fa-trash-alt"></i>
                  <i v-else class="fas fa-spinner fa-spin"></i>
                  {{ deactivatingId === trf.id_balance_head ? '...' : 'Deactivate' }}
                </button>
              </td>
            </tr>
            <tr v-if="transferStore.transferList.length === 0">
              <td :colspan="plantSelectionStore.selectedPlantId ? 10 : 11" class="px-6 py-12 text-center text-sm text-gray-500">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                <p>Belum ada data transfer</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Material Document Modal -->
    <MaterialDocModal
      v-if="selectedTransfer"
      v-model:is-open="isMatlDocModalOpen"
      :mode="matlDocMode"
      :id-trace-head="matlDocIdTraceHead"
      :current-number="matlDocCurrentNumber"
      @success="onMatlDocSuccess"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant'
import { useTsTransferStore } from '@/modules/ts-transfer/stores'
import PlantSelector from '@/components/shared/PlantSelector.vue'
import MaterialDocModal from '@/modules/ts-transfer/components/MaterialDocModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const transferStore = useTsTransferStore()

const isMatlDocModalOpen = ref(false)
const deactivatingId = ref(null)
const selectedTransfer = ref(null)
const matlDocMode = ref('ADD')
const matlDocIdTraceHead = ref(null)
const matlDocCurrentNumber = ref('')

function formatDate(dateString) {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric'
  })
}

async function fetchData() {
  const plantId = plantSelectionStore.selectedPlantId || 0
  await transferStore.fetchTransferList(plantId)
}

function openMatlDocModal(trf) {
  selectedTransfer.value = trf
  matlDocIdTraceHead.value = trf.id_trace_head
  matlDocMode.value = trf.material_document ? 'UPDATE' : 'ADD'
  matlDocCurrentNumber.value = trf.material_document || ''
  isMatlDocModalOpen.value = true
}

function onMatlDocSuccess() {
  isMatlDocModalOpen.value = false
  selectedTransfer.value = null
  fetchData()
}

async function deactivateTransfer(trf) {
  if (!confirm('Are you sure you want to deactivate this transfer?')) return

  deactivatingId.value = trf.id_balance_head
  try {
    await transferStore.deleteTransfer(trf.id_balance_head)
    alert('Transfer deactivated successfully')
    fetchData()
  } catch (error) {
    alert(error.message || 'Failed to deactivate transfer')
  } finally {
    deactivatingId.value = null
  }
}

watch(() => plantSelectionStore.selectedPlantId, () => {
  fetchData()
})

onMounted(() => {
  fetchData()
})
</script>
