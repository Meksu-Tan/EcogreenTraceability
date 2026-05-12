<template>
  <div>
    <!-- DataTable toolbar -->
    <div class="datatable-toolbar">
      <div class="datatable-search">
        <i class="fas fa-search"></i>
        <input
          id="dt-search"
          v-model="search"
          type="text"
          class="form-control"
          placeholder="Search..."
        />
      </div>
      <div class="datatable-info">
        Showing {{ paginatedData.length }} of {{ filtered.length }} entries
      </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width:48px;">#</th>
            <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
            <th style="width:120px;text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="columns.length + 2" class="empty-state">
              <i class="fas fa-circle-notch spinner" style="font-size:24px;color:var(--primary);margin-bottom:8px;display:block;"></i>
              Loading data...
            </td>
          </tr>
          <tr v-else-if="paginatedData.length === 0">
            <td :colspan="columns.length + 2" class="empty-state">
              <i class="fas fa-inbox" style="font-size:32px;color:var(--text-muted);"></i>
              <div style="margin-top:8px;">Tidak ada data ditemukan</div>
            </td>
          </tr>
          <tr v-for="(row, i) in paginatedData" :key="row[rowKey]">
            <td style="color:var(--text-muted);">{{ (currentPage - 1) * perPage + i + 1 }}</td>
            <td v-for="col in columns" :key="col.key">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                <span v-if="col.key === 'status'">
                  <span class="badge" :class="row.status == 1 ? 'badge-success' : 'badge-danger'">
                    {{ row.status == 1 ? 'Active' : 'Inactive' }}
                  </span>
                </span>
                <span v-else>{{ row[col.key] ?? '—' }}</span>
              </slot>
            </td>
            <td>
              <div class="action-cell" style="justify-content:center;">
                <button
                  class="btn btn-warning btn-sm btn-icon"
                  title="Edit"
                  @click="$emit('edit', row)"
                  style="border-radius:4px;"
                >
                  <i class="fas fa-pencil-alt" style="font-size:11px;"></i>
                </button>
                <button
                  class="btn btn-sm btn-icon"
                  :class="row.status == 1 ? 'btn-danger' : 'btn-success'"
                  :title="row.status == 1 ? 'Deactivate' : 'Activate'"
                  @click="$emit('toggle-status', row)"
                  style="border-radius:4px;"
                >
                  <i :class="row.status == 1 ? 'fas fa-times' : 'fas fa-check'" style="font-size:11px;"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" style="display:flex;align-items:center;justify-content:space-between;margin-top:14px;flex-wrap:wrap;gap:8px;">
      <div style="font-size:12px;color:var(--text-muted);">
        Page {{ currentPage }} of {{ totalPages }}
      </div>
      <div class="pagination">
        <button class="page-link" :disabled="currentPage === 1" @click="currentPage = 1">«</button>
        <button class="page-link" :disabled="currentPage === 1" @click="currentPage--">‹</button>
        <button
          v-for="p in visiblePages"
          :key="p"
          class="page-link"
          :class="{ active: p === currentPage }"
          @click="currentPage = p"
        >{{ p }}</button>
        <button class="page-link" :disabled="currentPage === totalPages" @click="currentPage++">›</button>
        <button class="page-link" :disabled="currentPage === totalPages" @click="currentPage = totalPages">»</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

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
