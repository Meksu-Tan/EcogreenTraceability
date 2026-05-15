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
        <router-view />
      </main>
    </div>
  </div>

  <!-- Global Toast -->
  <AppToast />
</template>

<script setup>
import AppSidebar from '@/components/shared/AppSidebar.vue'
import AppTopbar  from '@/components/shared/AppTopbar.vue'
import AppToast   from '@/components/shared/AppToast.vue'
</script>
