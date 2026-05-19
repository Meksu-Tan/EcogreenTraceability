import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const isSidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true')

  function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value
    localStorage.setItem('sidebarCollapsed', isSidebarCollapsed.value)
  }

  function setSidebar(value) {
    isSidebarCollapsed.value = value
    localStorage.setItem('sidebarCollapsed', value)
  }

  return {
    isSidebarCollapsed,
    toggleSidebar,
    setSidebar
  }
})
