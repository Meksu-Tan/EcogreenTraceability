<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material Packaging' : 'Add Material Packaging'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="pkg-code"
            v-model="form.code"
            label="Packaging Code"
            :error-messages="errors.code"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="pkg-code-noneudr"
            v-model="form.code_noneudr"
            label="Non-EUDR Code"
            density="compact"
            variant="outlined"
            hide-details="auto"
          />
        </VCol>
      </VRow>

      <VTextField
        id="pkg-description"
        v-model="form.description"
        label="Description"
        :error-messages="errors.description"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VSelect
        id="pkg-source"
        v-model="form.id_material"
        :items="sourceProductOptions"
        label="Source Product"
        placeholder="Select Material"
        :error-messages="errors.id_material"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
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

const sourceProductOptions = computed(() => {
  return (props.sourceProducts || []).map(m => ({
    value: m.id_material,
    title: m.material
  }))
})
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
  if (!form.code)        { errors.code = 'Code is required'; ok = false }
  if (!form.description) { errors.description = 'Description is required'; ok = false }
  if (!form.id_material) { errors.id_material = 'Source product must be selected'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>