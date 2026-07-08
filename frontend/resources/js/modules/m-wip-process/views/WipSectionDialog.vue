<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Section' : 'Add Section'"
    :loading="saving"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        v-model="form.name"
        label="Section Name"
        :error-messages="errors.name"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
      <VTextField
        v-model="form.code"
        label="Code"
        :error-messages="errors.code"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
      <VTextField
        v-model="form.sort_order"
        type="number"
        label="Sort Order"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />
      <VSwitch v-model="form.status" :true-value="1" :false-value="0" label="Active" hide-details />
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

const props = defineProps({
  modelValue: Boolean,
  section: Object,
  plantId: [String, Number],
  saving: Boolean,
})
const emit = defineEmits(['update:modelValue', 'save'])

const isEdit = computed(() => !!props.section)

const form = reactive({ id: null, name: '', code: '', sort_order: 0, status: 1, plant_id: null })
const errors = reactive({ name: '', code: '' })

watch(() => props.section, (val) => {
  Object.assign(form, val
    ? { id: val.id, name: val.name || '', code: val.code || '', sort_order: val.sort_order ?? 0, status: val.status ?? 1, plant_id: val.plant_id ?? null }
    : { id: null, name: '', code: '', sort_order: 0, status: 1, plant_id: props.plantId ?? null }
  )
  Object.assign(errors, { name: '', code: '' })
}, { immediate: true })

function validate() {
  let ok = true
  Object.assign(errors, { name: '', code: '' })
  if (!form.name) { errors.name = 'Required'; ok = false }
  if (!form.code) { errors.code = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('save', { ...form })
}
</script>
