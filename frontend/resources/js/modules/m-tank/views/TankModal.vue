<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Tank' : 'Add Tank'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VTextField
        v-if="!isEdit"
        id="tank-id"
        v-model="form.id"
        type="number"
        label="Tank ID (Optional)"
        placeholder="Leave empty to auto-generate"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />

      <VRow dense>
        <VCol cols="12" sm="6">
          <VSelect
            id="tank-plant-select"
            v-model="form.plant_code"
            :items="plantOptions"
            label="Plant"
            :error-messages="errors.plant_code"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
            @update:model-value="onPlantChange"
          />
        </VCol>

        <VCol cols="12" sm="6">
          <VTextField
            id="tank-plant-name"
            v-model="form.plant_name"
            type="text"
            label="Plant Name"
            placeholder="Plant Name"
            :error-messages="errors.plant_name"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
      </VRow>

      <VRow dense>
        <VCol cols="12" sm="6">
          <VTextField
            id="tank-number"
            v-model="form.tank_number"
            type="text"
            label="Tank Number"
            placeholder="e.g. 210T01"
            :error-messages="errors.tank_number"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>

        <VCol cols="12" sm="6">
          <VTextField
            id="tank-height-input"
            v-model="form.tank_height"
            type="number"
            step="0.01"
            label="Tank Height"
            placeholder="e.g. 1460.00"
            :error-messages="errors.tank_height"
            density="compact"
            variant="outlined"
            hide-details="auto"
            required
          />
        </VCol>
      </VRow>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch, onMounted } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'
import { useSetupPlantStore } from '@/stores/plant'

const props  = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean })
const emit   = defineEmits(['update:modelValue', 'submit'])

const plantStore = useSetupPlantStore()
const isEdit = computed(() => !!props.editData)

const plantOptions = computed(() => {
  return (plantStore.plants || []).map(p => ({
    value: p.code_2,
    title: `${p.code_2} - ${p.description}`
  }))
})

const form = reactive({ id: '', plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
const errors = reactive({ plant_code: '', plant_name: '', tank_number: '', tank_height: '' })

onMounted(() => {
  if (plantStore.plants.length === 0) {
    plantStore.fetchPlants()
  }
})

watch(() => props.editData, (val) => {
  Object.assign(form, val
    ? { id: val.id||'', plant_code: val.plant_code||'', plant_name: val.plant_name||'', tank_number: val.tank_number||'', tank_height: val.tank_height||'' }
    : { id: '', plant_code: '', plant_name: '', tank_number: '', tank_height: '' }
  )
  Object.assign(errors, { plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
})

function onPlantChange() {
  const selected = plantStore.plants.find(p => p.code_2 === form.plant_code)
  if (selected) {
    form.plant_name = selected.description || selected.code_3 || ''
  }
}

function validate() {
  let ok = true
  Object.assign(errors, { plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
  if (!form.plant_code)  { errors.plant_code = 'Required'; ok = false }
  if (!form.plant_name)  { errors.plant_name = 'Required'; ok = false }
  if (!form.tank_number) { errors.tank_number = 'Required'; ok = false }
  if (form.tank_height === '' || form.tank_height === null) { errors.tank_height = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>