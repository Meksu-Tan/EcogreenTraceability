<template>
  <VDialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="480px" persistent>
    <VCard rounded="lg">
      <VCardTitle class="pa-5 pb-3 d-flex align-center justify-space-between">
        <span class="text-h6 font-weight-bold">SELECT SPECIFIC SLOC</span>
        <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="close" />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VAlert v-if="submitError" type="error" density="compact" class="mb-4">{{ submitError }}</VAlert>
        <VForm ref="formRef" v-model="isValid" @submit.prevent="save">
          <VRow>
            <!-- Main Sloc Display -->
            <VCol cols="12">
              <VTextField
                label="Main Sloc"
                v-model="mainSlocLabel"
                readonly
                variant="outlined"
                density="compact"
              />
            </VCol>

            <!-- Specific Sub Tanks -->
            <VCol cols="12">
              <VSelect
                v-model="form.idTankTail"
                label="Specific Tank No"
                :items="store.specificTanks"
                item-title="tankNo"
                item-value="id_tank_tail"
                multiple
                chips
                variant="outlined"
                density="compact"
                required
                :rules="[v => !!v && v.length > 0 || 'Please select at least one sub tank']"
              />
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="close">Cancel</VBtn>
        <VBtn color="primary" prepend-icon="ri-save-line" :disabled="!isValid" :loading="submitting" @click="save">Save</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { usePackageEntryStore } from '../stores/usePackageEntryStore'

const props = defineProps({
  modelValue: { type: Boolean, required: true },
  row: { type: Object, default: null }
})
const emit = defineEmits(['update:modelValue', 'saved'])

const store = usePackageEntryStore()
const formRef = ref(null)
const isValid = ref(false)
const submitting = ref(false)
const submitError = ref('')
const mainSlocLabel = ref('')

const form = reactive({
  id: null,
  idTankTail: []
})

watch(() => props.modelValue, async (newVal) => {
  if (newVal && props.row) {
    form.id = props.row.id_whx_head
    mainSlocLabel.value = props.row.sloc || ''

    // Parse current subtanks - read from id_sloc (new column) or id_tank (legacy fallback)
    let currentTails = []
    const rawTails = props.row.id_sloc || props.row.id_tank || '[]'
    if (rawTails) {
      try {
        currentTails = typeof rawTails === 'string'
          ? JSON.parse(rawTails)
          : rawTails
      } catch (e) {
        currentTails = []
      }
    }
    form.idTankTail = currentTails.map(String)

    // Load specific tanks for the parent sloc
    const parentSloc = props.row.id_sloc || props.row.id_tank
    if (parentSloc) {
      await store.fetchSpecificTanks(parentSloc)
    }
  }
})

function close() {
  emit('update:modelValue', false)
}

async function save() {
  if (!isValid.value) return
  submitting.value = true
  try {
    const res = await store.updateSubTank(form.id, form.idTankTail)
    if (res.response == 1) {
      emit('saved')
      close()
    }
  } catch (err) {
    submitError.value = err?.response?.data?.message || 'An error occurred'
  } finally {
    submitting.value = false
  }
}
</script>
