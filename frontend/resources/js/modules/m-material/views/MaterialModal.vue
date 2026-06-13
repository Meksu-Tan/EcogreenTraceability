<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material' : 'Add Material'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="mat-code"
            v-model="form.code"
            label="Material Code"
            placeholder="RM-001"
            :error-messages="errors.code"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="mat-code-noneudr"
            v-model="form.code_noneudr"
            label="Non-EUDR Code"
            placeholder="Optional"
            density="compact"
            variant="outlined"
            hide-details="auto"
          />
        </VCol>
      </VRow>

      <VTextField
        id="mat-description"
        v-model="form.description"
        label="Description"
        placeholder="Material Name"
        :error-messages="errors.description"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VRow dense>
        <VCol cols="12" sm="6">
          <VSelect
            id="mat-type"
            v-model="form.type"
            :items="typeOptions"
            label="Type"
            placeholder="Select Type"
            :error-messages="errors.type"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="mat-yield"
            v-model="form.yield"
            type="number"
            label="Yield (%)"
            density="compact"
            variant="outlined"
            hide-details="auto"
            min="0"
            max="100"
            step="0.1"
          />
        </VCol>
      </VRow>

      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="mat-qtf-feed"
            v-model="form.qtf_feed"
            label="QTF Feed"
            density="compact"
            variant="outlined"
            hide-details="auto"
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="mat-qtf-rundown"
            v-model="form.qtf_rundown"
            label="QTF Rundown"
            density="compact"
            variant="outlined"
            hide-details="auto"
          />
        </VCol>
      </VRow>

      <VTextField
        id="mat-code-supplier"
        v-model="form.code_matl_supplier"
        label="Supplier Material Code"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />

      <VCheckbox
        id="mat-status-packaging"
        v-model="form.status_packaging"
        true-value="1"
        false-value="0"
        label="Active for Packaging"
        density="comfortable"
        color="primary"
        hide-details
      />
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editData:   { type: Object, default: null },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'submit'])

const isEdit = computed(() => !!props.editData)

const typeOptions = [
  { value: 'WIP', title: 'WIP' },
  { value: 'RM', title: 'Raw Material' },
  { value: 'FG', title: 'Finished Good' }
]

const form = reactive({
  code: '', code_noneudr: '', description: '', type: '',
  yield: 100, qtf_feed: '', qtf_rundown: '',
  code_matl_supplier: '', status_packaging: '0',
})

const errors = reactive({ code: '', description: '', type: '' })

watch(() => props.editData, (val) => {
  if (val) {
    Object.assign(form, {
      code: val.code || '', code_noneudr: val.code_noneudr || '',
      description: val.description || '', type: val.type || '',
      yield: val.yield || 100, qtf_feed: val.qtf_feed || '',
      qtf_rundown: val.qtf_rundown || '',
      code_matl_supplier: val.code_matl_supplier || '',
      status_packaging: String(val.status_packaging || '0'),
    })
  } else {
    Object.assign(form, { code: '', code_noneudr: '', description: '', type: '', yield: 100, qtf_feed: '', qtf_rundown: '', code_matl_supplier: '', status_packaging: '0' })
  }
  Object.assign(errors, { code: '', description: '', type: '' })
})

function validate() {
  errors.code = errors.description = errors.type = ''
  let ok = true
  if (!form.code)        { errors.code = 'Code is required'; ok = false }
  if (!form.description) { errors.description = 'Description is required'; ok = false }
  if (!form.type)        { errors.type = 'Type must be selected'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>