<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Storage Detail' : 'Tambah Storage Detail'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">TF Number <span class="text-red-500">*</span></label>
        <input
          id="detail-tf-number"
          v-model="form.tf_number"
          type="text"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.tf_number ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.tf_number" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.tf_number }}</div>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

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