import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useConfirmStore = defineStore('confirm', () => {
  const isOpen = ref(false)
   const title = ref('Confirmation')
   const message = ref('')
   const icon = ref('ri-error-warning-line')
   const color = ref('error')
   const confirmText = ref('Yes, Continue')
   const cancelText = ref('Cancel')
   const loading = ref(false)
 
   let resolvePromise = null
   let rejectPromise = null
 
   function show(options) {
     title.value = options.title || 'Confirmation'
     message.value = options.message || 'Are you sure?'
     icon.value = options.icon || 'ri-error-warning-line'
     color.value = options.color || 'error'
     confirmText.value = options.confirmText || 'Yes, Continue'
     cancelText.value = options.cancelText || 'Cancel'
    loading.value = false
    isOpen.value = true

    return new Promise((resolve, reject) => {
      resolvePromise = resolve
      rejectPromise = reject
    })
  }

  function confirm() {
    isOpen.value = false
    if (resolvePromise) resolvePromise(true)
  }

  function cancel() {
    isOpen.value = false
    if (resolvePromise) resolvePromise(false)
  }

  function startLoading() {
    loading.value = true
  }

  function stopLoading() {
    loading.value = false
  }

  return {
    isOpen,
    title,
    message,
    icon,
    color,
    confirmText,
    cancelText,
    loading,
    show,
    confirm,
    cancel,
    startLoading,
    stopLoading
  }
})
