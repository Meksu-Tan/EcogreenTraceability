<template>
  <aside class="app-sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="sidebar-brand-logo">
        <i class="fas fa-layer-group"></i>
      </div>
      <div>
        <span class="sidebar-brand-name">EUDR-TS</span>
        <span class="sidebar-brand-sub">EO Trace System</span>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <!-- Dashboard -->
      <ul style="list-style:none;padding:0;margin:0;">
        <li class="menu-header">Dashboard</li>
        <li>
          <RouterLink to="/dashboard" custom v-slot="{ navigate, isActive }">
            <div class="nav-item" :class="{ active: isActive }" @click="navigate">
              <i class="fas fa-th-large"></i>
              <span>Dashboard</span>
            </div>
          </RouterLink>
        </li>

        <!-- TS Setup -->
        <li class="menu-header">TS Setup</li>

        <!-- Setup Material -->
        <li>
          <div
            class="nav-group-header"
            :class="{ open: openGroups.setup }"
            @click="toggleGroup('setup')"
          >
            <div class="header-left">
              <i class="fab fa-asymmetrik"></i>
              <span>Setup</span>
            </div>
            <i class="fas fa-chevron-right chevron"></i>
          </div>
          <div class="nav-group-children" :class="{ open: openGroups.setup }">
            <RouterLink to="/setup/material" custom v-slot="{ navigate, isActive }">
              <div class="nav-group-child" :class="{ active: isActive }" @click="navigate">
                Material
              </div>
            </RouterLink>
            <RouterLink to="/setup/storage" custom v-slot="{ navigate, isActive }">
              <div class="nav-group-child" :class="{ active: isActive }" @click="navigate">
                Storage
              </div>
            </RouterLink>
            <RouterLink to="/setup/supplier" custom v-slot="{ navigate, isActive }">
              <div class="nav-group-child" :class="{ active: isActive }" @click="navigate">
                Supplier
              </div>
            </RouterLink>
          </div>
        </li>
      </ul>
    </nav>

    <!-- Sidebar footer user -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">{{ userInitial }}</div>
        <div style="min-width:0;">
          <div class="sidebar-user-name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ authStore.user?.name || 'User' }}
          </div>
          <div class="sidebar-user-role">{{ firstRole }}</div>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore  = useAuthStore()
const openGroups = ref({ setup: true })

function toggleGroup(key) {
  openGroups.value[key] = !openGroups.value[key]
}

const userInitial = computed(() => (authStore.user?.name || 'U').charAt(0).toUpperCase())
const firstRole   = computed(() => authStore.roles?.[0] || '')
</script>
