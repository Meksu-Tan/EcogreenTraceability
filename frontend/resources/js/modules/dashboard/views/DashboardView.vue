<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-400">Home</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-500">Dashboard</span>
        </div>
      </div>
    </div>

    <!-- Welcome card -->
    <div class="relative overflow-hidden bg-green-500 rounded-2xl shadow-lg">
      <!-- Decorative circles -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-2xl"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full -ml-10 -mb-10 blur-xl"></div>

      <div class="relative px-8 py-10 flex items-center justify-between gap-6 flex-wrap">
        <div class="space-y-2">
          <h4 class="text-white text-2xl font-extrabold tracking-tight">
            Selamat datang, {{ authStore.user?.name || 'User' }}!
          </h4>
          <p class="text-green-50 text-sm font-medium opacity-90">
            Logged in as <span class="bg-white/20 px-2 py-0.5 rounded-md font-bold">{{ firstRole }}</span> &mdash; {{ today }}
          </p>
        </div>
        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md border border-white/30 shadow-lg">
          <Icon icon="ri:user-3-line" class="text-white text-2xl w-8 h-8" />
        </div>
      </div>
    </div>

    <!-- Quick navigation -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/30">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <Icon icon="ri:dashboard-line" class="text-green-500 w-4 h-4" />
          Quick Navigation
        </h4>
      </div>
      <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <RouterLink
          v-for="link in quickLinks"
          :key="link.to"
          :to="link.to"
          class="group flex flex-col items-center p-6 border-2 border-slate-50 rounded-2xl hover:border-green-100 hover:bg-green-50/50 transition-all active:scale-95 text-center"
        >
          <div
            class="w-14 h-14 rounded-xl flex items-center justify-center mb-4 shadow-md transition-transform group-hover:scale-110 group-hover:rotate-3"
            :class="link.bgClass"
          >
            <Icon :icon="link.icon" class="text-xl text-white w-6 h-6" />
          </div>
          <span class="text-sm font-bold text-slate-700 group-hover:text-green-700">{{ link.label }}</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const firstRole = computed(() => authStore.roles?.[0] || 'User')
const today = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const quickLinks = [
  { to: '/setup/material',     label: 'Setup Material',     bgClass: 'bg-green-500', icon: 'ri:flask-line' },
  { to: '/setup/storage',     label: 'Setup Storage',     bgClass: 'bg-green-500', icon: 'ri:database-2-line' },
  { to: '/setup/supplier',    label: 'Setup Supplier',     bgClass: 'bg-green-500', icon: 'ri:truck-line' },
  { to: '/setup/tank',        label: 'Setup Tank',          bgClass: 'bg-green-600', icon: 'ri:water-flash-line' },
  { to: '/setup/manufacturer', label: 'Setup Manufacturer', bgClass: 'bg-green-600', icon: 'ri:factory-line' },
  { to: '/setup/plant',        label: 'Setup Plant',         bgClass: 'bg-green-700', icon: 'ri:building-4-line' },
]
</script>