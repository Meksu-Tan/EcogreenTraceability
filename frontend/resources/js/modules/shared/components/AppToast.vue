<template>
  <Teleport to="body">
    <div class="fixed top-6 right-6 z-[2000] flex flex-col gap-3 max-w-xs w-full pointer-events-none">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-x-12 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-12 opacity-0"
      >
        <div
          v-for="t in toastStore.toasts"
          :key="t.id"
          class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl border backdrop-blur-md"
          :class="{
            'bg-green-600/90 border-green-500 text-white': t.type === 'success',
            'bg-red-600/90 border-red-500 text-white': t.type === 'error',
            'bg-slate-800/90 border-slate-700 text-white': t.type === 'info',
          }"
        >
          <div class="flex-shrink-0 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
            <Icon :icon="iconClass(t.type)" />
          </div>
          <p class="text-sm font-bold tracking-tight">{{ t.message }}</p>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()

function iconClass(type) {
  return {
    success: 'ri:check-line',
    error:   'ri:error-warning-line',
    info:    'ri:information-line',
  }[type] || 'ri:information-line'
}
</script>