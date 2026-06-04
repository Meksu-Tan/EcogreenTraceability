import { defineStore } from 'pinia'

const getStoredTheme = () => {
  if (typeof localStorage === 'undefined') return 'light'

  return localStorage.getItem('theme') || 'light'
}

export const useUiStore = defineStore('ui', {
  state: () => ({
    isSidebarCollapsed: true,
    theme: getStoredTheme(),
  }),
  actions: {
    toggleSidebar() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed
    },
    closeSidebar() {
      this.isSidebarCollapsed = true
    },
    toggleTheme() {
      this.theme = this.theme === 'dark' ? 'light' : 'dark'
      localStorage.setItem('theme', this.theme)
    }
  }
})
