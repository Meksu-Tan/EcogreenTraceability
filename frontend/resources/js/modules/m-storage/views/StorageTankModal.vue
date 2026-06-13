<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Storage Tank' : 'Add Storage Tank'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="tank-code2"
            v-model="form.code_2"
            label="Type (Code 2)"
            :error-messages="errors.code_2"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
        <VCol cols="12" sm="6">
          <VTextField
            id="tank-code3"
            v-model="form.code_3"
            label="Code (Code 3)"
            :error-messages="errors.code_3"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
      </VRow>

      <VTextField
        id="tank-code4"
        v-model="form.code_4"
        label="Supplier Code (Code 4)"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />

      <VTextField
        id="tank-id-plant"
        v-model="form.id_plant"
        label="ID Plant"
        :error-messages="errors.id_plant"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <VTextField
        id="tank-description"
        v-model="form.description"
        label="Description"
        :error-messages="errors.description"
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
  if (!form.code_2)       { errors.code_2 = 'Required'; ok = false }
  if (!form.code_3)       { errors.code_3 = 'Required'; ok = false }
  if (!form.id_plant)     { errors.id_plant = 'Required'; ok = false }
  if (!form.description)  { errors.description = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>