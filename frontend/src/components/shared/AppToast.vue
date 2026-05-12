<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast-anim">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="toast"
          :class="`toast-${t.type}`"
        >
          <i :class="iconClass(t.type)"></i>
          {{ t.message }}
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToastStore } from '@/stores/toast'
const { toasts } = useToastStore()
function iconClass(type) {
  return {
    success: 'fas fa-check-circle',
    error:   'fas fa-times-circle',
    info:    'fas fa-info-circle',
  }[type] || 'fas fa-info-circle'
}
</script>

<style scoped>
.toast-anim-enter-active, .toast-anim-leave-active { transition: all .25s ease; }
.toast-anim-enter-from { transform: translateX(100%); opacity: 0; }
.toast-anim-leave-to   { transform: translateX(100%); opacity: 0; }
</style>
