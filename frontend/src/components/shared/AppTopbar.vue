<template>
  <header class="app-topbar">
    <!-- Left: hamburger + page title -->
    <div class="topbar-left">
      <div class="topbar-title">{{ pageTitle }}</div>
    </div>

    <!-- Right: user dropdown -->
    <div class="topbar-right">
      <!-- User info + dropdown -->
      <div style="position:relative;" ref="dropdownRef">
        <div class="topbar-user" @click="showDropdown = !showDropdown">
          <div class="topbar-avatar">{{ userInitial }}</div>
          <div style="display:none;" class="topbar-name-block">
            <div class="topbar-name">Hi, {{ authStore.user?.name }}</div>
          </div>
          <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text-muted);margin-left:4px;"></i>
        </div>

        <!-- Dropdown menu (Stisla style) -->
        <div
          v-if="showDropdown"
          style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid var(--border-color);border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.12);min-width:180px;z-index:200;overflow:hidden;"
        >
          <div style="padding:10px 16px;border-bottom:1px solid var(--border-color);font-size:12px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em;">
            Account
          </div>
          <div
            style="display:flex;align-items:center;gap:10px;padding:10px 16px;font-size:13px;color:var(--danger);cursor:pointer;font-weight:600;"
            @click="handleLogout"
          >
            <i class="fas fa-sign-out-alt"></i>
            <span>{{ loggingOut ? 'Loading...' : 'Logout' }}</span>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore    = useAuthStore()
const router       = useRouter()
const route        = useRoute()
const loggingOut   = ref(false)
const showDropdown = ref(false)
const dropdownRef  = ref(null)

const userInitial = computed(() => (authStore.user?.name || 'U').charAt(0).toUpperCase())

const pageTitle = computed(() => {
  const map = {
    '/dashboard':        'Dashboard',
    '/setup/material':   'Setup Material',
    '/setup/storage':    'Setup Storage',
    '/setup/supplier':   'Setup Supplier',
  }
  return map[route.path] || 'EUDR-TS'
})

// Close dropdown on outside click
function onClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    showDropdown.value = false
  }
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))

async function handleLogout() {
  loggingOut.value   = true
  showDropdown.value = false
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>
