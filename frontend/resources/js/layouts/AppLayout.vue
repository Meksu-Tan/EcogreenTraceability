<template>
  <VApp class="layout-wrapper">
    <!-- Sidebar -->
    <VNavigationDrawer
      v-model="drawer"
      :rail="rail"
      permanent
      color="surface"
      :border="0"
      class="app-sidebar"
    >
      <!-- Logo -->
      <div class="sidebar-logo d-flex align-center px-4 gap-3">
        <img src="/logo-stacked.jpg" alt="EUDR-TS" class="sidebar-logo-img" />
        <Transition name="fade-slide">
          <div v-if="!rail" class="d-flex flex-column">
            <span class="text-primary font-weight-bold text-body-1" style="white-space: nowrap;">EUDR-TS</span>
            <span class="text-caption text-medium-emphasis" style="white-space: nowrap;">EO Trace System</span>
          </div>
        </Transition>
      </div>

      <VDivider />

      <!-- Navigation Items -->
      <NavItems :rail="rail" />

      <!-- Collapse toggle -->
      <template #append>
        <VDivider />
        <div class="pa-2">
          <VBtn
            :icon="rail ? 'ri-arrow-right-s-line' : 'ri-arrow-left-s-line'"
            variant="text"
            color="medium-emphasis"
            size="small"
            block
            @click="rail = !rail"
          />
        </div>
      </template>
    </VNavigationDrawer>

    <!-- App Bar -->
    <VAppBar flat color="surface" border="b" :height="64">
      <VAppBarNavIcon class="d-md-none" @click="drawer = !drawer" />

      <VToolbarTitle>
        <span class="text-body-2 text-medium-emphasis">{{ pageTitle }}</span>
      </VToolbarTitle>

      <VSpacer />

      <LayoutThemeSwitcher />
      <LayoutUserProfile />
    </VAppBar>

    <!-- Main Content -->
    <VMain>
      <div class="page-content pa-6">
        <RouterView />
      </div>
    </VMain>
  </VApp>

  <!-- Global Toast -->
  <AppToast />
  <ConfirmDialog
    v-model="confirmStore.isOpen"
    :title="confirmStore.title"
    :message="confirmStore.message"
    :icon="confirmStore.icon"
    :color="confirmStore.color"
    :confirm-text="confirmStore.confirmText"
    :cancel-text="confirmStore.cancelText"
    :loading="confirmStore.loading"
    @confirm="confirmStore.confirm"
    @cancel="confirmStore.cancel"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import NavItems from './components/NavItems.vue'
import LayoutThemeSwitcher from './components/ThemeSwitcher.vue'
import LayoutUserProfile from './components/UserProfile.vue'
import AppToast from '@/modules/shared/components/AppToast.vue'
import ConfirmDialog from '@/modules/shared/components/ConfirmDialog.vue'
import { useConfirmStore } from '@/stores/confirm.js'

const confirmStore = useConfirmStore()
const drawer = ref(true)
const rail   = ref(false)
const route  = useRoute()

const pageTitle = computed(() => {
  const map = {
    '/dashboard':              'Dashboard',
    '/setup/material':         'Setup Material',
    '/setup/storage':          'Setup Storage',
    '/setup/supplier':         'Setup Supplier',
    '/setup/plant':            'Setup Plant',
    '/setup/tank':             'Setup Tank',
    '/setup/manufacturer':     'Setup Manufacturer',
    '/admin/user-management':  'User Management',
    '/ts-raw/rm-entry':        'Raw Material Entry',
    '/ts-wip/wip-entry':       'WIP Entry',
    '/ts-blending/blending':   'Blending',
    '/ts-transfer/transfer':   'Transfer',
    '/ts-package/package-entry': 'Packaging',
    '/ts-shipment/shipment-entry': 'Shipment',
  }
  return map[route.path] || 'EUDR-TS'
})
</script>

<style scoped>
.layout-wrapper { min-block-size: 100vh; }

.app-sidebar { border-right: 1px solid rgb(var(--v-theme-neutral-200)); }

.sidebar-logo {
  min-block-size: 64px;
  display: flex;
  align-items: center;
}

.sidebar-logo-img {
  block-size: 36px;
  inline-size: 36px;
  object-fit: contain;
  border-radius: 6px;
  flex-shrink: 0;
}

.page-content {
  background: rgb(var(--v-theme-background));
  min-block-size: 100%;
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
