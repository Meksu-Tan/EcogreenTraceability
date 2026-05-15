<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal"></div>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <!-- Header -->
        <div class="bg-green-600 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-white">
              Transfer to Feed Tank
            </h3>
            <button @click="closeModal" class="text-white hover:text-gray-200">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="bg-white px-6 py-4">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Date</label>
              <input
                v-model="form.entry_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              />
            </div>

            <!-- Source (Storage Tank Entry) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Source (Storage Tank / Trace No)</label>
              <select
                v-model="form.id_balance_head"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              >
                <option value="">Select Source Entry</option>
                <option v-for="entry in sourceEntries" :key="entry.id_balance_head" :value="entry.id_balance_head">
                  {{ entry.tank }} :: {{ entry.trace_no }} ({{ entry.material }}) - Bal: {{ entry.qty }} MT
                </option>
              </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Destination Feed Tank -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Destination Feed Tank</label>
                <select
                  v-model="form.id_dest_tank"
                  @change="onTankChange"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                >
                  <option value="">Select Feed Tank</option>
                  <option v-for="tank in destTanks" :key="tank.id_tank" :value="tank.id_tank">
                    {{ tank.tank }}
                  </option>
                </select>
              </div>

              <!-- Sub Tanks -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sub Tanks (TF Number)</label>
                <div class="border border-gray-300 rounded-lg p-2 max-h-32 overflow-y-auto">
                  <div v-if="tankDetails.length === 0" class="text-sm text-gray-500 text-center py-2">
                    Select a tank first
                  </div>
                  <label v-for="detail in tankDetails" :key="detail.id_tank_tail" class="flex items-center py-1 hover:bg-gray-50 px-2 rounded">
                    <input
                      type="checkbox"
                      :value="detail.id_tank_tail"
                      v-model="form.id_dest_tank_tail"
                      class="mr-2 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                    />
                    <span class="text-sm">{{ detail.tankNo }}</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Quantity -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantity to Transfer (MT)</label>
              <input
                v-model="form.qty"
                type="number"
                step="0.001"
                min="0.001"
                required
                :max="maxQty"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              />
              <p v-if="selectedSource" class="mt-1 text-xs text-gray-500">
                Maximum available: {{ selectedSource.qty }} MT
              </p>
            </div>
          </form>
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
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Processing...' : 'Confirm Transfer' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useTransactionTransferStore } from '@/stores/transactionTransfer'

const props = defineProps({
  isOpen: { type: Boolean, required: true }
})

const emit = defineEmits(['close', 'saved'])

const store = useTransactionTransferStore()

// State
const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  id_balance_head: '',
  id_dest_tank: '',
  id_dest_tank_tail: [],
  qty: ''
})

// Computed
const loading = computed(() => store.loading)
const sourceEntries = computed(() => store.sourceEntries)
const destTanks = computed(() => store.destTanks)
const tankDetails = computed(() => store.tankDetails)

const selectedSource = computed(() => {
  return sourceEntries.value.find(e => e.id_balance_head === form.value.id_balance_head)
})

const maxQty = computed(() => selectedSource.value?.qty || 0)

const canSubmit = computed(() => {
  return form.value.entry_date &&
         form.value.id_balance_head &&
         form.value.id_dest_tank &&
         form.value.id_dest_tank_tail.length > 0 &&
         form.value.qty &&
         parseFloat(form.value.qty) > 0 &&
         parseFloat(form.value.qty) <= maxQty.value
})

// Methods
async function initialize() {
  await Promise.all([
    store.fetchSourceEntries(),
    store.fetchDestTanks()
  ])
}

async function onTankChange() {
  form.value.id_dest_tank_tail = []
  if (form.value.id_dest_tank) {
    await store.fetchTankDetails(form.value.id_dest_tank)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return
  try {
    await store.performTransfer(form.value)
    emit('saved')
  } catch (error) {
    console.error('Transfer error:', error)
  }
}

function closeModal() {
  emit('close')
}

// Watchers
watch(() => props.isOpen, (newVal) => {
  if (newVal) initialize()
})

onMounted(() => {
  if (props.isOpen) initialize()
})
</script>
