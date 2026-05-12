<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Storage Detail' : 'Tambah Storage Detail'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="form-group">
      <label class="form-label">TF Number <span style="color:red">*</span></label>
      <input id="detail-tf-number" v-model="form.tf_number" type="text" class="form-control" :class="{'is-invalid': errors.tf_number}" />
      <div v-if="errors.tf_number" class="form-error">{{ errors.tf_number }}</div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

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
  if (!form.tf_number) { errors.tf_number = 'TF Number wajib diisi'; return false }
  return true
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form, id_tank: props.tankId })
}
</script>
