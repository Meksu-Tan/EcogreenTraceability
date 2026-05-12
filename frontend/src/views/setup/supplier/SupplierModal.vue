<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Supplier' : 'Tambah Supplier'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="form-group">
      <label class="form-label">Kode <span style="color:red">*</span></label>
      <input id="sup-code" v-model="form.code" type="text" class="form-control" :class="{'is-invalid': errors.code}" />
      <div v-if="errors.code" class="form-error">{{ errors.code }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi <span style="color:red">*</span></label>
      <input id="sup-description" v-model="form.description" type="text" class="form-control" :class="{'is-invalid': errors.description}" />
      <div v-if="errors.description" class="form-error">{{ errors.description }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Tipe (ID Tank/Sloc)</label>
      <input id="sup-type" v-model="form.type" type="text" class="form-control" placeholder="ID Tank atau kosongkan" />
    </div>
    <div class="form-group">
      <label class="form-label">Batch Code</label>
      <input id="sup-batch-code" v-model="form.batch_code" type="text" class="form-control" />
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

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
  if (!form.code)        { errors.code = 'Kode wajib diisi'; ok = false }
  if (!form.description) { errors.description = 'Deskripsi wajib diisi'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
