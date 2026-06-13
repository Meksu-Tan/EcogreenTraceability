<template>
  <VBtn
    icon
    variant="text"
    @click="toggle"
  >
    <VIcon :icon="isDark ? 'ri-sun-line' : 'ri-moon-line'" />
    <VTooltip activator="parent" location="bottom" open-delay="800">
      {{ isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode' }}
    </VTooltip>
  </VBtn>
</template>

<script setup>
import { computed } from 'vue'
import { useTheme } from 'vuetify'

const theme   = useTheme()
const isDark  = computed(() => theme.global.current.value.dark)

function toggle() {
  const nextTheme = isDark.value ? 'eco' : 'ecoDark'
  theme.change(nextTheme)
  localStorage.setItem('eods-theme', nextTheme)
}
</script>
