<template>
  <VDialog
    :model-value="isOpen"
    max-width="960"
    scrollable
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">{{ form.mode === 'EDIT' ? 'Edit Raw Material Entry' : 'Raw Material Entry' }}</span>
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
        <VAlert
          v-if="initError && !initLoading"
          type="error"
          variant="tonal"
          class="mb-4"
          density="comfortable"
        >
          <div class="d-flex flex-wrap align-center justify-space-between ga-2">
            <span>{{ initError }}</span>
            <VBtn color="error" variant="flat" size="small" @click="bootstrap">Try again</VBtn>
          </div>
        </VAlert>

        <form @submit.prevent="handleSubmit" class="d-flex flex-column ga-4">
          <VCard variant="outlined">
            <VCardTitle class="d-flex flex-wrap align-end justify-space-between border-b pa-4 ga-3">
              <span class="text-body-1 font-weight-bold">Entry details</span>
              <p class="text-caption text-primary ma-0">Do not change material after adding supplier.</p>
            </VCardTitle>
            <VCardText class="pt-4">
              <VRow dense>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.mode"
                    label="Mode"
                    readonly
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.rm_number"
                    :label="initLoading && !form.rm_number ? 'Generating entry number...' : 'Entry number (auto)'"
                    :loading="initLoading && !form.rm_number"
                    readonly
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.entry_date"
                    label="Date"
                    type="date"
                    required
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" sm="6" md="4">
                  <VSelect
                    v-model="form.tf_number"
                    label="Sloc"
                    :items="tankOptions"
                    item-title="label"
                    item-value="value"
                    :loading="initLoading"
                    required
                    density="compact"
                    variant="outlined"
                    @update:model-value="onTankChange"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.material_document"
                    label="Material doc (SAP)"
                    density="compact"
                    variant="outlined"
                    class="text-uppercase"
                  />
                </VCol>
                <VCol cols="12" sm="6" md="4">
                  <VTextField
                    v-model="form.po_so"
                    label="Purchase order (PO)"
                    density="compact"
                    variant="outlined"
                    class="text-uppercase"
                  />
                </VCol>
              </VRow>

              <VRow dense class="mt-2">
                <VCol cols="12" md="7">
                  <VSelect
                    v-model="form.id_material"
                    label="Material"
                    :items="materialOptions"
                    item-title="label"
                    item-value="value"
                    :loading="initLoading"
                    required
                    density="compact"
                    variant="outlined"
                  />
                </VCol>
                <VCol cols="12" md="5">
                  <VSelect
                    v-model="form.id_sloc"
                    label="Sub-Sloc"
                    :items="tankDetails"
                    item-title="tf_number"
                    item-value="id_sloc"
                    multiple
                    chips
                    closable-chips
                    variant="outlined"
                    density="compact"
                    :disabled="!form.tf_number"
                    placeholder="Select Sloc first"
                  />
                </VCol>
              </VRow>

              <VRow class="mt-2 border-t pt-4" dense>
                <VCol cols="12" class="d-flex flex-wrap align-center justify-space-between ga-3">
                  <div class="d-flex flex-wrap ga-2">
                    <VBtn
                      color="secondary"
                      prepend-icon="ri-user-add-line"
                      @click="isSupplierModalOpen = true"
                    >
                      Supplier &amp; Qty
                    </VBtn>
                    <VBtn
                      type="button"
                      color="primary"
                      :disabled="!canSubmit || loading"
                      @click="handleSubmit"
                    >
                      {{ form.mode === 'EDIT' ? 'Update Entry' : 'Save Entry' }}
                    </VBtn>
                  </div>
                  <div class="d-flex align-center ga-3">
                    <span class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Total (MT)</span>
                    <VTextField
                      :model-value="totalQty"
                      readonly
                      density="compact"
                      variant="outlined"
                      style="width:144px"
                      class="text-right"
                    />
                  </div>
                </VCol>
              </VRow>
            </VCardText>
          </VCard>

          <VCard variant="outlined">
            <VCardTitle class="bg-neutral-50 text-caption font-weight-bold text-uppercase pa-3">
              Supplier list
            </VCardTitle>
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr class="bg-neutral-50">
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Supplier</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Manufacturer</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Batch SAP</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Qty (MT)</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:80px">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="supplierList.length === 0">
                  <td colspan="7" class="text-center text-disabled py-6 text-body-2">
                    No supplier yet — use "Supplier &amp; Qty" button.
                  </td>
                </tr>
                <tr v-for="(sup, index) in supplierList" :key="sup.id">
                  <td class="text-center text-caption text-medium-emphasis">{{ index + 1 }}</td>
                  <td class="text-caption">{{ sup.material }}</td>
                  <td class="text-caption">{{ sup.supplier }}</td>
                  <td class="text-caption">{{ sup.manufacturer }}</td>
                  <td class="text-caption font-mono">{{ sup.batch_sap }}</td>
                  <td class="text-right font-weight-medium text-caption font-mono">{{ sup.qty }}</td>
                  <td class="text-center">
                    <VBtn
                      icon="ri-delete-bin-line"
                      size="x-small"
                      color="error"
                      variant="tonal"
                      @click="removeSupplier(sup.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCard>
        </form>
      </VCardText>

      <VDivider />

      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="closeModal">Close</VBtn>
        <VBtn
          color="primary"
          prepend-icon="ri-save-line"
          :loading="loading"
          :disabled="!canSubmit"
          @click="handleSubmit"
        >
          {{ loading ? 'Saving...' : (form.mode === 'EDIT' ? 'Update RM Entry' : 'Save RM Entry') }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>

  <VDialog v-model="isSupplierModalOpen" max-width="500">
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Add supplier &amp; qty</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="isSupplierModalOpen = false" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div class="d-flex flex-column ga-3">
          <VSelect
            v-model="supplierForm.id_supplier"
            label="Supplier"
            :items="supplierOptions"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
            @update:model-value="onSupplierChange"
          />
          <VCombobox
            v-model="supplierForm.id_manufacturer"
            label="Manufacturer"
            :items="manufacturerOptions"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
            placeholder="Select or type manufacturer"
            clearable
            :return-object="false"
          />
          <VRow dense>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="supplierForm.batch_sap"
                label="Batch SAP (auto)"
                readonly
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" sm="6">
              <VTextField
                v-model="supplierForm.qty"
                label="Qty (MT)"
                type="number"
                step="0.001"
                placeholder="0.000"
                density="compact"
                variant="outlined"
                class="text-right"
              />
            </VCol>
          </VRow>
          <div class="d-flex flex-row-reverse ga-2 pt-2">
            <VBtn variant="outlined" color="medium-emphasis" @click="isSupplierModalOpen = false">Cancel</VBtn>
            <VBtn color="primary" :disabled="!canAddSupplier" @click="addSupplier">Add</VBtn>
          </div>
        </div>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTsRawRmEntryStore } from '@/modules/ts-raw/stores'
import { usePlantSelectionStore } from '@/stores/plant.js'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const confirmStore = useConfirmStore()

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  editId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'saved', 'update:isOpen'])

const store = useTsRawRmEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const toastStore = useToastStore()

const isSupplierModalOpen = ref(false)
const initLoading = ref(false)
const initError = ref(null)

const form = ref({
  mode: 'ADD',
  entry_date: new Date().toISOString().split('T')[0],
  rm_number: '',
  id_material: '',
  tf_number: '',
  id_sloc: [],
  material_document: '',
  po_so: '',
  total_qty: 0
})

const supplierForm = ref({
  id_supplier: '',
  id_manufacturer: null,
  batch_sap: '',
  qty: ''
})

const loading = computed(() => store.loading)
const tanks = computed(() => store.tanks)
const tankDetails = computed(() => store.tankDetails)
const materials = computed(() => store.materials)
const suppliers = computed(() => store.suppliers)
const supplierList = computed(() => store.supplierList)
const totalQty = computed(() => store.totalQty)

const tankOptions = computed(() => {
  const seen = new Set()
  return (tanks.value || [])
    .filter(t => {
      const label = t.tank || t.description || ''
      if (!label || seen.has(label)) return false
      seen.add(label)
      return true
    })
    .map(t => ({ value: t.id_sloc ?? t.tf_number, label: t.tank || t.description || '' }))
})

const materialOptions = computed(() => {
  return (materials.value || []).map(m => ({ value: m.id_material, label: m.material }))
})

const supplierOptions = computed(() => {
  return (suppliers.value || []).map(s => ({ value: s.id, label: s.text }))
})

const manufacturerOptions = computed(() => {
  return (store.manufacturers || []).map(m => ({ value: m.id_manufacturer, label: m.manufacturer }))
})

const canAddSupplier = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         supplierForm.value.id_supplier &&
         supplierForm.value.batch_sap &&
         supplierForm.value.qty &&
         parseFloat(supplierForm.value.qty) > 0 &&
         form.value.id_material
})

const canSubmit = computed(() => {
  const qtyStr = totalQty.value ? String(totalQty.value).replace(/,/g, '') : '0'
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_date &&
         form.value.rm_number &&
         form.value.id_material &&
         form.value.tf_number &&
         form.value.id_sloc.length > 0 &&
         supplierList.value.length > 0 &&
         parseFloat(qtyStr) > 0
})

async function bootstrap() {
  initLoading.value = true
  initError.value = null
  store.resetForm()

  form.value = {
    mode: 'ADD',
    entry_date: new Date().toISOString().split('T')[0],
    rm_number: '',
    id_material: '',
    tf_number: '',
    id_sloc: [],
    material_document: '',
    po_so: '',
    total_qty: 0
  }
  supplierForm.value = { id_supplier: '', id_manufacturer: null, batch_sap: '', qty: '' }
  isSupplierModalOpen.value = false

  try {
    const params = { id_plant: plantSelectionStore.selectedPlantId }

    await Promise.all([
      store.fetchTanks(params, true),
      store.fetchMaterials()
    ])

    if (props.editId) {
      const res = await store.prepareEdit(props.editId)
      const editData = res.data || res

      let parsedSloc = editData.id_sloc || []
      if (typeof parsedSloc === 'string') {
        try {
          parsedSloc = JSON.parse(parsedSloc)
        } catch (e) {
          parsedSloc = [parsedSloc]
        }
      } else if (!Array.isArray(parsedSloc)) {
        parsedSloc = [parsedSloc]
      }

      form.value = {
        mode: 'EDIT',
        entry_date: editData.entry_date,
        rm_number: String(editData.rm_number),
        id_material: Number(editData.id_material),
        tf_number: editData.sloc_desc || '',
        id_sloc: parsedSloc,
        material_document: editData.material_document || '',
        po_so: editData.po_so || '',
        total_qty: parseFloat(editData.total_qty)
      }

      store.rmNumber = form.value.rm_number

      if (editData.sloc_desc) {
        const matchedTank = (tanks.value || []).find(t => t.tank === editData.sloc_desc || t.description === editData.sloc_desc)
        const slocIdForEdit = matchedTank?.id_sloc ?? editData.sloc_desc
        form.value.tf_number = slocIdForEdit
        await store.fetchTankDetails(slocIdForEdit, plantSelectionStore.selectedPlantId)
      }
    } else {
      await store.generateRmNumber(params)
      form.value.rm_number = store.rmNumber || ''
      form.value.mode = 'ADD'
      if (!form.value.rm_number) {
        initError.value = 'RM Number not generated. Check permissions, user id_plant, and database connection (MySQL).'
      }
    }
  } catch (error) {
    toastStore.error('Initialization error:', error)
    initError.value =
      error.response?.data?.message ||
      error.message ||
      'Failed to load form data. Ensure Laravel API (Sanctum) and MySQL are accessible from frontend.'
  } finally {
    initLoading.value = false
  }

  if (form.value.rm_number && !initError.value) {
    try {
      await store.fetchSupplierList(form.value.rm_number)
      await store.fetchTotalQty(form.value.rm_number)
    } catch (error) {
      toastStore.error('Supplier list load:', error)
      initError.value =
        error.response?.data?.message ||
        error.message ||
        'Failed to load temporary supplier row.'
    }
  }
}

async function onTankChange() {
  form.value.id_sloc = []
  if (form.value.tf_number) {
    await store.fetchTankDetails(form.value.tf_number, plantSelectionStore.selectedPlantId)

    if (store.tankDetails.length === 1) {
      form.value.id_sloc = [store.tankDetails[0].id_sloc]
    }

    if (!plantSelectionStore.selectedPlantId || plantSelectionStore.selectedPlantId == 0) {
      await store.generateRmNumber({
        id_plant: 0,
        tank_desc: form.value.tf_number
      })
      form.value.rm_number = store.rmNumber || ''
    }
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

  const selectedTankObj = tanks.value.find(t => (t.id_sloc ?? t.tf_number) == form.value.tf_number || t.tank === form.value.tf_number)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    await store.addSupplier({
      entry_no: form.value.rm_number,
      id_supplier: supplierForm.value.id_supplier,
      id_manufacturer: supplierForm.value.id_manufacturer,
      id_material: form.value.id_material,
      qty: parseFloat(supplierForm.value.qty),
      batch_sap: supplierForm.value.batch_sap,
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId
    })

    supplierForm.value = {
      id_supplier: '',
      id_manufacturer: null,
      batch_sap: '',
      qty: ''
    }
    isSupplierModalOpen.value = false
  } catch (error) {
    toastStore.error('Add supplier error:', error)
  }
}

async function removeSupplier(id) {
  const isConfirmed = await confirmStore.show({ message: 'Are you sure you want to remove this supplier?' })
  if (isConfirmed) {
    await store.deleteSupplier(id, form.value.rm_number)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return

  const selectedTankObj = tanks.value.find(t => (t.id_sloc ?? t.tf_number) == form.value.tf_number || t.tank === form.value.tf_number)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    const data = {
      ...form.value,
      id_sloc: form.value.id_sloc,
      tf_number: form.value.id_sloc,
      id_sloc_tail: null,
      id_sloc_tail: null,
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId,
      total_qty: parseFloat(String(totalQty.value ?? '0').replace(/,/g, ''), 10)
    }

    if (form.value.mode === 'EDIT') {
      await store.updateEntry(props.editId, data)
    } else {
      await store.createEntry(data)
    }
    emit('saved')
    closeModal()
  } catch (error) {
    toastStore.error('Submit error:', error)
    const errorMsg = error.response?.data?.message || error.message || 'Failed to save RM Entry'
    toastStore.error(errorMsg)
    if (form.value.rm_number) {
      try {
        await store.clearTempList(form.value.rm_number)
      } catch (e) {
        toastStore.error('Failed to clear temp list on submit error:', e)
      }
    }
    await bootstrap()
  }
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
  emit('close')
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      void bootstrap()
    } else {
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur()
      }
      if (form.value.rm_number) {
        void store.clearTempList(form.value.rm_number).catch(e => toastStore.error('Failed to clear temp list'))
      }
      initLoading.value = false
      initError.value = null
      isSupplierModalOpen.value = false
    }
  },
  { flush: 'post' }
)

watch(isSupplierModalOpen, async (open) => {
  if (!open) return
  try {
    await Promise.all([
      store.suppliers.length === 0 ? store.searchSuppliers('') : Promise.resolve(),
      store.manufacturers.length === 0 ? store.fetchManufacturers() : Promise.resolve()
    ])
  } catch (e) {
    toastStore.error('Failed to load suppliers or manufacturers')
  }
})
</script>
