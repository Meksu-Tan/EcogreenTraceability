<template>
  <div class="flex flex-col gap-4">
    <!-- DataTable toolbar -->
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div class="relative max-w-xs w-full">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <Icon icon="ri:search-line" class="text-gray-400 text-sm" />
        </div>
        <input
          id="dt-search"
          v-model="search"
          type="text"
          class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 sm:text-sm transition-all"
          placeholder="Search data..."
        />
      </div>
      <div class="text-xs font-medium text-gray-500">
        Showing <span class="text-slate-800">{{ paginatedData.length }}</span> of <span class="text-slate-800">{{ filtered.length }}</span> entries
      </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto rounded-lg border border-gray-100">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">#</th>
            <th
              v-for="col in columns"
              :key="col.key"
              scope="col"
              class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
            >
              <div class="flex items-center gap-1 cursor-pointer group">
                {{ col.label }}
                <Icon icon="ri:sort-asc" class="text-[10px] opacity-0 group-hover:opacity-50 transition-opacity" />
              </div>
            </th>
            <th scope="col" class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-32">Action</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-if="loading">
            <td :colspan="columns.length + 2" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center gap-3">
                <Icon icon="ri:loader-4-line" class="animate-spin text-2xl text-green-600" />
                <span class="text-sm font-medium text-gray-500 tracking-wide">Loading data...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="paginatedData.length === 0">
            <td :colspan="columns.length + 2" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center gap-3">
                <Icon icon="ri:inbox-2-line" class="text-4xl text-gray-200" />
                <span class="text-sm font-medium text-gray-400">Tidak ada data ditemukan</span>
              </div>
            </td>
          </tr>
          <tr
            v-for="(row, i) in paginatedData"
            :key="row[rowKey]"
            class="hover:bg-slate-50/50 transition-colors"
          >
            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400 font-medium">
              {{ (currentPage - 1) * perPage + i + 1 }}
            </td>
            <td v-for="col in columns" :key="col.key" class="px-4 py-3 whitespace-nowrap text-sm text-slate-600">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                <span v-if="col.key === 'status'">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                    :class="row.status == 1 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                  >
                    {{ row.status == 1 ? 'Active' : 'Inactive' }}
                  </span>
                </span>
                <span v-else>{{ row[col.key] ?? '—' }}</span>
              </slot>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
              <slot name="actions" :row="row">
                <div class="flex items-center justify-center gap-1.5">
                  <button
                    class="p-1.5 rounded-md bg-green-500 text-white hover:bg-green-600 transition-colors shadow-sm active:scale-90"
                    title="Edit"
                    @click="$emit('edit', row)"
                  >
                    <Icon icon="ri:edit-line" class="text-[11px]" />
                  </button>
                  <button
                    class="p-1.5 rounded-md transition-colors shadow-sm active:scale-90 text-white"
                    :class="row.status == 1 ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700'"
                    :title="row.status == 1 ? 'Deactivate' : 'Activate'"
                    @click="$emit('toggle-status', row)"
                  >
                    <Icon :icon="row.status == 1 ? 'ri:close-line' : 'ri:check-line'" class="text-[11px]" />
                  </button>
                </div>
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-between mt-2 pt-4 border-t border-gray-100 gap-4 flex-wrap">
      <div class="text-xs font-medium text-gray-500 italic">
        Page <span class="text-slate-800 font-bold">{{ currentPage }}</span> of <span class="text-slate-800 font-bold">{{ totalPages }}</span>
      </div>
      <div class="flex items-center gap-1">
        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          :disabled="currentPage === 1"
          @click="currentPage = 1"
        >
          <Icon icon="ri:arrow-left-s-line" class="text-xs" />
        </button>
        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          :disabled="currentPage === 1"
          @click="currentPage--"
        >
          <Icon icon="ri:arrow-left-line" class="text-xs" />
        </button>

        <div class="flex items-center gap-1 mx-1">
          <button
            v-for="p in visiblePages"
            :key="p"
            class="w-8 h-8 flex items-center justify-center rounded-md text-xs font-bold transition-all shadow-sm"
            :class="p === currentPage ? 'bg-green-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
            @click="currentPage = p"
          >
            {{ p }}
          </button>
        </div>

        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          :disabled="currentPage === totalPages"
          @click="currentPage++"
        >
          <Icon icon="ri:arrow-right-line" class="text-xs" />
        </button>
        <button
          class="w-8 h-8 flex items-center justify-center rounded-md border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all"
          :disabled="currentPage === totalPages"
          @click="currentPage = totalPages"
        >
          <Icon icon="ri:arrow-right-s-line" class="text-xs" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({
  columns: { type: Array, required: true },
  data:    { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  rowKey:  { type: String, default: 'id' },
  perPage: { type: Number, default: 10 },
})
defineEmits(['edit', 'toggle-status'])

const search      = ref('')
const currentPage = ref(1)
watch(search, () => { currentPage.value = 1 })

const filtered = computed(() => {
  if (!search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(row =>
    Object.values(row).some(v => String(v ?? '').toLowerCase().includes(q))
  )
})
const totalPages    = computed(() => Math.max(1, Math.ceil(filtered.value.length / props.perPage)))
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * props.perPage
  return filtered.value.slice(start, start + props.perPage)
})
const visiblePages = computed(() => {
  const t = totalPages.value, c = currentPage.value
  let s = Math.max(1, c - 2), e = Math.min(t, s + 4)
  if (e - s < 4) s = Math.max(1, e - 4)
  const pages = []
  for (let i = s; i <= e; i++) pages.push(i)
  return pages
})
</script>