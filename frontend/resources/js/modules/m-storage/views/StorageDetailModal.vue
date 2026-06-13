<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Storage Detail' : 'Add Storage Detail'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        id="detail-tf-number"
        v-model="form.tf_number"
        label="TF Number"
        :error-messages="errors.tf_number"
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

const props  = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean, tankId: Number })
const emit   = defineEmits(['update:modelValue', 'submit'])
const isEdit = computed(() => !!props.editData)
const form   = reactive({ tf_number: '' })
const errors = reactive({ tf_number: '' })

watch(() => props.editData, (val) => {
  form.tf_number   = val?.tf_number || ''
  errors.tf_number = ''
})

function validate() {
  errors.tf_number = ''
  if (!form.tf_number) { errors.tf_number = 'TF Number is required'; return false }
  return true
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form, id_tank: props.tankId })
}
</script>