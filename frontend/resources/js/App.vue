<template>
  <div v-if="authStore.initializing" class="loading-overlay">
    <div class="text-center loading-content">
      <div class="logo-wrapper">
        <img
          src="/logo-stacked.jpg"
          alt="Ecogreen Logo"
          class="loading-logo"
        />
      </div>
      <h2 class="text-h6 font-weight-black text-on-surface mt-6 text-pulse">EUDR-TS</h2>
      <p class="text-caption text-medium-emphasis mt-1 text-pulse-sub">Enterprise Operations Data System</p>
    </div>
  </div>
  <RouterView v-else />
</template>

<script setup>
import { onMounted } from 'vue'
import { useTheme } from 'vuetify'
import { useAuthStore } from '@/stores/auth.js'

const theme = useTheme()
const authStore = useAuthStore()

onMounted(() => {
  const saved = localStorage.getItem('eods-theme')
  if (saved === 'eco' || saved === 'ecoDark') {
    theme.change(saved)
  }
})
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at center, rgb(var(--v-theme-surface)) 0%, rgb(var(--v-theme-background)) 100%);
  z-index: 9999;
}

.loading-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.logo-wrapper {
  position: relative;
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading-logo {
  width: 76px;
  height: 76px;
  border-radius: 16px;
  object-fit: contain;
  z-index: 2;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  /* ponytail: removed refresh animation */
}


.text-pulse {
  letter-spacing: 1.5px;
}

.text-pulse-sub {
  opacity: 0.8;
}

</style>
