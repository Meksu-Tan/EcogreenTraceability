<template>
  <VDialog v-model="isOpen" max-width="480" persistent>
    <VCard rounded="lg">
      <!-- Header -->
      <VCardTitle class="d-flex align-center gap-3 pa-5 pb-3">
        <VIcon icon="ri-factory-line" color="primary" />
        <span class="text-h6 font-weight-bold">Select Transaction Plant</span>
      </VCardTitle>
      <VCardSubtitle class="pa-5 pt-2 pb-3">
        Please select a plant to view transaction data on this page.
      </VCardSubtitle>

      <VDivider />

      <!-- Plant List -->
      <VCardText class="pa-3" style="max-height: 60vh; overflow-y: auto;">
        <VList lines="two" density="compact">
          <!-- All Plants -->
          <VListItem
            :active="plantSelectionStore.selectedPlantId === null"
            base-color="primary"
            rounded="lg"
            class="mb-2"
            @click="selectPlant(null, 'All Plants')"
          >
            <template #prepend>
              <VAvatar color="primary-lighten-2" rounded="lg" size="44">
                <VIcon icon="ri-layout-grid-line" color="primary" />
              </VAvatar>
            </template>
            <VListItemTitle class="font-weight-bold">All Plants</VListItemTitle>
            <VListItemSubtitle>Show data from all locations</VListItemSubtitle>
            <template #append>
              <VIcon
                v-if="plantSelectionStore.selectedPlantId === null"
                icon="ri-checkbox-circle-line"
                color="primary"
              />
            </template>
          </VListItem>

          <!-- Individual Plants -->
          <VListItem
            v-for="plant in plantStore.plants"
            :key="plant.id_plant"
            :active="plantSelectionStore.selectedPlantId === plant.id_plant"
            base-color="primary"
            rounded="lg"
            class="mb-2"
            @click="selectPlant(plant.id_plant, plant.description)"
          >
            <template #prepend>
              <VAvatar color="primary-lighten-2" rounded="lg" size="44">
                <VIcon icon="ri-building-4-line" color="primary" />
              </VAvatar>
            </template>
            <VListItemTitle class="font-weight-bold">{{ plant.description }}</VListItemTitle>
            <VListItemSubtitle>ID Plant: {{ plant.id_plant }}</VListItemSubtitle>
            <template #append>
              <VIcon
                v-if="plantSelectionStore.selectedPlantId === plant.id_plant"
                icon="ri-checkbox-circle-line"
                color="primary"
              />
            </template>
          </VListItem>
        </VList>
      </VCardText>

      <!-- Footer -->
      <VDivider v-if="!isMandatory" />
      <VCardActions v-if="!isMandatory" class="pa-5 pt-3 justify-end gap-2">
        <VBtn variant="outlined" color="medium-emphasis" @click="close">Cancel</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePlantSelectionStore, useSetupPlantStore } from '@/stores/plant.js'

defineProps({
  isMandatory: { type: Boolean, default: true },
})

const emit = defineEmits(['selected', 'close'])

const plantSelectionStore = usePlantSelectionStore()
const plantStore          = useSetupPlantStore()
const isOpen              = ref(false)

function open()  { isOpen.value = true }
function close() { isOpen.value = false; emit('close') }

function selectPlant(id, name) {
  plantSelectionStore.setPlant(id, name)
  isOpen.value = false
  emit('selected', id)
}

onMounted(async () => {
  if (plantStore.plants.length === 0) await plantStore.fetchPlants()
})

defineExpose({ open, close })
</script>