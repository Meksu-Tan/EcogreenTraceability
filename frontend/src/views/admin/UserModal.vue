<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="isEdit ? 'Edit User' : 'Tambah User'"
    :loading="loading"
    size="md"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      
      <!-- Name -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">
          Nama Lengkap <span class="text-red-500">*</span>
        </label>
        <input 
          v-model="form.name"
          type="text" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
          placeholder="Masukkan nama lengkap"
        />
        <div v-if="errors.name" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.name }}</div>
      </div>

      <!-- Email -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">
          Email <span class="text-red-500">*</span>
        </label>
        <input 
          v-model="form.email"
          type="email" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.email ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
          placeholder="email@example.com"
        />
        <div v-if="errors.email" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.email }}</div>
      </div>

      <!-- Password -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">
          Password <span v-if="!isEdit" class="text-red-500">*</span>
        </label>
        <input 
          v-model="form.password"
          type="password" 
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.password ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
          :placeholder="isEdit ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter'"
        />
        <div v-if="errors.password" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.password }}</div>
      </div>

      <!-- Role -->
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">
          Role / Peran <span class="text-red-500">*</span>
        </label>
        <select 
          v-model="form.role"
          class="w-full px-3 py-2 border rounded-md text-sm transition-all focus:ring-1 focus:ring-green-500 focus:border-green-500 outline-none"
          :class="errors.role ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        >
          <option value="" disabled>Pilih Role</option>
          <option 
            v-for="r in store.roles" 
            :key="r.id" 
            :value="r.name"
          >
            {{ r.name.charAt(0).toUpperCase() + r.name.slice(1) }}
          </option>
        </select>
        <div v-if="errors.role" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.role }}</div>
      </div>

    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import { useAdminUsersStore } from '@/stores/adminUsers'

const props = defineProps({
  modelValue: Boolean,
  editData: { type: Object, default: null },
  loading: Boolean
})

const emit = defineEmits(['update:modelValue', 'submit'])
const store = useAdminUsersStore()

const isEdit = computed(() => !!props.editData)

const form = reactive({ name: '', email: '', password: '', role: '' })
const errors = reactive({ name: '', email: '', password: '', role: '' })

watch(() => props.modelValue, (val) => {
  if (val) {
    if (props.editData) {
      form.name = props.editData.name
      form.email = props.editData.email
      form.password = ''
      form.role = props.editData.roles && props.editData.roles.length > 0 ? props.editData.roles[0].name : ''
    } else {
      form.name = ''
      form.email = ''
      form.password = ''
      form.role = ''
    }
    // Clear errors
    errors.name = ''
    errors.email = ''
    errors.password = ''
    errors.role = ''
  }
})

function validate() {
  errors.name = errors.email = errors.password = errors.role = ''
  let ok = true

  if (!form.name) {
    errors.name = 'Nama wajib diisi'
    ok = false
  }

  if (!form.email) {
    errors.email = 'Email wajib diisi'
    ok = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Format email tidak valid'
    ok = false
  }

  if (!isEdit.value && !form.password) {
    errors.password = 'Password wajib diisi'
    ok = false
  } else if (form.password && form.password.length < 8) {
    errors.password = 'Password minimal 8 karakter'
    ok = false
  }

  if (!form.role) {
    errors.role = 'Role wajib dipilih'
    ok = false
  }

  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
