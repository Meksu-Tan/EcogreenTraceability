<template>
  <div class="p-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
      <div class="flex items-center gap-3 mb-4">
        <Icon icon="ri:file-chart-line" class="text-green-500 text-2xl w-6 h-6" />
        <h1 class="text-2xl font-bold text-slate-800">TS Report</h1>
      </div>
      <p class="text-gray-500 mb-4">
        Traceability System Report - Generate comprehensive traceability reports.
      </p>
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-green-800 text-sm flex items-center gap-2">
          <Icon icon="ri:information-line" class="w-4 h-4" />
          This module is under development. Full functionality will be available soon.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
</script>