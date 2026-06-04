<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950 relative">
    <!-- Sidebar -->
    <AppSidebar />

    <!-- Backdrop Overlay -->
    <div
      v-if="!uiStore.isSidebarCollapsed"
      @click="uiStore.closeSidebar()"
      class="fixed inset-0 bg-black/50 z-40 transition-opacity duration-300 cursor-pointer"
    ></div>

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
import AppSidebar from '@/modules/shared/components/AppSidebar.vue'
import AppTopbar from '@/modules/shared/components/AppTopbar.vue'
import AppToast from '@/modules/shared/components/AppToast.vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/modules/shared/stores/ui'
import { watch, onMounted, onBeforeUnmount } from 'vue'

const route = useRoute()
const uiStore = useUiStore()

let observer = null

onMounted(() => {
  const checkScrollLock = () => {
    // Check if any modal backdrop is present in the DOM
    const hasBackdrop = Array.from(document.querySelectorAll('*')).some(el => {
      if (!el.className || typeof el.className !== 'string') return false
      return el.classList.contains('modal-backdrop') || el.className.includes('bg-black/')
    })

    // Check if sidebar is currently open (not collapsed)
    const isSidebarOpen = !uiStore.isSidebarCollapsed

    if (hasBackdrop || isSidebarOpen) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  }

  // Observe all DOM changes (modals dynamically added/removed)
  observer = new MutationObserver(checkScrollLock)
  observer.observe(document.body, { childList: true, subtree: true })

  // Also check when sidebar toggle state changes
  watch(() => uiStore.isSidebarCollapsed, checkScrollLock)
})

onBeforeUnmount(() => {
  if (observer) observer.disconnect()
  document.body.style.overflow = ''
})
</script>
