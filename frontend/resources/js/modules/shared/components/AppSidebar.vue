<template>
  <aside
    class="bg-white dark:bg-slate-900 h-screen flex flex-col flex-shrink-0 z-50 transition-all duration-300 ease-in-out border-r border-gray-200 dark:border-slate-800 fixed top-0 left-0"
    :class="uiStore.isSidebarCollapsed ? '-translate-x-full w-0 opacity-0 !border-none overflow-hidden' : 'translate-x-0 w-64'"
  >
    <!-- Brand -->
    <div class="h-16 px-6 flex items-center border-b border-gray-100 dark:border-slate-800 gap-3 overflow-hidden whitespace-nowrap">
      <div class="w-10 h-10 flex items-center justify-center flex-shrink-0">
        <img src="@/assets/logo.png" alt="Logo" class="w-full h-full object-contain" />
      </div>
      <div
        class="flex flex-col leading-tight transition-all duration-300"
        :class="uiStore.isSidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'"
      >
        <span class="text-slate-800 dark:text-slate-200 text-base font-bold tracking-tight">EUDR-TS</span>
        <span class="text-gray-400 dark:text-gray-500 text-[10px] font-medium uppercase">EO Trace System</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden">
      <ul class="space-y-2 px-3">
        <!-- Dynamic Menu Groups -->
        <template v-for="group in sidebarMenu" :key="group.id">
          <!-- Group Label -->
          <li
            class="px-4 py-2 text-[11px] font-extrabold text-gray-400 uppercase tracking-widest transition-all duration-300 overflow-hidden whitespace-nowrap"
            :class="[
              group.id !== 'main' ? 'mt-8 mb-2' : 'mt-2 mb-2',
              uiStore.isSidebarCollapsed ? 'opacity-0 h-0 py-0 mt-0 mb-0' : 'opacity-100'
            ]"
          >
            {{ group.label }}
          </li>

          <!-- Simple Items (no children) -->
          <template v-if="group.items">
            <li v-for="item in group.items" :key="item.path">
              <RouterLink :to="item.path" custom v-slot="{ navigate, isActive }">
                <div
                  class="group flex items-center gap-4 px-4 py-3 rounded-lg cursor-pointer transition-all overflow-hidden whitespace-nowrap"
                  :class="[
                    isActive ? 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 font-bold shadow-sm shadow-green-100/50 dark:shadow-none' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-green-600 dark:hover:text-green-400',
                    uiStore.isSidebarCollapsed ? 'justify-center px-0' : ''
                  ]"
                  @click="() => { navigate(); uiStore.closeSidebar(); }"
                  :title="uiStore.isSidebarCollapsed ? item.label : ''"
                >
                  <Icon
                    :icon="item.icon"
                    class="w-5 h-5 text-center text-sm flex-shrink-0"
                    :class="isActive ? 'text-green-600 dark:text-green-400' : 'text-slate-400 dark:text-slate-500 group-hover:text-green-600 dark:group-hover:text-green-400'"
                  />
                  <span
                    class="text-sm transition-all duration-300"
                    :class="uiStore.isSidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'"
                  >
                    {{ item.label }}
                  </span>
                </div>
              </RouterLink>
            </li>
          </template>
        </template>
      </ul>
    </nav>

    <!-- Sidebar footer user -->
    <div class="p-4 border-t border-gray-100 dark:border-slate-800 overflow-hidden">
      <div
        class="flex items-center gap-3 transition-all duration-300"
        :class="uiStore.isSidebarCollapsed ? 'justify-center' : ''"
      >
        <div class="w-9 h-9 rounded-full bg-green-600 flex items-center justify-center text-white text-sm font-bold shadow-sm flex-shrink-0">
          {{ userInitial }}
        </div>
        <div
          class="min-w-0 transition-all duration-300"
          :class="uiStore.isSidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'"
        >
          <div class="text-slate-800 dark:text-slate-200 text-sm font-bold truncate">
            {{ authStore.user?.name || 'User' }}
          </div>
          <div class="text-gray-400 dark:text-gray-500 text-[11px] font-medium truncate">
            {{ firstRole }}
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/modules/shared/stores/ui'
import { sidebarMenu } from '@/config/sidebar'

const authStore = useAuthStore()
const uiStore = useUiStore()

const userInitial = computed(() => (authStore.user?.name || 'U').charAt(0).toUpperCase())
const firstRole = computed(() => authStore.roles?.[0] || '')
</script>