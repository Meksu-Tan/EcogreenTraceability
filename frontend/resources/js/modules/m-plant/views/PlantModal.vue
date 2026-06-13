<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Plant' : 'Add Plant'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        id="plant-code"
        v-model="form.code"
        type="text"
        label="Internal Code"
        placeholder="e.g. P01"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />

      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="plant-code2"
            v-model="form.code_2"
            type="text"
            label="Plant Code"
            placeholder="e.g. 1001"
            :error-messages="errors.code_2"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="plant-code3"
            v-model="form.code_3"
            type="text"
            label="Plant Name"
            placeholder="e.g. EOMB"
            :error-messages="errors.code_3"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
      </VRow>

      <VTextField
        id="plant-description"
        v-model="form.description"
        type="text"
        label="Description"
        :error-messages="errors.description"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

const props  = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean })
const emit   = defineEmits(['update:modelValue', 'submit'])
const isEdit = computed(() => !!props.editData)
const form   = reactive({ code: '', code_2: '', code_3: '', description: '' })
const errors = reactive({ code_2: '', code_3: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { code: val.code||'', code_2: val.code_2||'', code_3: val.code_3||'', description: val.description||'' } : { code: '', code_2: '', code_3: '', description: '' })
  Object.assign(errors, { code_2: '', code_3: '', description: '' })
})

function validate() {
  let ok = true
  Object.assign(errors, { code_2: '', code_3: '', description: '' })
  if (!form.code_2)       { errors.code_2 = 'Required'; ok = false }
  if (!form.code_3)       { errors.code_3 = 'Required'; ok = false }
  if (!form.description)  { errors.description = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>