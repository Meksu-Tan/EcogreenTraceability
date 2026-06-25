<template>
  <div v-if="authStore.initializing" class="loading-overlay">
    <div class="text-center loading-content">
      <div class="logo-wrapper">
        <img
          src="/logo-stacked.jpg"
          alt="Ecogreen Logo"
          class="loading-logo"
        />
        <div class="pulse-glow"></div>
        <div class="spinner-ring"></div>
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
  animation: logo-bounce 2s infinite ease-in-out;
}

.pulse-glow {
  position: absolute;
  width: 80px;
  height: 80px;
  border-radius: 18px;
  background-color: rgb(var(--v-theme-primary));
  filter: blur(8px);
  z-index: 1;
  animation: pulse-glow-anim 2s ease-in-out infinite;
}

.spinner-ring {
  position: absolute;
  width: 96px;
  height: 96px;
  border-radius: 24px;
  border: 3px solid transparent;
  border-top-color: rgb(var(--v-theme-primary));
  border-right-color: rgb(var(--v-theme-primary));
  z-index: 3;
  animation: spin-anim 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

.text-pulse {
  animation: text-glow 2s ease-in-out infinite alternate;
  letter-spacing: 1.5px;
}

.text-pulse-sub {
  opacity: 0.8;
  animation: text-fade 2s ease-in-out infinite alternate;
}

@keyframes logo-bounce {
  0%, 100% {
    transform: translateY(0) scale(1);
  }
  50% {
    transform: translateY(-4px) scale(1.02);
  }
}

@keyframes pulse-glow-anim {
  0%, 100% {
    transform: scale(0.9);
    opacity: 0.2;
  }
  50% {
    transform: scale(1.15);
    opacity: 0.45;
  }
}

@keyframes spin-anim {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes text-glow {
  0% {
    text-shadow: 0 0 0px rgba(var(--v-theme-primary), 0);
  }
  100% {
    text-shadow: 0 0 10px rgba(var(--v-theme-primary), 0.3);
  }
}

@keyframes text-fade {
  0% {
    opacity: 0.5;
  }
  100% {
    opacity: 0.9;
  }
}
</style>
