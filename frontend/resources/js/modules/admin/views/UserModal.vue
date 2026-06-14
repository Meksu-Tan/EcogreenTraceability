<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="isEdit ? 'Edit User' : 'Add User'"
    :loading="loading"
    size="md"
    @submit="handleSubmit"
  >
    <div class="d-flex flex-column gap-4 py-2">
      <!-- Name -->
      <VTextField
        v-model="form.name"
        label="Full Name"
        placeholder="Enter full name"
        :error-messages="errors.name"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <!-- Email -->
      <VTextField
        v-model="form.email"
        type="email"
        label="Email"
        placeholder="email@example.com"
        :error-messages="errors.email"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <!-- Password -->
      <VTextField
        v-model="form.password"
        type="password"
        label="Password"
        :placeholder="isEdit ? 'Leave empty if you do not want to change' : 'Minimum 8 characters'"
        :error-messages="errors.password"
        density="compact"
        variant="outlined"
        hide-details="auto"
        :required="!isEdit"
      />

      <!-- Role -->
      <VSelect
        v-model="form.role"
        :items="roleOptions"
        label="Role"
        placeholder="Select Role"
        :error-messages="errors.role"
        density="compact"
        variant="outlined"
        hide-details="auto"
        required
      />

      <!-- Assigned Plants -->
      <VSelect
        v-model="form.plants"
        :items="plantOptions"
        label="Assigned Plants"
        placeholder="Select accessible plants"
        multiple
        chips
        :error-messages="errors.plants"
        density="compact"
        variant="outlined"
        hide-details="auto"
      />
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch, onMounted } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'
import { useAdminUsersStore } from '@/modules/admin/stores'
import { useSetupPlantStore } from '@/stores/plant.js'

const props = defineProps({
  modelValue: Boolean,
  editData: { type: Object, default: null },
  loading: Boolean
})

const emit = defineEmits(['update:modelValue', 'submit'])
const store = useAdminUsersStore()
const plantStore = useSetupPlantStore()

const isEdit = computed(() => !!props.editData)

const form = reactive({ name: '', email: '', password: '', role: '', plants: [] })
const errors = reactive({ name: '', email: '', password: '', role: '', plants: '' })

const roleOptions = computed(() => {
  return (store.roles || []).map(r => ({
    value: r.name,
    title: r.name.charAt(0).toUpperCase() + r.name.slice(1)
  }))
})

const plantOptions = computed(() => {
  return (plantStore.plants || []).map(p => ({
    value: p.code_3,
    title: `${p.code_3} - ${p.description}`
  }))
})

onMounted(() => {
  plantStore.fetchPlants()
})

watch(() => props.modelValue, (val) => {
  if (val) {
    plantStore.fetchPlants()
    if (props.editData) {
      form.name = props.editData.name
      form.email = props.editData.email
      form.password = ''
      form.role = props.editData.roles && props.editData.roles.length > 0 ? props.editData.roles[0].name : ''
      form.plants = props.editData.plants ? props.editData.plants.map(p => p.code_3) : []
    } else {
      form.name = ''
      form.email = ''
      form.password = ''
      form.role = ''
      form.plants = []
    }
    // Clear errors
    errors.name = ''
    errors.email = ''
    errors.password = ''
    errors.role = ''
    errors.plants = ''
  }
})

function validate() {
  errors.name = errors.email = errors.password = errors.role = errors.plants = ''
  let ok = true

  if (!form.name) {
    errors.name = 'Name is required'
    ok = false
  }

  if (!form.email) {
    errors.email = 'Email is required'
    ok = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Invalid email format'
    ok = false
  }

  if (!isEdit.value && !form.password) {
    errors.password = 'Password is required'
    ok = false
  } else if (form.password && form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    ok = false
  }

  if (!form.role) {
    errors.role = 'Role must be selected'
    ok = false
  }

  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>