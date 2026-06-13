<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useTheme } from 'vuetify'

const theme = useTheme()

onMounted(() => {
  const saved = localStorage.getItem('eods-theme')
  if (saved === 'eco' || saved === 'ecoDark') {
    theme.change(saved)
  }
})
</script>
