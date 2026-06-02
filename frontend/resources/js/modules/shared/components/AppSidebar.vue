<template>
  <aside
    class="bg-white border-r border-gray-200 min-h-screen flex flex-col flex-shrink-0 z-50 transition-all duration-300 ease-in-out"
    :class="uiStore.isSidebarCollapsed ? 'w-20' : 'w-64'"
  >
    <!-- Brand -->
    <div class="h-16 px-6 flex items-center border-b border-gray-100 gap-3 overflow-hidden whitespace-nowrap">
      <div class="w-10 h-10 flex items-center justify-center flex-shrink-0">
        <img src="@/assets/logo.png" alt="Logo" class="w-full h-full object-contain" />
      </div>
      <div
        class="flex flex-col leading-tight transition-all duration-300"
        :class="uiStore.isSidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'"
      >
        <span class="text-slate-800 text-base font-bold tracking-tight">EUDR-TS</span>
        <span class="text-gray-400 text-[10px] font-medium uppercase">EO Trace System</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden">
      <ul class="space-y-1 px-3">
        <!-- Dynamic Menu Groups -->
        <template v-for="group in sidebarMenu" :key="group.id">
          <!-- Group Label -->
          <li
            class="px-3 py-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest transition-all duration-300 overflow-hidden whitespace-nowrap"
            :class="[
              group.id !== 'main' ? 'mt-6 mb-1' : '',
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
                  class="group flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-all overflow-hidden whitespace-nowrap"
                  :class="[
                    isActive ? 'bg-green-50 text-green-600 font-bold shadow-sm shadow-green-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-green-600',
                    uiStore.isSidebarCollapsed ? 'justify-center px-0' : ''
                  ]"
                  @click="navigate"
                  :title="uiStore.isSidebarCollapsed ? item.label : ''"
                >
                  <Icon
                    :icon="item.icon"
                    class="w-5 h-5 text-center text-sm flex-shrink-0"
                    :class="isActive ? 'text-green-600' : 'text-slate-400 group-hover:text-green-600'"
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

          <!-- Collapsible Group (with children) -->
          <li v-if="group.children">
            <div
              class="flex items-center justify-between px-3 py-2.5 rounded-lg cursor-pointer transition-all text-slate-600 hover:bg-slate-50 hover:text-green-600 overflow-hidden whitespace-nowrap"
              :class="[
                openGroups[group.id] ? 'bg-slate-50/50 text-green-600 font-bold' : '',
                uiStore.isSidebarCollapsed ? 'justify-center px-0' : ''
              ]"
              @click="uiStore.isSidebarCollapsed ? uiStore.toggleSidebar() : toggleGroup(group.id)"
              :title="uiStore.isSidebarCollapsed ? group.label : ''"
            >
              <div class="flex items-center gap-3">
                <Icon
                  :icon="group.icon"
                  class="w-5 h-5 text-center text-sm flex-shrink-0"
                  :class="[openGroups[group.id] ? 'text-green-600' : 'text-slate-400']"
                />
                <span
                  class="text-sm transition-all duration-300"
                  :class="uiStore.isSidebarCollapsed ? 'opacity-0 w-0' : 'opacity-100'"
                >
                  {{ group.label }}
                </span>
              </div>
              <Icon
                v-if="!uiStore.isSidebarCollapsed"
                icon="ri:arrow-right-s-line"
                class="text-[10px] transition-transform duration-200"
                :class="{ 'rotate-90': openGroups[group.id] }"
              />
            </div>

            <!-- Children Items -->
            <div
              v-show="openGroups[group.id] && !uiStore.isSidebarCollapsed"
              class="mt-1 space-y-1 ml-4 border-l border-gray-100 transition-all duration-300"
            >
              <RouterLink
                v-for="child in group.children"
                :key="child.path"
                :to="child.path"
                custom
                v-slot="{ navigate, isActive }"
              >
                <div
                  class="flex items-center gap-3 px-4 py-2 rounded-r-lg cursor-pointer text-sm transition-all overflow-hidden whitespace-nowrap"
                  :class="isActive ? 'text-green-600 font-bold bg-green-50 border-l-2 border-green-600 -ml-[2px]' : 'text-slate-500 hover:text-green-600 hover:bg-slate-50'"
                  @click="navigate"
                >
                  {{ child.label }}
                </div>
              </RouterLink>
            </div>
          </li>
        </template>
      </ul>
    </nav>

    <!-- Sidebar footer user -->
    <div class="p-4 border-t border-gray-100 overflow-hidden">
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
          <div class="text-slate-800 text-sm font-bold truncate">
            {{ authStore.user?.name || 'User' }}
          </div>
          <div class="text-gray-400 text-[11px] font-medium truncate">
            {{ firstRole }}
          </div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/modules/shared/stores/ui'
import { sidebarMenu } from '@/config/sidebar'

const authStore = useAuthStore()
const uiStore = useUiStore()

// Initialize all groups as open by default
const openGroups = ref(
  sidebarMenu.reduce((acc, group) => {
    if (group.children) {
      acc[group.id] = true
    }
    return acc
  }, {})
)

function toggleGroup(key) {
  openGroups.value[key] = !openGroups.value[key]
}

const userInitial = computed(() => (authStore.user?.name || 'U').charAt(0).toUpperCase())
const firstRole = computed(() => authStore.roles?.[0] || '')
</script>