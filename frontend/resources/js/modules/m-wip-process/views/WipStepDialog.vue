<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Step' : 'Add Step'"
    :loading="saving"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <VSelect
        v-model="form.step_type"
        :items="stepTypes"
        label="Step Type"
        :error-messages="errors.step_type"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
      <VTextField
        v-model="form.label"
        label="Label"
        :error-messages="errors.label"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />
      <VTextField
        v-if="form.step_type === 'feed'"
        v-model="form.feed_id"
        label="Feed ID"
        :error-messages="errors.feed_id"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />
      <VTextField
        v-if="form.step_type === 'rundown'"
        v-model="form.rundown_id"
        label="Rundown ID"
        :error-messages="errors.rundown_id"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />
      <VTextField
        v-model="form.dcs_tag"
        label="DCS Tag"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />
      <template v-if="form.step_type === 'mode_switch'">
        <VTextField
          v-model="form.mode_group"
          label="Mode Group"
          :error-messages="errors.mode_group"
          density="compact"
          variant="outlined"
          hide-details="auto"
        />
        <VTextField
          v-model="form.mode_value"
          label="Mode Value"
          density="compact"
          variant="outlined"
          hide-details="auto"
        />
      </template>
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
  step: Object,
  section: Object,
  saving: Boolean,
})
const emit = defineEmits(['update:modelValue', 'save'])

const isEdit = computed(() => !!props.step)

const stepTypes = [
  { title: 'Label', value: 'label' },
  { title: 'Feed', value: 'feed' },
  { title: 'Rundown', value: 'rundown' },
  { title: 'Mode Switch', value: 'mode_switch' },
]

const form = reactive({
  id: null, section_id: null, step_type: 'label', label: '',
  feed_id: '', rundown_id: '', dcs_tag: '', mode_group: '', mode_value: '',
  sort_order: 0, status: 1,
})
const errors = reactive({ step_type: '', label: '', feed_id: '', rundown_id: '', mode_group: '' })

watch(() => props.step, (val) => {
  Object.assign(form, val
    ? {
      id: val.id, section_id: val.section_id, step_type: val.step_type || 'label', label: val.label || '',
      feed_id: val.feed_id || '', rundown_id: val.rundown_id || '', dcs_tag: val.dcs_tag || '',
      mode_group: val.mode_group || '', mode_value: val.mode_value || '',
      sort_order: val.sort_order ?? 0, status: val.status ?? 1,
    }
    : {
      id: null, section_id: props.section?.id ?? null, step_type: 'label', label: '',
      feed_id: '', rundown_id: '', dcs_tag: '', mode_group: '', mode_value: '',
      sort_order: 0, status: 1,
    }
  )
  Object.assign(errors, { step_type: '', label: '', feed_id: '', rundown_id: '', mode_group: '' })
}, { immediate: true })

function validate() {
  let ok = true
  Object.assign(errors, { step_type: '', label: '', feed_id: '', rundown_id: '', mode_group: '' })
  if (!form.step_type) { errors.step_type = 'Required'; ok = false }
  if (!form.label) { errors.label = 'Required'; ok = false }
  if (form.step_type === 'feed' && !form.feed_id) { errors.feed_id = 'Required'; ok = false }
  if (form.step_type === 'rundown' && !form.rundown_id) { errors.rundown_id = 'Required'; ok = false }
  if (form.step_type === 'mode_switch' && !form.mode_group) { errors.mode_group = 'Required'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('save', { ...form, section_id: props.section?.id ?? form.section_id })
}
</script>
