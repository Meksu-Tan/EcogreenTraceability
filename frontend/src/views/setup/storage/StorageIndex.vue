<template>
  <div>
    <!-- Section header -->
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;">
      <div>
        <h1 style="font-size:18px;font-weight:700;color:var(--text-main);margin:0 0 4px;">Setup Storage</h1>
        <div class="section-header-breadcrumb">
          <div class="breadcrumb-item">TS Setup</div>
          <div class="breadcrumb-item" style="color:var(--primary);">Storage</div>
        </div>
      </div>
      <button id="btn-tambah-storage" class="btn btn-primary" @click="openModal">
        <i class="fas fa-plus"></i> Tambah
      </button>
    </div>

    <div class="card card-primary">
      <div class="card-header">
        <h4><i class="fas fa-database" style="color:var(--primary);margin-right:8px;"></i>Data Storage</h4>
        <div class="card-header-action">
          <div class="nav-tabs" style="border:none;margin:0;">
            <button class="nav-tab" :class="{ active: activeTab==='tank' }" @click="activeTab='tank'">Storage Tank</button>
            <button class="nav-tab" :class="{ active: activeTab==='warehouse' }" @click="activeTab='warehouse'">Warehouse</button>
          </div>
        </div>
      </div>
      <div class="card-body">
        <StorageDetailTab v-if="activeTab==='tank'" ref="tankTabRef" />
        <WarehouseTab     v-if="activeTab==='warehouse'" ref="warehouseTabRef" />
      </div>
    </div>
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
