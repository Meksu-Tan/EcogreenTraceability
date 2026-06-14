<template>
  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">User Management</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">Admin Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Users</span>
        </div>
      </div>
      <VBtn
        id="btn-tambah-user"
        color="primary"
        prepend-icon="ri-add-line"
        @click="openModal"
      >
        Add User
      </VBtn>
    </div>

    <!-- Data Table Card -->
    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-user-3-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Users Data</span>
      </VCardTitle>
      <VDivider />

      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.users"
          :loading="store.loading"
          row-key="id"
        >
          <!-- Name + Email -->
          <template #cell-name="{ row }">
            <div class="d-flex align-center gap-3">
              <VAvatar color="primary-lighten-2" size="32">
                <span class="text-caption font-weight-bold text-primary">{{ row.name.charAt(0).toUpperCase() }}</span>
              </VAvatar>
              <div>
                <p class="text-body-2 font-weight-bold mb-0">{{ row.name }}</p>
                <p class="text-caption text-medium-emphasis mb-0">{{ row.email }}</p>
              </div>
            </div>
          </template>

          <!-- Plant Assignment -->
          <template #cell-plant="{ row }">
            <div class="d-flex flex-wrap gap-1">
              <VChip
                v-for="plant in row.plants"
                :key="plant.code_3"
                size="x-small"
                color="secondary"
                variant="tonal"
                class="me-1"
              >
                {{ plant.description }}
              </VChip>
              <span v-if="!row.plants || row.plants.length === 0" class="text-caption text-medium-emphasis">
                All Plants (Global)
              </span>
            </div>
          </template>

          <!-- Roles -->
          <template #cell-roles="{ row }">
            <VChip
              v-for="role in row.roles"
              :key="role.id"
              size="x-small"
              color="primary"
              variant="tonal"
              class="me-1"
            >
              {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
            </VChip>
            <span v-if="!row.roles || row.roles.length === 0" class="text-caption text-medium-emphasis">No Role</span>
          </template>

          <!-- Actions -->
          <template #actions="{ row }">
            <div class="d-flex justify-center gap-1">
              <VBtn size="x-small" icon="ri-edit-line" color="primary" variant="tonal" @click="onEdit(row)" />
              <VBtn size="x-small" icon="ri-delete-bin-line" color="error" variant="tonal" @click="onDelete(row)" />
            </div>
          </template>
        </DataTable>
      </VCardText>
    </VCard>

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
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store      = useAdminUsersStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const showModal  = ref(false)
const editRow    = ref(null)
const submitting = ref(false)

// Columns definition for DataTable
const columns = [
  { key: 'name',  label: 'User' }, // Will use slot #cell-name
  { key: 'plant', label: 'Plant Assignment' }, // Will use slot #cell-plant
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
  const isConfirmed = await confirmStore.show({ message: `Delete user "${row.name}"? This action cannot be undone.` })
  if (!isConfirmed) return

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