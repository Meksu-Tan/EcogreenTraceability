<template>
  <div>
    <!-- Toolbar: Search + count -->
    <div v-if="(showSearch && !serverSide) || showTopInfo" class="d-flex align-center justify-space-between gap-4 pa-4 flex-wrap">
      <VTextField
        v-if="showSearch && !serverSide"
        id="dt-search"
        v-model="search"
        placeholder="Search data..."
        prepend-inner-icon="ri-search-line"
        density="compact"
        hide-details
        style="max-width: 280px;"
      />
      <span v-if="showTopInfo" class="text-caption text-medium-emphasis">
        {{ serverSide ? `Showing ${(currentPage - 1) * perPageLocal + 1}–${Math.min(currentPage * perPageLocal, totalItems)} of ${totalItems} entries` : `Showing ${displayData.length} of ${sortedData.length} entries` }}
      </span>
    </div>

    <VDivider v-if="(showSearch && !serverSide) || showTopInfo" />

    <!-- Table -->
    <VTable density="compact" class="data-table">
      <thead>
        <tr>
          <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width: 48px;">#</th>
          <th
            v-for="col in columns"
            :key="col.key"
            class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th"
            :class="{ 'sort-active': sortKey === col.key }"
            style="cursor: pointer; user-select: none; white-space: nowrap;"
            @click="toggleSort(col.key)"
          >
            {{ col.label }}
            <VIcon
              v-if="sortKey === col.key"
              :icon="sortDir === 'asc' ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'"
              size="16"
              class="sort-icon"
            />
            <VIcon
              v-else
              icon="ri-arrow-up-down-line"
              size="14"
              class="sort-icon"
            />
          </th>
          <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width: 120px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <!-- Loading -->
        <tr v-if="loading">
          <td :colspan="columns.length + 2" class="pa-0">
            <VSkeletonLoader type="table-tbody@5" :loading="true" />
          </td>
        </tr>

        <!-- Empty -->
        <tr v-else-if="displayData.length === 0">
          <td :colspan="columns.length + 2" class="text-center pa-8">
            <VIcon icon="ri-inbox-2-line" size="40" color="neutral-200" class="d-block mx-auto mb-2" />
            <span class="text-caption text-medium-emphasis">No data found</span>
          </td>
        </tr>

        <!-- Rows -->
        <tr v-for="(row, i) in displayData" :key="row[rowKey]">
          <td class="text-caption text-medium-emphasis">{{ (currentPage - 1) * perPageLocal + i + 1 }}</td>
          <td v-for="col in columns" :key="col.key">
            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
              <span v-if="col.key === 'status'">
                <VChip
                  :color="row.status == 1 ? 'success' : 'error'"
                  variant="tonal"
                  size="x-small"
                >
                  {{ row.status == 1 ? 'Active' : 'Inactive' }}
                </VChip>
              </span>
              <span v-else-if="col.key.endsWith('_at')" class="text-body-2">
                {{ row[col.key] ? new Date(row[col.key]).toLocaleString() : '—' }}
              </span>
              <span v-else class="text-body-2">{{ row[col.key] ?? '—' }}</span>
            </slot>
          </td>
          <td class="text-center">
            <slot name="actions" :row="row">
              <div class="d-flex justify-center gap-1">
                <VBtn size="x-small" icon="ri-edit-line" color="primary" variant="tonal" @click="$emit('edit', row)" />
                <VBtn
                  size="x-small"
                  :icon="row.status == 1 ? 'ri-close-line' : 'ri-check-line'"
                  :color="row.status == 1 ? 'error' : 'success'"
                  variant="tonal"
                  @click="$emit('toggle-status', row)"
                />
              </div>
            </slot>
          </td>
        </tr>
      </tbody>
    </VTable>

    <!-- Pagination + Per-page selector -->
    <div v-if="totalPages >= 1 && (serverSide ? totalItems > 0 : totalRows > 0)" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
      <div class="d-flex align-center gap-3">
        <span v-if="showBottomInfo" class="text-caption text-medium-emphasis">
          {{ serverSide ? `Showing ${(currentPage - 1) * perPageLocal + 1} - ${Math.min(currentPage * perPageLocal, totalItems)} of ${totalItems} records` : `Showing ${(currentPage - 1) * perPageLocal + 1} - ${Math.min(currentPage * perPageLocal, totalRows)} of ${totalRows} records` }}
        </span>
        <VSelect
          v-model="perPageLocal"
          :items="perPageOptions"
          density="compact"
          variant="outlined"
          hide-details
          style="min-width: 80px; max-width: 100px;"
        />
      </div>
      <VPagination
        v-if="totalPages > 1"
        v-model="currentPage"
        :length="totalPages"
        :total-visible="5"
        density="comfortable"
        size="small"
        show-first-last-page
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { debounce } from '@/utils/debounce'

const props = defineProps({
  columns: { type: Array, required: true },
  data:    { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  rowKey:  { type: String, default: 'id' },
  perPage: { type: Number, default: 10 },
  serverSide: { type: Boolean, default: false },
  totalItems: { type: Number, default: 0 },
  showSearch: { type: Boolean, default: true },
  showTopInfo: { type: Boolean, default: true },
  showBottomInfo: { type: Boolean, default: true },
})
const emit = defineEmits(['edit', 'toggle-status', 'page-change'])

const search       = ref('')
const currentPage  = ref(1)
const sortKey      = ref(null)
const sortDir      = ref(null)
const perPageLocal = ref(props.perPage)
const perPageOptions = [5, 10, 15, 20]

const debouncedResetPage = debounce(() => {
  currentPage.value = 1
}, 300)

watch(search, () => {
  debouncedResetPage()
})

function detectColumnType(colKey) {
  const rows = props.data
  if (!rows || rows.length === 0) return 'text'
  for (const row of rows) {
    const val = row[colKey]
    if (val !== null && val !== undefined && val !== '') {
      return !isNaN(parseFloat(val)) && isFinite(val) ? 'number' : 'text'
    }
  }
  return 'text'
}

function toggleSort(key) {
  if (sortKey.value === key) {
    if (sortDir.value === 'asc') {
      sortDir.value = 'desc'
    } else if (sortDir.value === 'desc') {
      sortKey.value = null
      sortDir.value = null
    }
  } else {
    sortKey.value = key
    const type = detectColumnType(key)
    sortDir.value = type === 'text' ? 'asc' : 'desc'
  }
  currentPage.value = 1
}

const totalRows = computed(() => {
  return props.serverSide ? props.totalItems : props.data.length
})

const sortedData = computed(() => {
  if (props.serverSide) return props.data
  if (!sortKey.value || !sortDir.value) return props.data
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...(props.data)]
  return rows.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    const type = detectColumnType(key)
    if (type === 'number') {
      return dir === 'asc' ? va - vb : vb - va
    }
    return dir === 'asc'
      ? String(va).localeCompare(String(vb))
      : String(vb).localeCompare(String(va))
  })
})

const filtered = computed(() => {
  if (props.serverSide) return sortedData.value
  if (!search.value) return sortedData.value
  const q = search.value.toLowerCase()
  return sortedData.value.filter(row =>
    Object.values(row).some(v => String(v ?? '').toLowerCase().includes(q))
  )
})

const totalPages = computed(() => {
  const count = props.serverSide ? props.totalItems : filtered.value.length
  return Math.max(1, Math.ceil(count / perPageLocal.value))
})

const paginatedData = computed(() => {
  if (props.serverSide) return filtered.value
  const start = (currentPage.value - 1) * perPageLocal.value
  return filtered.value.slice(start, start + perPageLocal.value)
})

const displayData = computed(() => paginatedData.value)

watch(currentPage, (val) => {
  if (props.serverSide) {
    emit('page-change', { page: val, perPage: perPageLocal.value })
  }
})

watch(perPageLocal, (val) => {
  currentPage.value = 1
  if (props.serverSide) {
    emit('page-change', { page: 1, perPage: val })
  }
})

function resetPage() {
  currentPage.value = 1
}

defineExpose({ resetPage })
</script>

<style scoped>
.sort-icon {
  vertical-align: middle;
  transition: opacity 0.15s;
  opacity: 0.35;
}
.sortable-th:hover .sort-icon {
  opacity: 0.7;
}
.sortable-th.sort-active .sort-icon {
  opacity: 1 !important;
  color: rgb(var(--v-theme-primary));
}
</style>
