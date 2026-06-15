<template>
  <VDialog
    :model-value="isOpen"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Transfer Inter-Plant Entry</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="closeModal"
        />
      </VCardTitle>

      <VDivider />

      <VCardText class="pa-5 bg-neutral-50">
        <div v-if="loading" class="d-flex flex-column align-center justify-center pa-8">
          <VProgressCircular indeterminate color="primary" size="48" />
          <span class="mt-3 text-body-2 text-medium-emphasis">Processing...</span>
        </div>



        <form @submit.prevent="handleSubmit" :class="{ 'opacity-50': loading }" class="d-flex flex-column gap-4">
          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" sm="6" md="3">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Entry Mode</label>
                  <VTextField
                    :model-value="mode"
                    readonly
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Trace No</label>
                  <VTextField
                    :model-value="form.entry_no"
                    readonly
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Date (Auto Detect)</label>
                  <VTextField
                    v-model="form.entry_date"
                    type="date"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="3">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Material Document (SAP)</label>
                  <VTextField
                    v-model="form.material_doc"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1 text-uppercase"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" md="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Transfer Material</label>
                  <VSelect
                    v-model="form.id_material"
                    :items="materialOptions"
                    item-title="label"
                    item-value="value"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @update:model-value="onMaterialChange"
                  />
                </VCol>
                <VCol cols="12" md="6">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">
                    Trf Type (<em>Trf ALL</em> only for TRF non-EOB1 to Adjust OUT)
                  </label>
                  <VSelect
                    v-model="form.trf_type"
                    :items="trfTypeOptions"
                    item-title="label"
                    item-value="value"
                    required
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @update:model-value="onTrfTypeChange"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard v-if="showSloc" variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol cols="12" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">
                    Source SLoc <span class="text-caption font-weight-regular text-disabled">(change this for TRF IN)</span>
                  </label>
                  <VSelect
                    v-model="form.source_sloc"
                    :items="sourceTankOptions"
                    item-title="label"
                    item-value="value"
                    :disabled="form.trf_type === 'out'"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @update:model-value="onSourceChange"
                  />
                  <p class="mt-1 text-caption text-medium-emphasis">{{ sourceStockLabel }}</p>
                </VCol>
                <VCol cols="12" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">
                    Transfer SLoc <span class="text-caption font-weight-regular text-disabled">(change this for TRF OUT)</span>
                  </label>
                  <VSelect
                    v-model="form.trf_sloc"
                    :items="destTankOptions"
                    item-title="label"
                    item-value="value"
                    :disabled="form.trf_type === 'in'"
                    rounded="md"
                    color="primary"
                    density="compact"
                    variant="outlined"
                    class="mt-1"
                    @update:model-value="onDestinationChange"
                  />
                  <p class="mt-1 text-caption text-medium-emphasis">{{ destStockLabel }}</p>
                </VCol>
                <VCol cols="12" md="4">
                  <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Trf Qty (MT)</label>
<VTextField
                      v-model.number="form.trf_qty"
                      type="number"
                      step="0.001"
                      min="0"
                      required
                      rounded="md"
                      color="primary"
                      density="compact"
                      variant="outlined"
                      class="mt-1"
                    />
                    <VBtn color="primary" variant="outlined" size="small" @click="fetchTransferQty">Fetch Qty</VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard v-if="showSpecificSlocRow" variant="outlined">
            <VCardText>
              <VRow dense>
                <VCol v-if="showSpecificSourceSloc" cols="12" md="6">
                  <VSelect
                    v-model="selectedSourceTails"
                    label="Specific Source Sloc"
                    :items="specificSourceOptions"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    rounded="md"
                    color="primary"
                    variant="outlined"
                    density="compact"
                  />
                </VCol>
                <VCol v-if="showSpecificDestSloc" cols="12" md="6">
                  <VSelect
                    v-model="selectedDestTails"
                    label="Specific Transfer Sloc"
                    :items="specificDestOptions"
                    item-title="title"
                    item-value="value"
                    multiple
                    chips
                    closable-chips
                    rounded="md"
                    color="primary"
                    variant="outlined"
                    density="compact"
                  />
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <div v-if="showSloc" class="d-flex align-center gap-3">
            <VBtn
              type="submit"
              color="primary"
              prepend-icon="ri-check-line"
              :loading="loading"
            >
              Save Entry
            </VBtn>
          </div>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useTsTransferStore } from '../stores'
import { useToastStore } from '@/stores/toast.js'

const props = defineProps({
  isOpen: { type: Boolean, default: false }
})

const emit = defineEmits(['update:isOpen', 'success'])

const plantSelectionStore = usePlantSelectionStore()
const store = useTsTransferStore()
const toastStore = useToastStore()

const mode = 'ADD'
const loading = ref(false)

const sourceTanks = ref([])
const destTanks = ref([])
const specificSourceTanks = ref([])
const specificDestTanks = ref([])
const selectedSourceTails = ref([])
const selectedDestTails = ref([])
const sourceStockLabel = ref('Stock : N/A')
const destStockLabel = ref('Stock : N/A')

const TANK_AUTO_IDS = [5, 6, 24, 25, 28, 29, 32, 33]

const form = reactive({
  entry_no: '',
  entry_date: new Date().toISOString().split('T')[0],
  id_material: '',
  trf_type: '',
  source_sloc: '',
  trf_sloc: '',
  trf_qty: 0,
  material_doc: '',
  idHead: null
})

const plantId = computed(() => plantSelectionStore.selectedPlantId)

const trfInLabel = computed(() => {
  const name = String(plantSelectionStore.selectedPlantName || 'EOMB').replace('-', ' ')
  return `- Trf IN to ${name} -`
})

const trfOutLabel = computed(() => {
  const name = String(plantSelectionStore.selectedPlantName || 'EOMB').replace('-', ' ')
  return `- Trf OUT from ${name} -`
})

const trfTypeOptions = computed(() => [
  { value: 'in', label: trfInLabel.value },
  { value: 'out', label: trfOutLabel.value },
  { value: 'all', label: '- Trf ALL -' }
])

const materialOptions = computed(() => {
  return (store.activeMaterials || []).map(m => ({
    value: m.id_material,
    label: m.material_code || m.description || m.material
  }))
})

const sourceTankOptions = computed(() => {
  const seen = new Set()
  return (sourceTanks.value || [])
    .map(t => {
      const label = t.tank || t.description || ''
      return {
        value: String(t.id_sloc || t.id_tank),
        label: label
      }
    })
    .filter(item => {
      if (!item.value || !item.label || /^\d+$/.test(item.label) || seen.has(item.label)) {
        return false
      }
      seen.add(item.label)
      return true
    })
})

const destTankOptions = computed(() => {
  const seen = new Set()
  return (destTanks.value || [])
    .map(t => {
      const label = t.tank || t.description || ''
      return {
        value: String(t.id_sloc || t.id_tank),
        label: label
      }
    })
    .filter(item => {
      if (!item.value || !item.label || /^\d+$/.test(item.label) || seen.has(item.label)) {
        return false
      }
      seen.add(item.label)
      return true
    })
})

const showSloc = computed(() => {
  return form.id_material && form.trf_type
})

const showSpecificSourceSloc = computed(() => {
  return showSloc.value && form.source_sloc && (form.trf_type === 'in' || form.trf_type === 'all')
})

const showSpecificDestSloc = computed(() => {
  return showSloc.value && form.trf_sloc && (form.trf_type === 'out' || form.trf_type === 'all')
})

const showSpecificSlocRow = computed(() => {
  return showSpecificSourceSloc.value || showSpecificDestSloc.value
})

const specificSourceOptions = computed(() =>
  (specificSourceTanks.value || []).map(t => ({
    value: String(t.id_sloc || t.id_tank_tail),
    title: String(t.tankName || t.tankNo || t.tf_number || t.description || t.id_sloc || t.id_tank_tail || 'Unknown'),
  }))
)

const specificDestOptions = computed(() =>
  (specificDestTanks.value || []).map(t => ({
    value: String(t.id_sloc || t.id_tank_tail),
    title: String(t.tankName || t.tankNo || t.tf_number || t.description || t.id_sloc || t.id_tank_tail || 'Unknown'),
  }))
)

async function bootstrap() {
  loading.value = true
  try {
    await store.fetchActiveMaterials()
    resetForm()
  } catch (err) {
    toastStore.error('Failed to load form data: ' + err.message)
  }
  loading.value = false
}

function resetForm() {
  form.entry_no = ''
  form.entry_date = new Date().toISOString().split('T')[0]
  form.id_material = ''
  form.trf_type = ''
  form.source_sloc = ''
  form.trf_sloc = ''
  form.trf_qty = 0
  form.material_doc = ''
  form.idHead = null
  sourceTanks.value = []
  destTanks.value = []
  specificSourceTanks.value = []
  specificDestTanks.value = []
  selectedSourceTails.value = []
  selectedDestTails.value = []
  sourceStockLabel.value = 'Stock (MT): N/A'
  destStockLabel.value = 'Stock (MT): N/A'
}

async function onMaterialChange() {
  if (!form.id_material) return

  try {
    form.entry_no = ''

    if (form.trf_type) {
      await populateTanks()
    }
  } catch (err) {
    toastStore.error(err.message)
  }
}

async function onTrfTypeChange() {
  if (!form.trf_type) {
    sourceTanks.value = []
    destTanks.value = []
    specificSourceTanks.value = []
    specificDestTanks.value = []
    form.source_sloc = ''
    form.trf_sloc = ''
    return
  }

  if (!form.id_material) {
    toastStore.error('Select Material first!')
    form.trf_type = ''
    return
  }

  await populateTanks()
}

async function populateTanks() {
  const trfType = form.trf_type
  const idMat = form.id_material
  const currentPlant = plantId.value

  sourceStockLabel.value = 'Stock (MT): N/A'
  destStockLabel.value = 'Stock (MT): N/A'

  try {
    if (trfType === 'in') {
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: true }),
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: false })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    } else if (trfType === 'out') {
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: false }),
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: true })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    } else {
      const [sourceRes, destRes] = await Promise.all([
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: false }),
        store.fetchActiveTanksRundown({ idMaterial: null, id_plant: currentPlant, exclude_plant: false })
      ])
      sourceTanks.value = sourceRes?.data || []
      destTanks.value = destRes?.data || []
    }

    if (trfType === 'out') {
      const wipTank = sourceTanks.value.find(t => String(t.tank || t.description || '').toUpperCase().includes('WIP'))
      if (wipTank) {
        form.source_sloc = String(wipTank.id_sloc || wipTank.id_tank)
      } else if (sourceTanks.value.length > 0) {
        form.source_sloc = String(sourceTanks.value[0].id_sloc || sourceTanks.value[0].id_tank)
      }
      await onSourceChange()
    } else {
      form.source_sloc = ''
    }

    if (trfType === 'in') {
      const wipTank = destTanks.value.find(t => String(t.tank || t.description || '').toUpperCase().includes('WIP'))
      if (wipTank) {
        form.trf_sloc = String(wipTank.id_sloc || wipTank.id_tank)
      } else if (destTanks.value.length > 0) {
        form.trf_sloc = String(destTanks.value[0].id_sloc || destTanks.value[0].id_tank)
      }
      await onDestinationChange()
    } else {
      form.trf_sloc = ''
    }
  } catch (err) {
    toastStore.error(err.message)
  }
}

async function onSourceChange() {
  if (!form.source_sloc) {
    specificSourceTanks.value = []
    sourceStockLabel.value = 'Stock (MT): N/A'
    return
  }
  try {
    await store.fetchActiveSpecificTanksRundown({ sloc: form.source_sloc })
    specificSourceTanks.value = store.activeSpecificTanks

    await updateStock('source')
    await updateEntryNoFromSloc()
  } catch (err) {
    specificSourceTanks.value = []
  }
}

async function onDestinationChange() {
  if (!form.trf_sloc) {
    specificDestTanks.value = []
    destStockLabel.value = 'Stock (MT): N/A'
    return
  }
  try {
    await store.fetchActiveSpecificTanksRundown({ sloc: form.trf_sloc })
    specificDestTanks.value = store.activeSpecificTanks

    await updateStock('dest')
    await updateEntryNoFromSloc()
  } catch (err) {
    specificDestTanks.value = []
  }
}

async function updateEntryNoFromSloc() {
  if (!form.id_material) return
  if (!form.trf_type) return

  if (form.trf_type === 'all' && (!form.source_sloc || !form.trf_sloc)) {
    form.entry_no = ''
    return
  }

  if (!form.source_sloc) {
    form.entry_no = ''
    return
  }

  let activePlantId = plantId.value
  const t = sourceTanks.value.find(x => String(x.id_sloc || x.id_tank) === String(form.source_sloc))
  if (t && t.id_plant) activePlantId = t.id_plant

  if (activePlantId && activePlantId !== 0) {
    try {
      const entryResponse = await store.fetchNewEntryNo({
        id_plant: activePlantId,
        id_material: form.id_material
      })
      if (entryResponse?.data?.[0]?.entryNo) {
        form.entry_no = entryResponse.data[0].entryNo
      }
    } catch (e) {}
  }
}

async function fetchTransferQty() {
  if (!form.id_material) {
    toastStore.error('Select material first')
    return
  }
  try {
    const res = await store.fetchTransferQty({
      idMaterial: form.id_material,
      idPlant: plantId.value
    })
    if (res?.status === 1) {
      form.trf_qty = res.data?.qty || 0
    } else {
      toastStore.error(res?.message || 'Fetch failed')
    }
  } catch (err) {
    toastStore.error(err.message || 'Fetch failed')
  }
}

async function updateStock(type) {
  const idTank = type === 'source' ? form.source_sloc : form.trf_sloc
  if (!form.id_material || !idTank) return

  const isAutoInOut = (form.trf_type === 'in' && type === 'source') || 
                      (form.trf_type === 'out' && type === 'dest')

  if (isAutoInOut) {
    try {
      await store.fetchSupplierCode({
        idMaterial: form.id_material,
        idTank: idTank,
        id_plant: plantId.value
      })
      const code = store.supplierCode?.supplierCode || 'N/A'
      const label = `AUTO IN/OUT (${code})`
      if (type === 'source') {
        sourceStockLabel.value = label
      } else {
        destStockLabel.value = label
      }
    } catch (err) {
      const label = `AUTO IN/OUT (Error)`
      if (type === 'source') {
        sourceStockLabel.value = label
      } else {
        destStockLabel.value = label
      }
    }
  } else {
    try {
      const response = await store.fetchTotalStockMaterial({
        idMaterial: form.id_material,
        idTank: idTank,
        id_plant: plantId.value
      })
      const total = store.totalStock || 0
      const label = `Stock (MT): ${total}`

      if (type === 'source') {
        sourceStockLabel.value = label
      } else {
        destStockLabel.value = label
      }
    } catch (err) {
      if (type === 'source') {
        sourceStockLabel.value = 'Stock (MT): N/A'
      } else {
        destStockLabel.value = 'Stock (MT): N/A'
      }
    }
  }
}

async function handleSubmit() {
  if (!form.trf_qty || parseFloat(form.trf_qty) <= 0) {
    toastStore.error('Entry Qty must be greater than 0')
    return
  }

  loading.value = true
  try {
    const payload = {
      entry_no: form.entry_no,
      entry_date: form.entry_date,
      id_material: form.id_material,
      trf_type: form.trf_type,
      trf_qty: form.trf_qty,
      source_sloc: form.source_sloc,
      trf_sloc: form.trf_sloc,
      source_sloc_no: selectedSourceTails.value,
      trf_sloc_no: selectedDestTails.value,
      material_doc: form.material_doc,
      id_plant: plantId.value
    }
    const response = await store.submitTransferEntry(payload)

    if (response?.status === 1) {
      emit('success')
      closeModal()
    } else {
      toastStore.error(response?.message || 'Transfer failed')
    }
  } catch (err) {
    toastStore.error(err.response?.data?.message || err.message || 'Error')
  }
  loading.value = false
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (val) => {
  if (!val && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (val) {
    bootstrap()
  }
})
</script>
