<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Warehouse' : 'Tambah Warehouse'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="form-group">
      <label class="form-label">ID Batch <span style="color:red">*</span></label>
      <input id="wh-id-batch" v-model="form.id_batch" type="text" class="form-control" :class="{'is-invalid': errors.id_batch}" />
      <div v-if="errors.id_batch" class="form-error">{{ errors.id_batch }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Kode <span style="color:red">*</span></label>
      <input id="wh-code" v-model="form.code" type="text" class="form-control" :class="{'is-invalid': errors.code}" />
      <div v-if="errors.code" class="form-error">{{ errors.code }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi <span style="color:red">*</span></label>
      <input id="wh-description" v-model="form.description" type="text" class="form-control" :class="{'is-invalid': errors.description}" />
      <div v-if="errors.description" class="form-error">{{ errors.description }}</div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

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
  if (!form.id_batch)    { errors.id_batch = 'ID Batch wajib diisi'; ok = false }
  if (!form.code)        { errors.code = 'Kode wajib diisi'; ok = false }
  if (!form.description) { errors.description = 'Deskripsi wajib diisi'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
