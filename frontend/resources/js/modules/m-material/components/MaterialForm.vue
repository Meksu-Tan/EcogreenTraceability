<script setup>
import { useSetupMaterialStore } from '../stores'
import BaseModal from '@/modules/shared/components/BaseModal.vue'

const props = defineProps({
  material: { type: Object, default: null },
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'saved'])
const store = useSetupMaterialStore()

async function handleSave(data) {
  const res = props.material
    ? await store.editMaterial(props.material.id, data)
    : await store.createMaterial(data)
  if (res.status === 1) {
    emit('saved', res)
    emit('close')
  }
  return res
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="material ? 'Edit Material' : 'Add Material'"
    @close="emit('close')"
  >
    <!-- form fields would go here -->
    <div class="pa-4">
      <p class="text-body-2 text-medium-emphasis">
        Module-scoped component example — form logic is fully contained
        within <code>modules/material/components/</code>.
      </p>
    </div>
  </BaseModal>
</template>
