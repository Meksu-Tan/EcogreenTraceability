import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])
  let counter  = 0

  function show(message, type = 'success', duration = 5000) {
    const id = ++counter
    toasts.value.push({ id, message, type })
    setTimeout(() => {
      toasts.value = toasts.value.filter(t => t.id !== id)
    }, duration)
  }

  const success = (msg, duration = 5000) => show(msg, 'success', duration)
  const error   = (msg, duration = 5000) => show(msg, 'error', duration)
  const info    = (msg, duration = 5000) => show(msg, 'info', duration)

  return { toasts, success, error, info }
})
