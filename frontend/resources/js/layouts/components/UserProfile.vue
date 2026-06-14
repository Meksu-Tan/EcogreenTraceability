<template>
  <VBtn
    variant="text"
    color="medium-emphasis"
    size="small"
    class="me-1"
  >
    <VAvatar color="primary" size="32" class="me-2">
      <span class="text-caption font-weight-bold text-white">{{ initial }}</span>
    </VAvatar>
    <span class="d-none d-sm-inline text-body-2 font-weight-medium me-1">{{ authStore.user?.name }}</span>
    <VIcon icon="ri-arrow-down-s-line" size="16" />

    <VMenu activator="parent" location="bottom end" :offset="8" width="200">
      <VList density="compact">
        <VListItem>
          <VListItemTitle class="text-body-2 font-weight-bold">{{ authStore.user?.name }}</VListItemTitle>
          <VListItemSubtitle class="text-caption text-uppercase">{{ firstRole }}</VListItemSubtitle>
        </VListItem>
        <VDivider />
        <VListItem
          prepend-icon="ri-logout-box-r-line"
          title="Logout"
          color="error"
          @click="handleLogout"
        />
      </VList>
    </VMenu>
  </VBtn>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const authStore  = useAuthStore()
const router     = useRouter()
const loggingOut = ref(false)

const initial   = computed(() => (authStore.user?.name || 'U').charAt(0).toUpperCase())
const firstRole = computed(() => authStore.roles?.[0] || '')

async function handleLogout() {
  if (loggingOut.value) return
  loggingOut.value = true
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>
