<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Warehouse' : 'Tambah Warehouse'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">ID Batch <span class="text-red-500">*</span></label>
        <input 
          id="wh-id-batch" 
          v-model="form.id_batch" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
          :class="errors.id_batch ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
        />
        <div v-if="errors.id_batch" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.id_batch }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode <span class="text-red-500">*</span></label>
        <input 
          id="wh-code" 
          v-model="form.code" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
          :class="errors.code ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
        />
        <div v-if="errors.code" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.code }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Deskripsi <span class="text-red-500">*</span></label>
        <input 
          id="wh-description" 
          v-model="form.description" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
          :class="errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
        />
        <div v-if="errors.description" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.description }}</div>
      </div>
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
