<template>
  <VDialog
    :model-value="isOpen"
    max-width="480"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Blend Entry Source Material</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="closeModal"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5">
        <form @submit.prevent="handleInsert" class="d-flex flex-column gap-4">
          <VSelect
            v-model="form.idMaterialSource"
            :items="materialOptions"
            label="Blending Material"
            placeholder="Select Material"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
            @update:model-value="onMaterialChange"
          />
          <p id="stockLabel" class="text-caption text-medium-emphasis mt-n2">Stock : {{ stockDisplay }}</p>

<VTextField
              v-model.number="form.qty"
              type="number"
              step="any"
              label="Entry Qty (MT)"
              density="compact"
              variant="outlined"
              hide-details="auto"
              required
            />
            <VBtn color="primary" variant="outlined" size="small" @click="fetchQty">Fetch Qty</VBtn>

          <div class="d-flex justify-end gap-2 mt-2">
            <VBtn
              variant="outlined"
              color="medium-emphasis"
              @click="closeModal"
            >
              Cancel
            </VBtn>
            <VBtn
              type="submit"
              color="primary"
            >
              Insert Material
            </VBtn>
          </div>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useTsBlendingStore } from '../stores'
import { useToastStore } from '@/stores/toast.js'

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
const toastStore = useToastStore()

const form = reactive({
  idMaterialSource: '',
  qty: ''
})

const activeSourceMaterials = computed(() => {
  return blendingStore.activeMaterials
})

const materialOptions = computed(() => {
  return (blendingStore.activeMaterials || []).map(mat => ({
    value: mat.id_material,
    title: mat.material_code || mat.description || mat.material
  }))
})

const stockDisplay = computed(() => {
  if (!form.idMaterialSource) return 'N/A'
  const stock = blendingStore.totalStock
  return stock ? `${stock.toFixed(3)} MT` : '0.000 MT'
})

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

function onMaterialChange() {
  if (form.idMaterialSource) {
    blendingStore.fetchTotalStockMaterial({
      idMaterial: form.idMaterialSource,
      idTank: props.idTank
    })
  } else {
    blendingStore.totalStock = 0
  }
}

function fetchQty(){
    if(!form.idMaterialSource){toastStore.error('Select material first');return}
    blendingStore.fetchQty({idMaterial: form.idMaterialSource, idPlant: props.idPlant})
      .then(r=>{if(r?.status===1)form.qty=r.data?.qty||'';else toastStore.error(r?.message||'Fetch failed')})
      .catch(e=>{toastStore.error(e.message)})
}
  // removed stray fetchTotalStock call; handled elsewhere

async function handleInsert() {
  const plantId = props.idPlant

  if (!form.idMaterialSource || !form.qty) {
    toastStore.error('Select material and enter qty')
    return
  }

  if (parseFloat(blendingStore.totalStock) < parseFloat(form.qty)) {
    toastStore.error('Qty > Stock !')
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
      toastStore.error(response?.message || 'Failed to add material')
    }
  } catch (err) {
    toastStore.error(err.message)
  }
}

watch(() => props.isOpen, (val) => {
  if (!val && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (val) {
    form.idMaterialSource = ''
    form.qty = ''
    blendingStore.totalStock = 0
  }
})
</script>
