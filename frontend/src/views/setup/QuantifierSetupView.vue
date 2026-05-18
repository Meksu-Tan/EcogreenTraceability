<template>
  <FeatureTableView title="Quantifier Setup" :tables="tables" :active-modal="activeModal" @close-modal="activeModal = null">
    <template #toolbar>
      <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" @click="activeModal = { title: 'New Reset Quantifier' }">
        <i class="fab fa-bity mr-2"></i> New Reset Quantifier
      </button>
    </template>
    <template #modal>
      <div class="grid gap-4 md:grid-cols-2">
        <Field label="Flowmeter" />
        <Field label="Reset Value" type="number" />
        <Field label="Reset Date" type="date" />
        <Field label="Remark" />
      </div>
    </template>
  </FeatureTableView>
</template>

<script setup>
import { ref } from 'vue'
import FeatureTableView from '@/views/_shared/FeatureTableView.vue'
import Field from '@/views/transaction/_TransactionModalField.vue'

const activeModal = ref(null)
const tables = [{
  columns: ['No', 'Action', 'Flowmeter', 'Reset Value', 'Remark', 'Status', 'Created at', 'Created by'],
  emptyText: 'No quantifier data found',
}]
</script>
