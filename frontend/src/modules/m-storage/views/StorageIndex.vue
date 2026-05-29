<template>
  <div class="space-y-6">
    <!-- Section header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Setup Storage</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-sm text-gray-500">TS Setup</span>
          <span class="text-gray-300">/</span>
          <span class="text-sm font-semibold text-green-600">Storage</span>
        </div>
      </div>
      <button
        id="btn-tambah-storage"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-bold text-sm transition-all flex items-center gap-2 shadow-sm active:scale-95"
        @click="openModal"
      >
        <i class="fas fa-plus"></i> Tambah
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between gap-4 flex-wrap">
        <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
          <i class="fas fa-database text-green-600"></i>
          Data Storage
        </h4>
        <div class="flex bg-slate-100 p-1 rounded-lg">
          <button
            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
            :class="activeTab==='tank' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            @click="activeTab='tank'"
          >
            Storage Tank
          </button>
          <button
            class="px-4 py-1.5 text-xs font-bold rounded-md transition-all"
            :class="activeTab==='warehouse' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            @click="activeTab='warehouse'"
          >
            Warehouse
          </button>
        </div>
      </div>
      <div class="p-6">
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