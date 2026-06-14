<template>
  <VDialog
    :model-value="isOpen"
    max-width="440"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Assign Specific Sloc (Sub Tank)</span>
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
        <p class="mb-4 text-body-2">Main SLoc: <strong>{{ mainSloc }}</strong></p>
        
        <form @submit.prevent="handleSave" class="d-flex flex-column gap-4">
          <div>
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Select Specific Sloc</label>
            <div v-if="loadingTanks" class="d-flex align-center justify-center py-4">
              <VProgressCircular indeterminate color="primary" size="24" />
              <span class="ms-2 text-caption text-medium-emphasis">Loading...</span>
            </div>
            <div v-else class="mt-2 pa-2 border rounded overflow-y-auto" style="max-height: 192px;">
              <VCheckbox
                v-for="tank in availableTanks"
                :key="tank.id_sloc_tail"
                v-model="selectedTails"
                :value="String(tank.id_sloc_tail)"
                :label="tank.tankNo || tank.tf_number"
                density="compact"
                hide-details
                color="primary"
              />
              <p v-if="availableTanks.length === 0" class="text-caption text-disabled text-center py-2">No specific sloc available</p>
            </div>
          </div>

          <VAlert
            v-if="errorMsg"
            type="error"
            variant="tonal"
            density="comfortable"
          >
            {{ errorMsg }}
          </VAlert>

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
              :loading="loading"
            >
              Save
            </VBtn>
          </div>
        </form>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useTsTransferStore } from '../stores'
import transferApi from '../services/index.js'

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
  selectedTails.value = props.idTankTail.map(String)

  try {
    const response = await transferApi.getSpecificTanksRundown({ sloc: props.idTank })
    availableTanks.value = response?.data || []

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
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('update:isOpen', false)
}

watch(() => props.isOpen, (open) => {
  if (!open && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  if (open) bootstrap()
})
</script>
