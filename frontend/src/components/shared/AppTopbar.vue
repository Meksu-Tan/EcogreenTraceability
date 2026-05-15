<template>
  <header class="h-16 bg-green-600 flex items-center justify-between px-6 shadow-sm z-40">
    <!-- Left: hamburger + page title -->
    <div class="flex items-center gap-4">
      <button 
        @click="uiStore.toggleSidebar()"
        class="w-10 h-10 flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-all"
        title="Toggle Sidebar"
      >
        <i class="fas" :class="uiStore.isSidebarCollapsed ? 'fa-indent' : 'fa-outdent'"></i>
      </button>
      <div class="text-white text-lg font-bold tracking-tight transition-all duration-300">
        {{ pageTitle }}
      </div>
    </div>

    <div class="flex items-center gap-4">
      <!-- User info + dropdown -->
      <div class="relative" ref="dropdownRef">
        <div 
          class="flex items-center gap-3 cursor-pointer group"
          @click="showDropdown = !showDropdown"
        >
          <div class="hidden sm:block text-right">
            <div class="text-white text-xs font-medium opacity-80">Welcome,</div>
            <div class="text-white text-sm font-bold leading-tight">{{ authStore.user?.name }}</div>
          </div>
          <div class="w-9 h-9 rounded-full bg-white/20 border border-white/30 flex items-center justify-center text-white text-sm font-bold backdrop-blur-sm group-hover:bg-white/30 transition-all">
            {{ userInitial }}
          </div>
          <i class="fas fa-chevron-down text-white/60 text-[10px] group-hover:text-white transition-all"></i>
        </div>

        <!-- Dropdown menu -->
        <transition
          enter-active-class="transition duration-100 ease-out"
          enter-from-class="transform scale-95 opacity-0"
          enter-to-class="transform scale-100 opacity-100"
          leave-active-class="transition duration-75 ease-in"
          leave-from-class="transform scale-100 opacity-100"
          leave-to-class="transform scale-95 opacity-0"
        >
          <div
            v-if="showDropdown"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden"
          >
            <div class="px-4 py-2 border-b border-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
              Account Settings
            </div>
            <div
              class="flex items-center gap-3 px-4 py-3 text-sm text-red-500 hover:bg-red-50 cursor-pointer font-semibold transition-colors"
              @click="handleLogout"
            >
              <i class="fas fa-sign-out-alt"></i>
              <span>{{ loggingOut ? 'Processing...' : 'Logout' }}</span>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { usePlantSelectionStore } from '@/stores/plantSelection'

const authStore    = useAuthStore()
const uiStore      = useUiStore()
const plantSelectionStore = usePlantSelectionStore()
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
    '/setup/plant':      'Setup Plant',
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

