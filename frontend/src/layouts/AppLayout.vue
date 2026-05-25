<template>
  <div class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <AppSidebar />

    <!-- Main Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Topbar -->
      <AppTopbar />

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-6 lg:p-8">
        <router-view :key="route.path" />
      </main>
    </div>
  </div>

  <!-- Global Toast -->
  <AppToast />
</template>

<script setup>
import { AppSidebar, AppTopbar, AppToast } from '@/modules/shared/components'
import { useRoute } from 'vue-router'

const route = useRoute()
</script>
