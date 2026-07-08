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
        id="warehouse-code"
        v-model="form.code"
        type="text"
        label="Code"
        placeholder="e.g. WH01"
        :error-messages="errors.code"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="warehouse-description"
        v-model="form.description"
        type="text"
        label="Description"
        placeholder="e.g. Main Warehouse"
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

const props = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean })
const emit = defineEmits(['update:modelValue', 'submit'])

const isEdit = computed(() => !!props.editData)

const form = reactive({ code: '', description: '' })
const errors = reactive({ code: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val
    ? { code: val.code || '', description: val.description || '' }
    : { code: '', description: '' }
  )
  Object.assign(errors, { code: '', description: '' })
})

function validate() {
  let ok = true
  Object.assign(errors, { code: '', description: '' })
  if (!form.code) { errors.code = 'Required'; ok = false }
  if (!form.description) { errors.description = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
