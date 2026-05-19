<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material' : 'Tambah Material'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode Material <span class="text-red-500">*</span></label>
          <input 
            id="mat-code" 
            v-model="form.code" 
            type="text" 
            class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
            :class="errors.code ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
            placeholder="RM-001" 
          />
          <div v-if="errors.code" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.code }}</div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode Non-EUDR</label>
          <input id="mat-code-noneudr" v-model="form.code_noneudr" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" placeholder="Opsional" />
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Deskripsi <span class="text-red-500">*</span></label>
        <input 
          id="mat-description" 
          v-model="form.description" 
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none" 
          :class="errors.description ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'" 
          placeholder="Nama material" 
        />
        <div v-if="errors.description" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.description }}</div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Tipe <span class="text-red-500">*</span></label>
          <select 
            id="mat-type" 
            v-model="form.type" 
            class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none bg-white cursor-pointer appearance-none" 
            :class="errors.type ? 'border-red-300 bg-red-50' : 'border-gray-300'"
          >
            <option value="">-- Pilih --</option>
            <option value="WIP">WIP</option>
            <option value="RM">Raw Material</option>
            <option value="FG">Finished Good</option>
          </select>
          <div v-if="errors.type" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.type }}</div>
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Yield (%)</label>
          <input id="mat-yield" v-model="form.yield" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" min="0" max="100" step="0.1" />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">QTF Feed</label>
          <input id="mat-qtf-feed" v-model="form.qtf_feed" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">QTF Rundown</label>
          <input id="mat-qtf-rundown" v-model="form.qtf_rundown" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" />
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Kode Supplier Material</label>
        <input id="mat-code-supplier" v-model="form.code_matl_supplier" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none transition-all" />
      </div>

      <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-100">
        <input id="mat-status-packaging" v-model="form.status_packaging" type="checkbox" true-value="1" false-value="0" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500" />
        <label for="mat-status-packaging" class="text-sm font-bold text-slate-700 cursor-pointer">Aktif untuk Packaging</label>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editData:   { type: Object, default: null },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'submit'])

const isEdit = computed(() => !!props.editData)

import { computed } from 'vue'

const form = reactive({
  code: '', code_noneudr: '', description: '', type: '',
  yield: 100, qtf_feed: '', qtf_rundown: '',
  code_matl_supplier: '', status_packaging: '0',
})

const errors = reactive({ code: '', description: '', type: '' })

watch(() => props.editData, (val) => {
  if (val) {
    Object.assign(form, {
      code: val.code || '', code_noneudr: val.code_noneudr || '',
      description: val.description || '', type: val.type || '',
      yield: val.yield || 100, qtf_feed: val.qtf_feed || '',
      qtf_rundown: val.qtf_rundown || '',
      code_matl_supplier: val.code_matl_supplier || '',
      status_packaging: String(val.status_packaging || '0'),
    })
  } else {
    Object.assign(form, { code: '', code_noneudr: '', description: '', type: '', yield: 100, qtf_feed: '', qtf_rundown: '', code_matl_supplier: '', status_packaging: '0' })
  }
  Object.assign(errors, { code: '', description: '', type: '' })
})

function validate() {
  errors.code = errors.description = errors.type = ''
  let ok = true
  if (!form.code)        { errors.code = 'Kode wajib diisi'; ok = false }
  if (!form.description) { errors.description = 'Deskripsi wajib diisi'; ok = false }
  if (!form.type)        { errors.type = 'Tipe wajib dipilih'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
