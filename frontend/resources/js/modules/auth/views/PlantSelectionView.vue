<template>
  <VContainer class="py-10">
    <div class="text-center mb-8">
      <h1 class="text-h5 font-weight-bold mb-2">Select Your Plant</h1>
      <p class="text-body-2 text-medium-emphasis">Please choose a plant to start managing transactions</p>
    </div>

    <div v-if="loading" class="d-flex justify-center py-12">
      <VProgressCircular indeterminate color="primary" size="48" />
    </div>

    <VRow v-else>
      <!-- All Plants -->
      <VCol cols="12" sm="6" md="4">
        <VCard
          rounded="lg"
          elevation="1"
          class="plant-card cursor-pointer"
          @click="selectPlant(null, 'All Plants')"
        >
          <VCardText class="pa-6">
            <VAvatar color="neutral-100" size="48" rounded="lg" class="mb-4">
              <VIcon icon="ri-layout-grid-line" color="primary" />
            </VAvatar>
            <h3 class="text-body-1 font-weight-bold mb-1">All Plants</h3>
            <p class="text-caption text-medium-emphasis">View transactions from all available plants</p>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Individual Plants -->
      <VCol v-for="plant in plants" :key="plant.id_plant" cols="12" sm="6" md="4">
        <VCard
          rounded="lg"
          elevation="1"
          class="plant-card cursor-pointer"
          @click="selectPlant(plant.id_plant, plant.description, plant.code_3)"
        >
          <VCardText class="pa-6">
            <VAvatar color="primary-lighten-2" size="48" rounded="lg" class="mb-4">
              <VIcon icon="ri-building-4-line" color="primary" />
            </VAvatar>
            <h3 class="text-body-1 font-weight-bold mb-1">{{ plant.description }}</h3>
            <p class="text-caption text-medium-emphasis">{{ plant.code }} - {{ plant.code_2 }}</p>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSetupPlantStore, usePlantSelectionStore } from '@/stores/plant.js'
import { storeToRefs } from 'pinia'

const router         = useRouter()
const plantStore     = useSetupPlantStore()
const selectionStore = usePlantSelectionStore()
const { plants, loading } = storeToRefs(plantStore)

onMounted(async () => {
  await plantStore.fetchPlants()
})

function selectPlant(id, name, code = '') {
  selectionStore.setPlant(id, name, code)
  router.push('/dashboard')
}
</script>

<style scoped>
.plant-card {
  border: 1px solid rgb(var(--v-theme-neutral-200));
  transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.plant-card:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgb(var(--v-theme-primary) / 0.15) !important;
}
</style>