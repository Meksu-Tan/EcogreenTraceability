<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material Packaging' : 'Tambah Material Packaging'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="form-group">
      <label class="form-label">Kode Packaging <span style="color:red">*</span></label>
      <input id="pkg-code" v-model="form.code" type="text" class="form-control" :class="{'is-invalid': errors.code}" />
      <div v-if="errors.code" class="form-error">{{ errors.code }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Kode Non-EUDR</label>
      <input id="pkg-code-noneudr" v-model="form.code_noneudr" type="text" class="form-control" />
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi <span style="color:red">*</span></label>
      <input id="pkg-description" v-model="form.description" type="text" class="form-control" :class="{'is-invalid': errors.description}" />
      <div v-if="errors.description" class="form-error">{{ errors.description }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Source Product <span style="color:red">*</span></label>
      <select id="pkg-source" v-model="form.id_material" class="form-control form-select" :class="{'is-invalid': errors.id_material}">
        <option value="">-- Pilih Material --</option>
        <option v-for="m in sourceProducts" :key="m.id_material" :value="m.id_material">{{ m.material }}</option>
      </select>
      <div v-if="errors.id_material" class="form-error">{{ errors.id_material }}</div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

const props = defineProps({
  modelValue:     { type: Boolean, default: false },
  editData:       { type: Object, default: null },
  loading:        { type: Boolean, default: false },
  sourceProducts: { type: Array, default: () => [] },
})
const emit   = defineEmits(['update:modelValue', 'submit'])
const isEdit = computed(() => !!props.editData)
const form   = reactive({ code: '', code_noneudr: '', description: '', id_material: '' })
const errors = reactive({ code: '', description: '', id_material: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val
    ? { code: val.code||'', code_noneudr: val.code_noneudr||'', description: val.description||'', id_material: val.id_material||'' }
    : { code: '', code_noneudr: '', description: '', id_material: '' })
  Object.assign(errors, { code: '', description: '', id_material: '' })
})

function validate() {
  errors.code = errors.description = errors.id_material = ''
  let ok = true
  if (!form.code)        { errors.code = 'Kode wajib diisi'; ok = false }
  if (!form.description) { errors.description = 'Deskripsi wajib diisi'; ok = false }
  if (!form.id_material) { errors.id_material = 'Source product wajib dipilih'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
