<template>
  <Teleport to="body">
    <div v-show="isOpen" class="fixed inset-0 z-[110] overflow-y-auto" role="dialog" aria-modal="true" :aria-hidden="!isOpen">
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />
        <div class="relative z-[2] mx-auto flex w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-yellow-600 to-yellow-600 px-6 py-4">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-yellow-100/90">Material Document</p>
              <h3 class="text-lg font-bold text-white">{{ mode === 'ADD' ? 'Add' : 'Edit' }} Material Document</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-5">
            <form @submit.prevent="handleSave">
              <div class="space-y-4">
                <div>
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material Document No (SAP)</label>
                  <input
                    v-model="docNumber"
                    type="text"
                    required
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
                <p v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</p>
              </div>
              <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="closeModal" class="rounded-xl border border-slate-200 bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-200">Cancel</button>
                <button type="submit" :disabled="loading" class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700 disabled:bg-slate-300">
                  <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 inline" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  {{ mode === 'ADD' ? 'Add' : 'Update' }}
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
  idTraceHead: { type: Number, default: null },
  currentNumber: { type: String, default: '' },
  mode: { type: String, default: 'ADD' }
})

const emit = defineEmits(['update:isOpen', 'success'])

const transferStore = useTsTransferStore()
const docNumber = ref('')
const errorMsg = ref('')
const loading = ref(false)

async function handleSave() {
  if (!docNumber.value || !props.idTraceHead) return
  loading.value = true
  errorMsg.value = ''

  try {
    const response = await transferStore.submitMatlDocNumber(props.mode, props.idTraceHead, docNumber.value)
    if (response?.status === 1) {
      emit('success')
    } else {
      errorMsg.value = 'Failed to save material document'
    }
  } catch (e) {
    errorMsg.value = e.response?.data?.message || e.message || 'Error'
  }
  loading.value = false
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (open) => {
  if (!open && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (open) {
    docNumber.value = props.mode === 'UPDATE' ? props.currentNumber : ''
    errorMsg.value = ''
  }
})
</script>
