<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Warehouse' : 'Add Warehouse'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        id="wh-id-batch"
        v-model="form.id_batch"
        label="ID Batch"
        :error-messages="errors.id_batch"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="wh-code"
        v-model="form.code"
        label="Code"
        :error-messages="errors.code"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="wh-description"
        v-model="form.description"
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
const form   = reactive({ id_batch: '', code: '', description: '' })
const errors = reactive({ id_batch: '', code: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { id_batch: val.id_batch||'', code: val.code||'', description: val.description||'' } : { id_batch: '', code: '', description: '' })
  Object.assign(errors, { id_batch: '', code: '', description: '' })
})

function validate() {
  let ok = true
  Object.assign(errors, { id_batch: '', code: '', description: '' })
  if (!form.id_batch)    { errors.id_batch = 'ID Batch is required'; ok = false }
  if (!form.code)        { errors.code = 'Code is required'; ok = false }
  if (!form.description) { errors.description = 'Description is required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>