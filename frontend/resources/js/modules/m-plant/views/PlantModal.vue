<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Plant' : 'Tambah Plant'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Internal Code</label>
        <input
          id="plant-code"
          v-model="form.code"
          type="text"
          placeholder="e.g. P01"
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none border-gray-300 bg-white"
        />
      </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Plant Code <span class="text-red-500">*</span></label>
        <input
          id="plant-code2"
          v-model="form.code_2"
          type="text"
          placeholder="e.g. 1001"
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.code_2 ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.code_2" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.code_2 }}</div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Plant Name <span class="text-red-500">*</span></label>
        <input
          id="plant-code3"
          v-model="form.code_3"
          type="text"
          placeholder="e.g. EOMB"
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.code_3 ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.code_3" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.code_3 }}</div>
      </div>
    </div>
    <div class="flex flex-col gap-1.5 mt-4">
      <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Deskripsi <span class="text-red-500">*</span></label>
      <input
        id="plant-description"
        v-model="form.description"
        type="text"
        class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
        :class="errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
      />
      <div v-if="errors.description" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.description }}</div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

const props  = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean })
const emit   = defineEmits(['update:modelValue', 'submit'])
const isEdit = computed(() => !!props.editData)
const form   = reactive({ code: '', code_2: '', code_3: '', description: '' })
const errors = reactive({ code_2: '', code_3: '', description: '' })

watch(() => props.editData, (val) => {
  Object.assign(form, val ? { code: val.code||'', code_2: val.code_2||'', code_3: val.code_3||'', description: val.description||'' } : { code: '', code_2: '', code_3: '', description: '' })
  Object.assign(errors, { code_2: '', code_3: '', description: '' })
})

function validate() {
  let ok = true
  Object.assign(errors, { code_2: '', code_3: '', description: '' })
  if (!form.code_2)       { errors.code_2 = 'Wajib diisi'; ok = false }
  if (!form.code_3)       { errors.code_3 = 'Wajib diisi'; ok = false }
  if (!form.description)  { errors.description = 'Wajib diisi'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>