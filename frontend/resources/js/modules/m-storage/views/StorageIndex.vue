<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Storage</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Storage</span>
        </div>
      </div>
      <VBtn id="btn-tambah-storage" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
    </div>

    <VCard rounded="lg" elevation="1">
      <div class="d-flex align-center justify-space-between pa-5 pb-3">
        <div class="d-flex align-center gap-2">
          <VIcon icon="ri-database-2-line" color="primary" size="20" />
          <span class="text-body-1 font-weight-bold">Data Storage</span>
        </div>
        <VBtnToggle v-model="activeTab" mandatory rounded="lg" density="compact" color="primary">
          <VBtn value="tank" size="small">Storage Tank</VBtn>
          <VBtn value="warehouse" size="small">Warehouse</VBtn>
        </VBtnToggle>
      </div>
      <VDivider />
      <VCardText class="pa-5">
        <StorageDetailTab v-if="activeTab==='tank'" ref="tankTabRef" />
        <WarehouseTab     v-if="activeTab==='warehouse'" ref="warehouseTabRef" />
      </VCardText>
    </VCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import StorageDetailTab from './StorageDetailTab.vue'
import WarehouseTab     from './WarehouseTab.vue'

const activeTab       = ref('tank')
const tankTabRef      = ref(null)
const warehouseTabRef = ref(null)

function openModal() {
  if (activeTab.value === 'tank')      tankTabRef.value?.openTankModal()
  if (activeTab.value === 'warehouse') warehouseTabRef.value?.openModal()
}
</script>