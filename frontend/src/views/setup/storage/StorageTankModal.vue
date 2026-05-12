<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Storage Tank' : 'Tambah Storage Tank'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Tipe (Code 2) <span style="color:red">*</span></label>
        <input id="tank-code2" v-model="form.code_2" type="text" class="form-control" :class="{'is-invalid': errors.code_2}" />
        <div v-if="errors.code_2" class="form-error">{{ errors.code_2 }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Kode (Code 3) <span style="color:red">*</span></label>
        <input id="tank-code3" v-model="form.code_3" type="text" class="form-control" :class="{'is-invalid': errors.code_3}" />
        <div v-if="errors.code_3" class="form-error">{{ errors.code_3 }}</div>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Kode Supplier (Code 4)</label>
      <input id="tank-code4" v-model="form.code_4" type="text" class="form-control" />
    </div>
    <div class="form-group">
      <label class="form-label">ID Plant <span style="color:red">*</span></label>
      <input id="tank-id-plant" v-model="form.id_plant" type="text" class="form-control" :class="{'is-invalid': errors.id_plant}" />
      <div v-if="errors.id_plant" class="form-error">{{ errors.id_plant }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi <span style="color:red">*</span></label>
      <input id="tank-description" v-model="form.description" type="text" class="form-control" :class="{'is-invalid': errors.description}" />
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
const form   = reactive({ code_2: '', code_3: '', code_4: '', id_plant: '', description: '' })
const errors = reactive({ code_2: '', code_3: '', id_plant: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { code_2: val.code_2||'', code_3: val.code_3||'', code_4: val.code_4||'', id_plant: val.id_plant||'', description: val.description||'' } : { code_2: '', code_3: '', code_4: '', id_plant: '', description: '' })
  Object.assign(errors, { code_2: '', code_3: '', id_plant: '', description: '' })
})

function validate() {
  let ok = true
  Object.assign(errors, { code_2: '', code_3: '', id_plant: '', description: '' })
  if (!form.code_2)       { errors.code_2 = 'Wajib diisi'; ok = false }
  if (!form.code_3)       { errors.code_3 = 'Wajib diisi'; ok = false }
  if (!form.id_plant)     { errors.id_plant = 'Wajib diisi'; ok = false }
  if (!form.description)  { errors.description = 'Wajib diisi'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
