<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center gap-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Blending</h1>
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
        @click="openBlendingModal"
        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
      >
        <i class="fas fa-plus"></i>
        New Blending Entry
      </button>
    </div>

    <!-- Warning -->
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
      <p class="text-sm text-red-700">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="blendingStore.loading" class="flex items-center justify-center py-12">
      <svg class="h-8 w-8 animate-spin text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
      <span class="ml-3 text-sm text-gray-600">Memuat data...</span>
    </div>

    <!-- Error State -->
    <div v-else-if="blendingStore.error" class="rounded-lg border border-red-200 bg-red-50 p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fas fa-exclamation-circle text-red-400"></i>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading data</h3>
          <p class="mt-2 text-sm text-red-700">{{ blendingStore.error }}</p>
          <button @click="fetchData" class="mt-3 rounded-md bg-red-100 px-3 py-2 text-sm font-medium text-red-800 hover:bg-red-200">Coba lagi</button>
        </div>
      </div>
    </div>

    <!-- Blending List DataTable -->
    <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Entry Date</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Matl Doc</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trace No</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Plant</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blending Source</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sloc</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Init Material (MT)</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">Init Supplier (MT)</th>
            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">On-Hand (MT)</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="min-width:200px">Supplier / Batch SAP / Init Qty (MT) / Remark</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(item, index) in blendingStore.blendingList" :key="item.idHead || item.trace_no" class="hover:bg-gray-50">
            <td class="px-3 py-3 whitespace-nowrap text-center text-gray-900">{{ index + 1 }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-center">
              <button
                v-if="!item.next_process"
                @click="deactivateBlending(item)"
                class="text-red-500 hover:text-red-700 text-sm"
                title="De-Activate"
              >
                <i class="fas fa-trash"></i>
              </button>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-center text-gray-900">{{ item.entry_date }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-center">
              <span v-if="item.material_document">
                {{ item.material_document }}
                <button
                  @click="openMatlDocEdit(item)"
                  class="text-yellow-600 hover:text-yellow-800 ml-1 text-xs"
                  title="Edit"
                >
                  <i class="fas fa-pen"></i>
                </button>
              </span>
              <button
                v-else
                @click="openMatlDocAdd(item)"
                class="inline-flex items-center gap-1 rounded bg-yellow-100 px-2 py-0.5 text-xs text-yellow-800 hover:bg-yellow-200"
              >
                Add Doc No
              </button>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-center font-mono text-gray-900">{{ item.trace_no }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-center text-gray-900">{{ item.plant_name || '-' }}</td>
            <td class="px-3 py-3 text-gray-900">{{ item.material }}</td>
            <td class="px-3 py-3 text-xs">
              <template v-if="item.from_trace_no">
                <span
                  v-for="(batch, bi) in (typeof item.from_trace_no === 'string' ? item.from_trace_no.split('|') : [item.from_trace_no])"
                  :key="bi"
                  class="badge badge-light inline-block mr-1 mb-0.5 px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200 text-[10px] font-medium"
                >{{ batch.trim() }}</span>
              </template>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-center">
              <a
                href="#"
                @click.prevent="openSubTankEdit(item)"
                class="text-gray-500 hover:text-gray-700 text-xs underline decoration-dotted"
              >
                {{ item.sloc }}
              </a>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-right font-mono text-gray-900">{{ item.init_qty }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-right font-mono" :class="initSupplierColor(item)">{{ item.balance_supplier }}</td>
            <td class="px-3 py-3 whitespace-nowrap text-right font-mono text-gray-900">{{ item.qty }}</td>
            <td class="px-3 py-3 text-xs" style="min-width:200px">
              <template v-if="item.supplier">
                <span
                  v-for="(sup, si) in (typeof item.supplier === 'string' ? item.supplier.split('|') : [item.supplier])"
                  :key="si"
                  class="badge badge-primary inline-block mr-1 mb-0.5 px-1.5 py-0.5 rounded bg-green-600 text-white text-[10px] font-medium"
                >{{ sup.trim() }}</span>
              </template>
            </td>
          </tr>
          <tr v-if="blendingStore.blendingList.length === 0">
            <td colspan="12" class="px-6 py-12 text-center text-sm text-gray-500">
              <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
              <p>Belum ada data blending</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modals -->
    <BlendingModal v-model:is-open="isModalOpen" @success="onBlendingSuccess" />
    <MaterialDocModal
      v-model:is-open="isMatlDocModalOpen"
      :id-trace-head="matlDocContext.idTraceHead"
      :current-number="matlDocContext.currentNumber"
      :mode="matlDocContext.mode"
      @success="onMatlDocSuccess"
    />
    <SubTankEditModal
      v-model:is-open="isSubTankModalOpen"
      :id-head="subTankContext.idHead"
      :id-tank="subTankContext.idTank"
      :main-sloc="subTankContext.mainSloc"
      :id-tank-tail="subTankContext.idTankTail"
      @success="onSubTankSuccess"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, reactive, computed } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant'
import { useTsBlendingStore } from '@/modules/ts-blending/stores'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import BlendingModal from '@/modules/ts-blending/components/BlendingModal.vue'
import MaterialDocModal from '@/modules/ts-blending/components/MaterialDocModal.vue'
import SubTankEditModal from '@/modules/ts-blending/components/SubTankEditModal.vue'

const plantSelectionStore = usePlantSelectionStore()
const blendingStore = useTsBlendingStore()

const isAllPlant = computed(() => plantSelectionStore.selectedPlantId === null)

const isModalOpen = ref(false)
const isMatlDocModalOpen = ref(false)
const isSubTankModalOpen = ref(false)

const matlDocContext = reactive({
  idTraceHead: null,
  currentNumber: '',
  mode: 'ADD'
})

const subTankContext = reactive({
  idHead: null,
  idTank: null,
  mainSloc: '',
  idTankTail: []
})

function initSupplierColor(item) {
  const initQty = parseFloat(item.init_qty) || 0
  const balanceSupplier = parseFloat(item.balance_supplier) || 0
  return initQty === balanceSupplier ? 'text-green-600' : 'text-red-600'
}

async function fetchData() {
  const plantId = plantSelectionStore.selectedPlantId
  await blendingStore.fetchBlendingList({ id_plant: plantId })
}

function openBlendingModal() {
  isModalOpen.value = true
}

function onBlendingSuccess() {
  fetchData()
}

function openMatlDocAdd(item) {
  matlDocContext.idTraceHead = item.idTraceHead
  matlDocContext.currentNumber = ''
  matlDocContext.mode = 'ADD'
  isMatlDocModalOpen.value = true
}

function openMatlDocEdit(item) {
  matlDocContext.idTraceHead = item.idTraceHead
  matlDocContext.currentNumber = item.material_document
  matlDocContext.mode = 'UPDATE'
  isMatlDocModalOpen.value = true
}

function onMatlDocSuccess() {
  isMatlDocModalOpen.value = false
  fetchData()
}

function openSubTankEdit(item) {
  let tankTails = item.id_tank_tail
  if (typeof tankTails === 'string') {
    try { tankTails = JSON.parse(tankTails) } catch { tankTails = [] }
  }
  subTankContext.idHead = item.idHead
  subTankContext.idTank = item.id_tank
  subTankContext.mainSloc = item.sloc || ''
  subTankContext.idTankTail = Array.isArray(tankTails) ? tankTails : []
  isSubTankModalOpen.value = true
}

function onSubTankSuccess() {
  isSubTankModalOpen.value = false
  fetchData()
}

async function deactivateBlending(item) {
  if (confirm('Are you sure? De-Activate this data')) {
    await blendingStore.deactivateBlending(item.idHead + '|' + item.idTraceHead)
    fetchData()
  }
}

watch(() => plantSelectionStore.selectedPlantId, () => { fetchData() })
onMounted(() => { fetchData() })
</script>