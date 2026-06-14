<template>
  <div>
    <VSnackbar
      v-for="t in toastStore.toasts"
      :key="t.id"
      :model-value="true"
      location="top right"
      :color="snackbarColor(t.type)"
      rounded="lg"
      elevation="2"
      class="mb-1"
      :timeout="-1"
    >
      <div class="d-flex align-center gap-2">
        <VIcon :icon="snackbarIcon(t.type)" size="20" />
        <span class="text-body-2 font-weight-medium">{{ t.message }}</span>
      </div>
    </VSnackbar>
  </div>
</template>

<script setup>
import { useToastStore } from '@/stores/toast.js'

const toastStore = useToastStore()

function snackbarColor(type) {
  return { success: 'success', error: 'error', info: 'info' }[type] || 'info'
}

function snackbarIcon(type) {
  return {
    success: 'ri-checkbox-circle-line',
    error:   'ri-error-warning-line',
    info:    'ri-information-line',
  }[type] || 'ri-information-line'
}
</script>