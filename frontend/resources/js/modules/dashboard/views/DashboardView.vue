<template>
  <div>
    <!-- Page header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Dashboard</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">Home</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Dashboard</span>
        </div>
      </div>
    </div>

    <!-- Welcome card -->
    <VCard color="primary" rounded="lg" elevation="1" class="mb-6 overflow-hidden">
      <VCardText class="pa-8 d-flex align-center justify-space-between flex-wrap gap-4">
        <div>
          <h2 class="text-h6 font-weight-black text-on-primary mb-2">
            Selamat datang, {{ authStore.user?.name || 'User' }}!
          </h2>
          <p class="text-body-2 text-on-primary mb-0" style="opacity: 0.9;">
            Logged in as
            <VChip size="x-small" variant="tonal" color="on-primary" class="mx-1">{{ firstRole }}</VChip>
            &mdash; {{ today }}
          </p>
        </div>
        <VAvatar color="rgb(var(--v-theme-primary) / 0.2)" size="64" style="border: 2px solid rgb(var(--v-theme-primary) / 0.3);">
          <VIcon icon="ri-user-3-line" size="32" color="on-primary" />
        </VAvatar>
      </VCardText>
    </VCard>

    <!-- Quick Navigation -->
    <VCard rounded="lg" elevation="1" class="mb-2">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-dashboard-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Quick Navigation</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <VRow>
          <VCol
            v-for="link in quickLinks"
            :key="link.to"
            cols="12" sm="6" md="4"
          >
            <VCard
              :to="link.to"
              rounded="lg"
              variant="outlined"
              class="quick-nav-card text-center pa-4"
            >
              <VAvatar color="primary" size="56" rounded="lg" class="mb-3">
                <VIcon :icon="link.icon" size="26" color="on-primary" />
              </VAvatar>
              <p class="text-body-2 font-weight-bold text-on-surface mb-0">{{ link.label }}</p>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.js'

const authStore = useAuthStore()

const firstRole = computed(() => authStore.roles?.[0] || 'User')
const today     = computed(() => new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))

const quickLinks = [
  { to: '/setup/material',      label: 'Setup Material',     icon: 'ri-flask-line' },
  { to: '/setup/storage',       label: 'Setup Storage',      icon: 'ri-database-2-line' },
  { to: '/setup/supplier',      label: 'Setup Supplier',     icon: 'ri-truck-line' },
  { to: '/setup/tank',          label: 'Setup Tank',         icon: 'ri-water-flash-line' },
  { to: '/setup/manufacturer',  label: 'Setup Manufacturer', icon: 'ri-building-3-line' },
  { to: '/setup/plant',         label: 'Setup Plant',        icon: 'ri-building-4-line' },
]
</script>

<style scoped>
.quick-nav-card {
  border-color: rgb(var(--v-theme-neutral-200));
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
  cursor: pointer;
}

.quick-nav-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgb(var(--v-theme-primary) / 0.15) !important;
}
</style>