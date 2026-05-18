<template>
  <FeatureTableView title="RM to PRD Adjustment" :tables="tables" :active-modal="activeModal" @close-modal="activeModal = null">
    <template #toolbar>
      <button v-for="action in actions" :key="action.kind" class="rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" @click="activeModal = { title: action.label, kind: action.kind }">
        <i :class="action.icon" class="mr-2"></i>{{ action.label }}
      </button>
    </template>
    <template #modal="{ modal }">
      <div class="grid gap-4 md:grid-cols-2">
        <Field v-if="modal.kind === 'last'" label="Adjustment No" readonly />
        <Field label="Entry Date" type="date" />
        <Field label="Material" />
        <Field label="SLoc" />
        <Field label="Adjusted Batch" />
        <Field label="Adjustment (MT)" type="number" />
        <Field v-if="modal.kind === 'period'" label="Period" />
        <Field v-if="modal.kind === 'supplier'" label="Supplier" />
      </div>
    </template>
  </FeatureTableView>
</template>

<script setup>
import { ref } from 'vue'
import FeatureTableView from '@/views/_shared/FeatureTableView.vue'
import Field from '@/views/transaction/_TransactionModalField.vue'

const activeModal = ref(null)
const actions = [
  { kind: 'last', label: 'Adjustment Last Record', icon: 'fab fa-fly' },
  { kind: 'init', label: 'Stock Initialization', icon: 'fab fa-bity' },
  { kind: 'period', label: 'Period Adjustment', icon: 'fab fa-ethereum' },
  { kind: 'supplier', label: 'Supplier Adjustment', icon: 'fas fa-male' },
]
const tables = [{
  columns: ['No', 'Action', 'Adjustment No', 'Material No', 'Entry Date', 'Material', 'SLoc', 'Adjusted Batch', 'Adjustment (MT)', 'Supplier / Batch SAP / Adjustment (MT)', 'Created At', 'Created By'],
  emptyText: 'No adjustment data found',
}]
</script>
