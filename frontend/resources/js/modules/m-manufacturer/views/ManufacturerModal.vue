<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Manufacturer' : 'Add Manufacturer'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        id="mfg-description"
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
const form   = reactive({ description: '' })
const errors = reactive({ description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { description: val.description||'' } : { description: '' })
  Object.assign(errors, { description: '' })
})

function validate() {
  errors.description = ''
  let ok = true
  if (!form.description) { errors.description = 'Description is required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>