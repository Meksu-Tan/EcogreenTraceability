<template>
  <VDialog
    :model-value="isOpen"
    max-width="440"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Edit Specific Sloc</span>
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
        <p class="mb-4 text-body-2">Main SLoc: <strong>{{ props.mainSloc }}</strong></p>
        
        <form @submit.prevent="handleSave" class="d-flex flex-column gap-4">
          <div>
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Specific Storage Location</label>
            <div v-if="loadingTanks" class="d-flex align-center justify-center py-4">
              <VProgressCircular indeterminate color="primary" size="24" />
              <span class="ms-2 text-caption text-medium-emphasis">Loading...</span>
            </div>
            <div v-else class="mt-2 pa-2 border rounded overflow-y-auto" style="max-height: 192px;">
              <VCheckbox
                v-for="tank in availableTanks"
                :key="tank.id_tank_tail"
                v-model="selectedTails"
                :value="String(tank.id_tank_tail)"
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
