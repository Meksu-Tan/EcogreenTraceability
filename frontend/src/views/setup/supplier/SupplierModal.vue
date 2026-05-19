<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Supplier' : 'Tambah Supplier'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode <span class="text-red-500">*</span></label>
        <input 
          id="sup-code" 
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
          id="sup-description" 
          v-model="form.description" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
          :class="errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
        />
        <div v-if="errors.description" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.description }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Tipe (ID Tank/Sloc)</label>
        <input 
          id="sup-type" 
          v-model="form.type" 
          type="text" 
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" 
          placeholder="ID Tank atau kosongkan" 
        />
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Batch Code</label>
        <input 
          id="sup-batch-code" 
          v-model="form.batch_code" 
          type="text" 
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" 
        />
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
