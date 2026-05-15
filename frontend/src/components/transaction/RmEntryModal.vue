<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Header -->
        <div class="bg-blue-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-white">
              Raw Material Entry
            </h3>
            <button @click="closeModal" class="text-white hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="bg-white px-6 py-4 max-h-[85vh] overflow-y-auto">
          <form @submit.prevent="handleSubmit">
            <!-- Header section (as in monorepo card) -->
            <div class="border border-gray-200 rounded-lg p-4 mb-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Entry Mode -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Entry Mode</label>
                  <input
                    v-model="form.mode"
                    type="text"
                    readonly
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-slate-800 font-bold"
                  />
                </div>
                <!-- Entry Number -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Entry Number (Auto)</label>
                  <input
                    v-model="form.rm_number"
                    type="text"
                    readonly
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-slate-800 font-bold"
                  />
                </div>
                <!-- Date -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Date (Auto Detect)</label>
                  <input
                    v-model="form.entry_date"
                    type="date"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Sloc -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Sloc</label>
                  <select
                    v-model="form.id_tank"
                    @change="onTankChange"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">- Select Sloc -</option>
                    <option v-for="tank in tanks" :key="tank.id_tank" :value="tank.id_tank">
                      {{ tank.tank }}
                    </option>
                  </select>
                </div>
                <!-- Material Document -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Material Document (SAP)</label>
                  <input
                    v-model="form.material_document"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                  />
                </div>
                <!-- PO -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Order (PO)</label>
                  <input
                    v-model="form.po_so"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <!-- Material -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1 text-xs">Material ( Do not change material selection after input supplier! )</label>
                  <select
                    v-model="form.id_material"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">- Select Material -</option>
                    <option v-for="material in materials" :key="material.id_material" :value="material.id_material">
                      {{ material.material }}
                    </option>
                  </select>
                </div>
                <!-- Specific Sloc -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Specific Sloc No</label>
                  <div class="border border-gray-300 rounded-lg p-2 max-h-32 overflow-y-auto">
                    <div v-if="tankDetails.length === 0" class="text-xs text-gray-500 py-1 italic">
                      Select Sloc first...
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                      <label v-for="detail in tankDetails" :key="detail.id_tank_tail" class="flex items-center gap-2 hover:bg-gray-50 p-1 rounded cursor-pointer">
                        <input
                          type="checkbox"
                          :value="detail.id_tank_tail"
                          v-model="form.id_tank_tail"
                          class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                        />
                        <span class="text-xs font-medium">{{ detail.tankNo }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between mt-4 gap-4">
                <div class="flex gap-2">
                  <button
                    type="button"
                    @click="isSupplierModalOpen = true"
                    class="px-4 py-2 bg-slate-800 text-white rounded shadow-sm hover:bg-slate-900 transition-colors"
                  >
                    Add Supplier & Qty
                  </button>
                  <button
                    type="button"
                    @click="handleSubmit"
                    :disabled="!canSubmit || loading"
                    class="px-4 py-2 bg-blue-600 text-white rounded shadow-sm hover:bg-blue-700 disabled:bg-gray-400 transition-colors"
                  >
                    Save Entry
                  </button>
                </div>
                <div class="flex items-center gap-2">
                  <label class="text-sm font-bold text-gray-700">Total Qty (MT)</label>
                  <input
                    v-model="totalQty"
                    type="text"
                    readonly
                    class="w-32 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-right font-bold"
                  />
                </div>
              </div>
            </div>

            <!-- Supplier table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-slate-700">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-bold uppercase w-16">No</th>
                    <th class="px-4 py-2 text-left text-xs font-bold uppercase">Action</th>
                    <th class="px-4 py-2 text-left text-xs font-bold uppercase">Material</th>
                    <th class="px-4 py-2 text-left text-xs font-bold uppercase">Supplier</th>
                    <th class="px-4 py-2 text-left text-xs font-bold uppercase">Batch SAP</th>
                    <th class="px-4 py-2 text-right text-xs font-bold uppercase">Qty (MT)</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-if="supplierList.length === 0">
                    <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 italic">No suppliers added yet.</td>
                  </tr>
                  <tr v-else v-for="(sup, index) in supplierList" :key="sup.id" class="hover:bg-gray-50 text-sm">
                    <td class="px-4 py-2 text-center">{{ index + 1 }}</td>
                    <td class="px-4 py-2">
                      <button type="button" @click="removeSupplier(sup.id)" class="text-red-600 hover:text-red-900">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                    <td class="px-4 py-2">{{ sup.material }}</td>
                    <td class="px-4 py-2">{{ sup.supplier }}</td>
                    <td class="px-4 py-2">{{ sup.batch_sap }}</td>
                    <td class="px-4 py-2 text-right font-medium">{{ sup.qty }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </form>
        </div>

        <!-- Supplier Modal Overlay -->
        <div v-if="isSupplierModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all scale-100">
            <div class="bg-slate-800 px-6 py-4 flex justify-between items-center">
              <h3 class="text-lg font-bold text-white">Add Supplier & Qty</h3>
              <button @click="isSupplierModalOpen = false" class="text-slate-400 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Supplier</label>
                <select
                  v-model="supplierForm.id_supplier"
                  @change="onSupplierChange"
                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none"
                >
                  <option value="">- Select Supplier -</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.text }}
                  </option>
                </select>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1">Batch SAP (Auto)</label>
                  <input
                    v-model="supplierForm.batch_sap"
                    type="text"
                    readonly
                    class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-lg font-mono text-slate-600"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-1">Quantity (MT)</label>
                  <input
                    v-model="supplierForm.qty"
                    type="number"
                    step="0.001"
                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all outline-none text-right font-bold"
                    placeholder="0.000"
                  />
                </div>
              </div>
              <div class="pt-4 flex gap-3">
                <button
                  type="button"
                  @click="addSupplier"
                  :disabled="!canAddSupplier"
                  class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-lg font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 disabled:bg-slate-300 disabled:shadow-none transition-all"
                >
                  Confirm & Add
                </button>
                <button
                  type="button"
                  @click="isSupplierModalOpen = false"
                  class="px-6 py-2.5 bg-slate-100 text-slate-600 rounded-lg font-bold hover:bg-slate-200 transition-all"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
          <button
            type="button"
            @click="closeModal"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="handleSubmit"
            :disabled="!canSubmit || loading"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Saving...' : 'Save RM Entry' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useTransactionRmEntryStore } from '@/stores/transactionRmEntry'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const store = useTransactionRmEntryStore()

// State
const isSupplierModalOpen = ref(false)
const form = ref({
  mode: 'ADD',
  entry_date: new Date().toISOString().split('T')[0],
  rm_number: '',
  id_material: '',
  id_tank: '',
  id_tank_tail: [],
  material_document: '',
  po_so: '',
  total_qty: 0
})

const supplierForm = ref({
  id_supplier: '',
  batch_sap: '',
  qty: ''
})

// Computed
const loading = computed(() => store.loading)
const tanks = computed(() => store.tanks)
const tankDetails = computed(() => store.tankDetails)
const materials = computed(() => store.materials)
const suppliers = computed(() => store.suppliers)
const supplierList = computed(() => store.supplierList)
const totalQty = computed(() => store.totalQty)

const canAddSupplier = computed(() => {
  return supplierForm.value.id_supplier &&
         supplierForm.value.batch_sap &&
         supplierForm.value.qty &&
         parseFloat(supplierForm.value.qty) > 0 &&
         form.value.id_material
})

const canSubmit = computed(() => {
  return form.value.entry_date &&
         form.value.rm_number &&
         form.value.id_material &&
         form.value.id_tank &&
         form.value.id_tank_tail.length > 0 &&
         supplierList.value.length > 0 &&
         parseFloat(totalQty.value.replace(/,/g, '')) > 0
})

// Methods
async function initialize() {
  try {
    await Promise.all([
      store.generateRmNumber(),
      store.fetchTanks(),
      store.fetchMaterials(),
      store.searchSuppliers('')
    ])
    form.value.rm_number = store.rmNumber
    form.value.mode = 'ADD'
    // Also fetch initial supplier list for this number (if any)
    await store.fetchSupplierList(form.value.rm_number)
  } catch (error) {
    console.error('Initialization error:', error)
  }
}

async function onTankChange() {
  form.value.id_tank_tail = []
  if (form.value.id_tank) {
    await store.fetchTankDetails(form.value.id_tank)
  }
}

async function onSupplierChange() {
  if (supplierForm.value.id_supplier) {
    const batchCode = await store.generateBatchCode(supplierForm.value.id_supplier)
    supplierForm.value.batch_sap = batchCode
  }
}

async function addSupplier() {
  if (!canAddSupplier.value) return

  try {
    await store.addSupplier({
      entry_no: form.value.rm_number,
      id_supplier: supplierForm.value.id_supplier,
      id_material: form.value.id_material,
      qty: parseFloat(supplierForm.value.qty),
      batch_sap: supplierForm.value.batch_sap
    })

    // Reset supplier form and close modal
    supplierForm.value = {
      id_supplier: '',
      batch_sap: '',
      qty: ''
    }
    isSupplierModalOpen.value = false
  } catch (error) {
    console.error('Add supplier error:', error)
  }
}

async function removeSupplier(id) {
  if (confirm('Are you sure you want to remove this supplier?')) {
    await store.deleteSupplier(id, form.value.rm_number)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return

  try {
    const data = {
      ...form.value,
      total_qty: parseFloat(totalQty.value.replace(/,/g, ''))
    }

    await store.createEntry(data)
    emit('saved')
    closeModal()
  } catch (error) {
    console.error('Submit error:', error)
  }
}

function closeModal() {
  emit('close')
}

// Lifecycle
onMounted(() => {
  if (props.isOpen) {
    initialize()
  }
})

// Watch for modal open
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    initialize()
  }
})
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
