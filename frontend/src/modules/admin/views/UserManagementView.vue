<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">User Management</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">Admin Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Users</span>
        </div>
      </div>
      <button
        id="btn-tambah-user"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95"
        @click="openModal"
      >
        <i class="fas fa-plus"></i> Tambah User
      </button>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/30">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-users text-green-600"></i>
          Data Pengguna
        </h4>
      </div>

      <div class="p-6">
        <DataTable
          :columns="columns"
          :data="store.users"
          :loading="store.loading"
          row-key="id"
        >
          <!-- Custom rendering for Name + Email -->
          <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-xs shrink-0">
                {{ row.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="font-bold text-slate-800">{{ row.name }}</div>
                <div class="text-xs text-gray-500">{{ row.email }}</div>
              </div>
            </div>
          </template>

          <!-- Role Badge -->
          <template #cell-roles="{ row }">
            <span
              v-for="role in row.roles"
              :key="role.id"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
              :class="{
                'bg-green-100 text-green-800': role.name === 'admin',
                'bg-green-100 text-green-800': role.name === 'user',
                'bg-gray-100 text-gray-800': !['admin', 'user'].includes(role.name)
              }"
            >
              {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
            </span>
            <span v-if="!row.roles || row.roles.length === 0" class="text-xs text-gray-400 italic">No Role</span>
          </template>

          <!-- Custom Actions -->
          <template #actions="{ row }">
            <div class="flex items-center justify-center gap-1.5">
              <button
                class="p-1.5 rounded-md bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm active:scale-90"
                title="Edit"
                @click="onEdit(row)"
              >
                <i class="fas fa-pencil-alt text-[11px]"></i>
              </button>
              <button
                class="p-1.5 rounded-md bg-red-500 hover:bg-red-600 transition-colors shadow-sm active:scale-90 text-white"
                title="Hapus User"
                @click="onDelete(row)"
              >
                <i class="fas fa-trash text-[11px]"></i>
              </button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <UserModal
      v-model="showModal"
      :edit-data="editRow"
      :loading="submitting"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import UserModal from './UserModal.vue'
import { useAdminUsersStore } from '@/modules/admin/stores'
import { useToastStore } from '@/stores/toast'

const store      = useAdminUsersStore()
const toast      = useToastStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

// Columns definition for DataTable
const columns = [
  { key: 'name',  label: 'Pengguna' }, // Will use slot #cell-name
  { key: 'roles', label: 'Role' },     // Will use slot #cell-roles
]

onMounted(() => {
  store.fetchUsers()
  store.fetchRoles() // Fetch roles so modal has them ready
})

function openModal() {
  editRow.value = null
  showModal.value = true
}

function onEdit(row) {
  editRow.value = row
  showModal.value = true
}

async function onDelete(row) {
  if (!confirm(`Hapus pengguna "${row.name}"? Tindakan ini tidak dapat dibatalkan.`)) return

  const response = await store.deleteUser(row.id)
  if (response.status === 1) {
    toast.success(response.message)
  } else {
    toast.error(response.message)
  }
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const isEditing = !!editRow.value
    const payload = { ...data }

    // In edit mode, do not send empty password
    if (isEditing && !payload.password) {
      delete payload.password
    }

    const response = isEditing
      ? await store.updateUser(editRow.value.id, payload)
      : await store.createUser(payload)

    if (response.status === 1) {
      toast.success(response.message)
      showModal.value = false
    } else {
      // Show first validation error if exists, else generic message
      const errorMsg = response.errors
        ? Object.values(response.errors)[0][0]
        : response.message
      toast.error(errorMsg)
    }
  } finally {
    submitting.value = false
  }
}
</script>