<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Supplier' : 'Add Supplier'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        id="sup-code"
        v-model="form.code"
        label="Code"
        :error-messages="errors.code"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="sup-description"
        v-model="form.description"
        label="Description"
        :error-messages="errors.description"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="sup-type"
        v-model="form.type"
        label="Type (Tank ID/Sloc)"
        placeholder="Tank ID or leave empty"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />

      <VTextField
        id="sup-batch-code"
        v-model="form.batch_code"
        label="Batch Code"
        density="compact"
        variant="outlined"
        hide-details="auto"
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
const form   = reactive({ code: '', description: '', type: '', batch_code: '' })
const errors = reactive({ code: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { code: val.code||'', description: val.description||'', type: val.type||'', batch_code: val.batch_code||'' } : { code: '', description: '', type: '', batch_code: '' })
  Object.assign(errors, { code: '', description: '' })
})

function validate() {
  errors.code = errors.description = ''
  let ok = true
  if (!form.code)        { errors.code = 'Code is required'; ok = false }
  if (!form.description) { errors.description = 'Description is required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>