<template>
  <aside class="w-64 bg-white border-r border-gray-200 min-h-screen flex flex-col flex-shrink-0 z-50">
    <!-- Brand -->
    <div class="h-16 px-6 flex items-center border-b border-gray-100 gap-3">
      <div class="w-10 h-10 flex items-center justify-center flex-shrink-0">
        <img src="@/assets/logo.png" alt="Logo" class="w-full h-full object-contain" />
      </div>
      <div class="flex flex-col leading-tight">
        <span class="text-slate-800 text-base font-bold tracking-tight">EUDR-TS</span>
        <span class="text-gray-400 text-[10px] font-medium uppercase">EO Trace System</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 overflow-y-auto">
      <ul class="space-y-1 px-3">
        <!-- Dynamic Menu Groups -->
        <template v-for="group in sidebarMenu" :key="group.id">
          <!-- Group Label -->
          <li class="px-3 py-2 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest" :class="{ 'mt-6 mb-1': group.id !== 'main' }">
            {{ group.label }}
          </li>

          <!-- Simple Items (no children) -->
          <template v-if="group.items">
            <li v-for="item in group.items" :key="item.path">
              <RouterLink :to="item.path" custom v-slot="{ navigate, isActive }">
                <div 
                  class="group flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-all"
                  :class="isActive ? 'bg-green-50 text-green-600 font-bold shadow-sm shadow-green-100/50' : 'text-slate-600 hover:bg-slate-50 hover:text-green-600'"
                  @click="navigate"
                >
                  <i 
                    class="fas w-5 text-center text-sm" 
                    :class="[item.icon, isActive ? 'text-green-600' : 'text-slate-400 group-hover:text-green-600']"
                  ></i>
                  <span class="text-sm">{{ item.label }}</span>
                </div>
              </RouterLink>
            </li>
          </template>

          <!-- Collapsible Group (with children) -->
          <li v-if="group.children">
            <div
              class="flex items-center justify-between px-3 py-2.5 rounded-lg cursor-pointer transition-all text-slate-600 hover:bg-slate-50 hover:text-green-600"
              :class="{ 'bg-slate-50/50 text-green-600 font-bold': openGroups[group.id] }"
              @click="toggleGroup(group.id)"
            >
              <div class="flex items-center gap-3">
                <i 
                  class="fas w-5 text-center text-sm" 
                  :class="[group.icon, openGroups[group.id] ? 'text-green-600' : 'text-slate-400']"
                ></i>
                <span class="text-sm">{{ group.label }}</span>
              </div>
              <i 
                class="fas fa-chevron-right text-[10px] transition-transform duration-200" 
                :class="{ 'rotate-90': openGroups[group.id] }"
              ></i>
            </div>
            
            <!-- Children Items -->
            <div v-show="openGroups[group.id]" class="mt-1 space-y-1 ml-4 border-l border-gray-100">
              <RouterLink 
                v-for="child in group.children" 
                :key="child.path" 
                :to="child.path" 
                custom 
                v-slot="{ navigate, isActive }"
              >
                <div 
                  class="flex items-center gap-3 px-4 py-2 rounded-r-lg cursor-pointer text-sm transition-all"
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
    <div class="p-4 border-t border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-green-600 flex items-center justify-center text-white text-sm font-bold shadow-sm">
          {{ userInitial }}
        </div>
        <div class="min-w-0">
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
import { useAuthStore } from '@/stores/auth'
import { sidebarMenu } from '@/config/sidebar'

const authStore = useAuthStore()

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
