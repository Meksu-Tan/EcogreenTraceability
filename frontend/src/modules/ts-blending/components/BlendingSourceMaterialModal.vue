<template>
  <Teleport to="body">
    <div v-show="isOpen" class="fixed inset-0 z-[110] overflow-y-auto" role="dialog" aria-modal="true" :aria-hidden="!isOpen">
      <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
        <div class="fixed inset-0 z-[1] bg-black/40 backdrop-blur-sm" aria-hidden="true" @click="closeModal" />
        <div class="relative z-[2] mx-auto flex w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white text-left shadow-xl">
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 to-green-600 px-6 py-4">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">Blending</p>
              <h3 class="text-lg font-bold text-white">Blend Entry Source Material</h3>
            </div>
            <button type="button" @click="closeModal" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="px-6 py-5">
            <form @submit.prevent="handleInsert">
              <div class="space-y-4">
                <div>
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Blending Material</label>
                  <select
                    v-model="form.idMaterialSource"
                    @change="onMaterialChange"
                    required
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  >
                    <option value="">- Select Material -</option>
                    <option v-for="mat in activeSourceMaterials" :key="mat.id_material" :value="mat.id_material">
                      {{ mat.material_code || mat.description || mat.material }}
                    </option>
                  </select>
                  <p id="stockLabel" class="mt-1 text-xs text-slate-500">Stock : {{ stockDisplay }}</p>
                </div>
                <div>
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Entry Qty (MT)</label>
                  <input
                    v-model="form.qty"
                    type="number"
                    step="any"
                    required
                    class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
              </div>
              <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" @click="closeModal" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                  Cancel
                </button>
                <button type="submit" class="rounded-xl bg-green-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-green-700">
                  Insert Material
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
import { ref, reactive, watch, computed } from 'vue'
import { useTsBlendingStore } from '../stores'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  entryNo: { type: String, default: '' },
  mode: { type: String, default: 'ADD' },
  idHead: { type: [String, Number], default: null },
  idTank: { type: [String, Number], default: null },
  idMaterial: { type: [String, Number], default: null },
  idPlant: { type: [String, Number], default: 0 }
})

const emit = defineEmits(['update:isOpen', 'success'])

const blendingStore = useTsBlendingStore()

const form = reactive({
  idMaterialSource: '',
  qty: ''
})

const errorMsg = ref('')

const activeSourceMaterials = computed(() => {
  return blendingStore.activeMaterials
})

const stockDisplay = computed(() => {
  if (!form.idMaterialSource) return 'N/A'
  const stock = blendingStore.totalStock
  return stock ? `${stock.toFixed(3)} MT` : '0.000 MT'
})

function closeModal() {
  emit('update:isOpen', false)
}

async function onMaterialChange() {
  if (form.idMaterialSource) {
    await blendingStore.fetchTotalStockMaterial({
      idMaterial: form.idMaterialSource,
      id_plant: props.idPlant
    })
  }
}

async function handleInsert() {
  errorMsg.value = ''
  const plantId = props.idPlant

  if (!form.idMaterialSource || !form.qty) {
    errorMsg.value = 'Select material and enter qty'
    return
  }

  if (parseFloat(blendingStore.totalStock) < parseFloat(form.qty)) {
    errorMsg.value = 'Qty > Stock !'
    return
  }

  try {
    const response = await blendingStore.addMaterialToBlending({
      entryNo: props.entryNo,
      idMaterialSource: form.idMaterialSource,
      qty: form.qty,
      idTank: props.idTank,
      id_plant: plantId,
      mode: props.mode
    })

    if (response?.status === 1) {
      emit('success')
      form.idMaterialSource = ''
      form.qty = ''
    } else {
      errorMsg.value = response?.message || 'Failed to add material'
    }
  } catch (err) {
    errorMsg.value = err.message
  }
}

watch(() => props.isOpen, (val) => {
  if (val) {
    form.idMaterialSource = ''
    form.qty = ''
    errorMsg.value = ''
    blendingStore.totalStock = 0
  }
})
</script>
