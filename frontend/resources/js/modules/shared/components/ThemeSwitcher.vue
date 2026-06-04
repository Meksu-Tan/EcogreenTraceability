<template>
  <button
    @click="toggleTheme"
    class="w-10 h-10 flex items-center justify-center text-white hover:bg-white/10 rounded-lg transition-all"
    :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
  >
    <Icon :icon="isDark ? 'ri:sun-line' : 'ri:moon-line'" class="w-5 h-5 text-white" />
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useUiStore } from '@/modules/shared/stores/ui'

const uiStore = useUiStore()

const isDark = computed(() => uiStore.theme === 'dark')

const toggleTheme = () => {
  uiStore.toggleTheme()
  updateHtmlClass(uiStore.theme)
}

const updateHtmlClass = (currentTheme) => {
  if (currentTheme === 'dark') {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

// Sync initial class with stored theme
updateHtmlClass(uiStore.theme)
</script>
