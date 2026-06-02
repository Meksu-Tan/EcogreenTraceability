<template>
  <Teleport to="body">
    <div v-show="isOpen" class="fixed inset-0 z-[110] overflow-y-auto" role="dialog" aria-modal="true" :aria-hidden="!isOpen">
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />
        <div class="relative z-[2] mx-auto flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 to-green-600 px-6 py-4">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">Sloc</p>
              <h3 class="text-lg font-bold text-white">Edit Specific Sloc</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-5">
            <p class="mb-4 text-sm text-slate-600">Main SLoc: <strong>{{ props.mainSloc }}</strong></p>
            <form @submit.prevent="handleSave">
              <div>
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Specific Storage Location</label>
                <div v-if="loadingTanks" class="mt-2 text-sm text-slate-400">Loading...</div>
                <div v-else class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                  <label
                    v-for="tank in availableTanks"
                    :key="tank.id_tank_tail"
                    class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50 cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :value="String(tank.id_tank_tail)"
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
                <button type="submit" class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700">Save</button>
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
import { useTsBlendingStore } from '../stores'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  idHead: { type: [String, Number], default: null },
  idTank: { type: [String, Number], default: null },
  mainSloc: { type: String, default: '' },
  idTankTail: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:isOpen', 'success'])

const blendingStore = useTsBlendingStore()
const availableTanks = ref([])
const selectedTails = ref([])
const loadingTanks = ref(false)
const errorMsg = ref('')

function closeModal() {
  emit('update:isOpen', false)
}

async function loadTanks() {
  loadingTanks.value = true
  try {
    const response = await blendingStore.fetchActiveSpecificTanksRundown({ sloc: props.idTank })
    availableTanks.value = response?.data || []
  } catch (err) {
    errorMsg.value = 'Failed to load tanks'
  } finally {
    loadingTanks.value = false
  }
}

async function handleSave() {
  errorMsg.value = ''
  try {
    const response = await blendingStore.updateSubTank({
      idHead: props.idHead,
      idTankTail: selectedTails.value
    })
    if (response?.status === 1) {
      emit('success')
    } else {
      errorMsg.value = response?.message || 'Failed to update sub-tank'
    }
  } catch (err) {
    errorMsg.value = err.message
  }
}

watch(() => props.isOpen, (val) => {
  if (val) {
    selectedTails.value = [...(props.idTankTail || [])].map(String)
    errorMsg.value = ''
    loadTanks()
  }
})
</script>
