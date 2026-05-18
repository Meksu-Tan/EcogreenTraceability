<template>
  <div class="overflow-x-auto rounded-md border border-slate-200">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50 text-xs font-black uppercase text-slate-600">
        <tr>
          <th v-for="column in columns" :key="column" class="px-3 py-3 text-left">{{ column }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td :colspan="columns.length" class="px-3 py-6 text-center text-slate-500">{{ emptyText }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, required: true },
  emptyText: { type: String, required: true },
})
</script>
