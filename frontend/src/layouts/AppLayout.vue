<template>
  <div class="app-shell">
    <!-- Sidebar -->
    <AppSidebar />

    <!-- Main Area -->
    <div class="app-main">
      <!-- Topbar -->
      <AppTopbar />

      <!-- Page Content -->
      <main class="app-content">
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
