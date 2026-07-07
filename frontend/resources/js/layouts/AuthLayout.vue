<template>
  <VApp>
    <VMain class="auth-main">
      <VContainer class="d-flex align-center justify-center" style="min-height: 100vh;">
        <div class="auth-wrapper w-100" style="max-width: 440px;">
          <!-- Logo -->
          <div class="text-center mb-6">
            <VImg
              src="/logo-stacked.jpg"
              alt="Ecogreen Logo"
              width="72"
              height="72"
              class="mx-auto mb-3"
              style="object-fit: contain;"
            />
            <h1 class="text-h5 font-weight-black text-on-surface">PTEO EUDR-TS</h1>
            <p class="text-caption text-medium-emphasis mt-1">&copy; AnD202406090 PT. Ecogreen Oleochemicals</p>
          </div>

          <!-- Card -->
          <VCard rounded="lg" elevation="2" class="pa-8">
            <RouterView />
          </VCard>

          <!-- Footer -->
          <p class="text-center text-caption text-medium-emphasis mt-6">
<!--            &copy; 2026 PT Ecogreen Oleochemicals -->
          </p>
        </div>
      </VContainer>
    </VMain>
  </VApp>
</template>

<script setup>
</script>

<style scoped>
.auth-main {
  background: rgb(var(--v-theme-background));
}
</style>