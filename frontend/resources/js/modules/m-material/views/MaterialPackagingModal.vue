<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material Packaging' : 'Tambah Material Packaging'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode Packaging <span class="text-red-500">*</span></label>
          <input
            id="pkg-code"
            v-model="form.code"
            type="text"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
            :class="errors.code ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
          />
          <div v-if="errors.code" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.code }}</div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode Non-EUDR</label>
          <input id="pkg-code-noneudr" v-model="form.code_noneudr" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" />
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Deskripsi <span class="text-red-500">*</span></label>
        <input
          id="pkg-description"
          v-model="form.description"
          type="text"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.description" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.description }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Source Product <span class="text-red-500">*</span></label>
        <select
          id="pkg-source"
          v-model="form.id_material"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.id_material ? 'border-red-300 bg-red-50' : 'border-gray-300'"
        >
          <option value="">-- Pilih Material --</option>
          <option v-for="m in sourceProducts" :key="m.id_material" :value="m.id_material">{{ m.material }}</option>
        </select>
        <div v-if="errors.id_material" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.id_material }}</div>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

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