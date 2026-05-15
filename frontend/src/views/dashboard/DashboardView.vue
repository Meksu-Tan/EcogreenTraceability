<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-400">Home</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Dashboard</span>
        </div>
      </div>
    </div>

    <!-- Welcome card -->
    <div class="relative overflow-hidden bg-green-600 rounded-2xl shadow-lg shadow-green-100">
      <!-- Decorative circles -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-2xl"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full -ml-10 -mb-10 blur-xl"></div>
      
      <div class="relative px-8 py-10 flex items-center justify-between gap-6 flex-wrap">
        <div class="space-y-2">
          <h4 class="text-white text-2xl font-extrabold tracking-tight">
            Selamat datang, {{ authStore.user?.name || 'User' }}! 👋
          </h4>
          <p class="text-green-50 text-sm font-medium opacity-90">
            Logged in as <span class="bg-white/20 px-2 py-0.5 rounded-md font-bold">{{ firstRole }}</span> &mdash; {{ today }}
          </p>
        </div>
        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md border border-white/30 shadow-lg">
          <i class="fas fa-user text-white text-2xl"></i>
        </div>
      </div>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 transition-all hover:shadow-md hover:-translate-y-1 group" v-for="stat in stats" :key="stat.label">
        <div 
          class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 text-white shadow-lg transition-transform group-hover:scale-110" 
          :class="stat.bgClass"
        >
          <i :class="stat.icon" class="text-lg"></i>
        </div>
        <div class="min-w-0">
          <div class="text-2xl font-extrabold text-slate-800 leading-tight">—</div>
          <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-0.5">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- Quick navigation -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/30">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-th-large text-green-600"></i>
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
            <i :class="link.icon" class="text-xl text-white"></i>
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
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const firstRole = computed(() => authStore.roles?.[0] || 'User')
const today     = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const stats = [
  { label: 'Material Setup',  bgClass: 'bg-blue-500',   icon: 'fab fa-asymmetrik' },
  { label: 'Storage Setup',   bgClass: 'bg-green-600',  icon: 'fas fa-database' },
  { label: 'Supplier Setup',  bgClass: 'bg-amber-500',  icon: 'fas fa-diagnoses' },
  { label: 'Total Pengguna',  bgClass: 'bg-slate-700',  icon: 'fas fa-users' },
]

const quickLinks = [
  { to: '/setup/material', label: 'Setup Material', bgClass: 'bg-blue-500',   icon: 'fab fa-asymmetrik' },
  { to: '/setup/storage',  label: 'Setup Storage',  bgClass: 'bg-green-600',  icon: 'fas fa-database' },
  { to: '/setup/supplier', label: 'Setup Supplier', bgClass: 'bg-amber-500',  icon: 'fas fa-diagnoses' },
]
</script>
