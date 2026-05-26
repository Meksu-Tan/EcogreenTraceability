<template>
  <Teleport to="body">
    <div v-show="isOpen" class="fixed inset-0 z-[110] overflow-y-auto" role="dialog" aria-modal="true" :aria-hidden="!isOpen">
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />
        <div class="relative z-[2] mx-auto flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-blue-600 to-blue-600 px-6 py-4">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-blue-100/90">Sloc</p>
              <h3 class="text-lg font-bold text-white">Assign Specific Sloc (Sub Tank)</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-5">
            <p class="mb-4 text-sm text-slate-600">Main SLoc: <strong>{{ mainSloc }}</strong></p>
            <form @submit.prevent="handleSave">
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Select Specific Sloc</label>
                <div v-if="loadingTanks" class="mt-2 text-sm text-slate-400">Loading...</div>
                <div v-else class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                  <label
                    v-for="tank in availableTanks"
                    :key="tank.id_sloc_tail"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50 cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :value="String(tank.id_sloc_tail)"
                      v-model="selectedTails"
                      class="h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
                    />
                    <span class="text-sm text-slate-700">{{ tank.tankNo || tank.tf_number }}</span>
                  </label>
                  <p v-if="availableTanks.length === 0" class="text-sm text-slate-400">No specific sloc available</p>
                </div>
              </div>
              <p v-if="errorMsg" class="mt-2 text-sm text-red-600">{{ errorMsg }}</p>
              <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="closeModal" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Cancel</button>
                <button type="submit" :disabled="loading" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:bg-slate-300">
                  <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 inline" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTsTransferStore } from '../stores'

const props = defineProps({
  isOpen: Boolean,
  idHead: { type: Number, default: null },
  idTank: { type: Number, default: null },
  mainSloc: { type: String, default: '' },
  idTankTail: { type: Array, default: () => [] },
  isSource: { type: Boolean, default: false }
})

const emit = defineEmits(['update:isOpen', 'success'])

const transferStore = useTsTransferStore()
const availableTanks = ref([])
const selectedTails = ref([])
const loadingTanks = ref(false)
const loading = ref(false)
const errorMsg = ref('')

async function bootstrap() {
  errorMsg.value = ''
  loadingTanks.value = true
  selectedTails.value = [...props.idTankTail.map(String)]

  try {
    await transferStore.fetchSpecificTankRundown(props.idTank)
    availableTanks.value = [...transferStore.specificTanks]

    if (availableTanks.value.length === 1) {
      selectedTails.value = [String(availableTanks.value[0].id_sloc_tail)]
    }
  } catch (e) {
    errorMsg.value = 'Failed to load specific sloc options'
  }
  loadingTanks.value = false
}

async function handleSave() {
  if (!props.idHead || selectedTails.value.length === 0) return
  loading.value = true
  errorMsg.value = ''

  try {
    const response = await transferStore.submitUpdateEntrySubTank(props.idHead, selectedTails.value)
    if (response?.status === 1) {
      emit('success')
    } else {
      errorMsg.value = 'Failed to save'
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message || e.message || 'Error'
  }
  loading.value = false
}

function closeModal() {
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (open) => {
  if (open) bootstrap()
})
</script>
