<template>
  <div class="pa-6">
    <VCard class="mb-4">
      <VCardTitle class="pa-5 pb-2">
        <h1 class="text-h6 font-weight-bold">Reset Quantifier</h1>
      </VCardTitle>
      <VCardText>
        <div class="mb-4">
          <VBtn color="primary" prepend-icon="ri-add-line" @click="openCreateModal">
            New Reset Quantifier
          </VBtn>
        </div>

        <VRow dense class="mb-2">
          <VCol cols="12" md="3">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Flowmeter</label>
            <VSelect
              v-model="filters.flowmeter"
              :items="flowmeterOptions"
              density="compact"
              variant="outlined"
              class="mt-1"
            />
          </VCol>
          <VCol cols="12" md="3">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Date From</label>
            <VTextField v-model="filters.date_from" type="date" density="compact" variant="outlined" class="mt-1" />
          </VCol>
          <VCol cols="12" md="3">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Date To</label>
            <VTextField v-model="filters.date_to" type="date" density="compact" variant="outlined" class="mt-1" />
          </VCol>
          <VCol cols="12" md="3">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Status</label>
            <VSelect
              v-model="filters.status"
              :items="[
                { value: '', title: 'All' },
                { value: '1', title: 'Active' },
                { value: '0', title: 'Inactive' }
              ]"
              density="compact"
              variant="outlined"
              class="mt-1"
            />
          </VCol>
        </VRow>

        <div class="d-flex ga-2 mb-4">
          <VBtn color="primary" prepend-icon="ri-search-line" @click="loadData">Search</VBtn>
          <VBtn variant="outlined" color="primary" prepend-icon="ri-refresh-line" @click="resetFilters">Reset</VBtn>
        </div>
      </VCardText>
    </VCard>

    <VCard>
      <VCardText class="pa-0">
        <div v-if="loading" class="pa-4">
          <VSkeletonLoader type="table-thead, table-tbody@5" :loading="true" />
        </div>
        <div v-else class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-center" style="width:48px">No</th>
                <th class="text-center" style="width:96px">Action</th>
                <th class="sortable-th" :class="{ active: sortKey === 'flowmeter' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('flowmeter')">Flowmeter<VIcon v-if="sortKey==='flowmeter'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-right sortable-th" :class="{ active: sortKey === 'value' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('value')">Reset Value<VIcon v-if="sortKey==='value'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="sortable-th" :class="{ active: sortKey === 'remark' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('remark')">Remark<VIcon v-if="sortKey==='remark'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="text-center sortable-th" :class="{ active: sortKey === 'status' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('status')">Status<VIcon v-if="sortKey==='status'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="sortable-th" :class="{ active: sortKey === 'created_at' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('created_at')">Created At<VIcon v-if="sortKey==='created_at'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                <th class="sortable-th" :class="{ active: sortKey === 'created_by' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('created_by')">Created By<VIcon v-if="sortKey==='created_by'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
              </tr>
            </thead>
            <tbody v-if="sortedList.length === 0">
              <tr>
                <td colspan="8" class="text-center pa-8">
                  <VIcon icon="ri-calculator-line" size="48" class="text-disabled mb-2" />
                  <p>No quantifier records.</p>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(row, i) in paginatedData" :key="row.id_reset">
                <td class="text-center text-medium-emphasis font-monospace">{{ (page - 1) * perPage + i + 1 }}</td>
                <td class="text-center">
                  <div class="d-flex ga-1 justify-center">
                    <VBtn icon="ri-edit-line" size="x-small" color="primary" variant="tonal" @click="openEditModal(row)" />
                    <VBtn
                      v-if="row.status===1"
                      icon="ri-delete-bin-line"
                      size="x-small"
                      color="error"
                      variant="tonal"
                      @click="toggleStatus(row, 'deactivate')"
                    />
                    <VBtn
                      v-else
                      icon="ri-redo-line"
                      size="x-small"
                      color="success"
                      variant="tonal"
                      @click="toggleStatus(row, 'activate')"
                    />
                  </div>
                </td>
                <td class="font-weight-medium">{{ row.flowmeter }}</td>
                <td class="text-right font-monospace">{{ formatQty(row.value) }}</td>
                <td class="text-truncate" style="max-width:200px">{{ row.remark || '-' }}</td>
                <td class="text-center">
                  <VIcon
                    :icon="row.status===1 ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'"
                    :color="row.status===1 ? 'success' : 'error'"
                    size="small"
                  />
                </td>
                <td class="text-caption text-medium-emphasis font-monospace">{{ row.created_at || '-' }}</td>
                <td>{{ row.created_by || '-' }}</td>
              </tr>
            </tbody>
          </VTable>
        </div>

        <div v-if="sortedList.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, sortedList.length) }} of {{ sortedList.length }} records
            </span>
            <VSelect
              v-model="perPage"
              :items="[5, 10, 15, 20]"
              density="compact"
              variant="outlined"
              hide-details
              style="min-width: 80px; max-width: 100px;"
            />
          </div>
          <VPagination
            v-if="totalPages > 1"
            v-model="page"
            :length="totalPages"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
          />
        </div>
      </VCardText>
    </VCard>

    <VDialog v-model="showModal" max-width="500" persistent>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">{{ editMode==='ADD' ? 'New Quantifier' : 'Edit Quantifier' }}</span>
          <VBtn icon="ri-close-line" size="small" variant="text" color="medium-emphasis" @click="showModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense>
            <VCol cols="12" md="6">
              <VTextField
                v-model="form.reset_date"
                label="Reset Date *"
                type="date"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="form.flowmeter"
                :items="flowmeterOptions"
                label="Flowmeter"
                density="compact"
                variant="outlined"
              />
              <p v-if="editMode==='ADD'" class="text-caption text-medium-emphasis mt-1">Leave empty for ALL flowmeters (bulk).</p>
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model.number="form.value"
                label="Value *"
                type="number"
                step="0.001"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12">
              <VTextarea
                v-model="form.remark"
                label="Remark"
                rows="2"
                density="compact"
                variant="outlined"
                placeholder="Monthly reset"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showModal = false">Cancel</VBtn>
          <VBtn color="primary" :loading="saving" prepend-icon="ri-save-line" @click="saveQuantifier">Save</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog :model-value="!!confirmMsg" max-width="400" @update:model-value="confirmMsg = ''">
      <VCard>
        <VCardText class="text-center pa-6">
          <p class="text-body-1 font-weight-medium mb-4">{{ confirmMsg }}</p>
          <div class="d-flex justify-center ga-2">
            <VBtn variant="outlined" color="medium-emphasis" @click="confirmMsg = ''">Cancel</VBtn>
            <VBtn :color="confirmType==='activate' ? 'primary' : 'error'" :loading="saving" @click="executeConfirm">
              {{ saving ? 'Processing...' : 'Confirm' }}
            </VBtn>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuantifierStore } from '../stores/quantifierStore'
import { useToastStore } from '@/stores/toast.js'

const store = useQuantifierStore()
const toast = useToastStore()

const { list: data, flowmeters, loading, saving } = storeToRefs(store)

const showModal = ref(false)
const editMode = ref('ADD')
const confirmMsg = ref('')
const confirmType = ref('')
const confirmAction = ref(null)

const sortKey = ref(null)
const sortDir = ref(null)
const page = ref(1)
const perPage = ref(10)

function detectColumnType(colKey) {
  const rows = data.value
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
    if (sortDir.value === 'asc') { sortDir.value = 'desc' }
    else if (sortDir.value === 'desc') { sortKey.value = null; sortDir.value = null }
  } else {
    sortKey.value = key
    sortDir.value = detectColumnType(key) === 'text' ? 'asc' : 'desc'
  }
  page.value = 1
}

const sortedList = computed(() => {
  if (!sortKey.value || !sortDir.value) return data.value || []
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...(data.value || [])]
  const type = detectColumnType(key)
  return rows.sort((a, b) => {
    const va = a[key]; const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1; if (vb == null) return -1
    if (type === 'number') return dir === 'asc' ? va - vb : vb - va
    return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va))
  })
})

const totalPages = computed(() => Math.max(1, Math.ceil(sortedList.value.length / perPage.value)))

const paginatedData = computed(() => {
  const start = (page.value - 1) * perPage.value
  return sortedList.value.slice(start, start + perPage.value)
})

watch(perPage, () => { page.value = 1 })

const filters = reactive({ flowmeter: '', date_from: '', date_to: '', status: '' })
const form = reactive({ id: null, reset_date: '', flowmeter: '', value: 0, remark: '' })

const flowmeterOptions = computed(() => [
  { value: '', title: 'All' },
  ...flowmeters.value.map(fm => ({ value: fm.flowmeter, title: fm.flowmeter }))
])

onMounted(() => { loadFlowmeters(); loadData() })

const loadFlowmeters = async () => {
  await store.fetchFlowmeters()
}

const loadData = async () => {
  await store.fetchList({ ...filters })
}

const resetFilters = () => {
  filters.flowmeter = ''
  filters.date_from = ''
  filters.date_to = ''
  filters.status = ''
  loadData()
}

const openCreateModal = () => {
  editMode.value = 'ADD'
  form.id = null
  form.reset_date = new Date().toISOString().split('T')[0]
  form.flowmeter = ''
  form.value = 0
  form.remark = ''
  showModal.value = true
}

const openEditModal = (row) => {
  editMode.value = 'UPDATE'
  form.id = row.id_reset
  form.reset_date = row.reset_date
  form.flowmeter = row.flowmeter
  form.value = parseFloat(row.value || 0)
  form.remark = row.remark || ''
  showModal.value = true
}

const saveQuantifier = async () => {
  if (!form.reset_date) return
  const payload = {
    mode: editMode.value,
    reset_date: form.reset_date,
    flowmeter: form.flowmeter || null,
    value: form.value,
    remark: form.remark,
    ...(editMode.value === 'UPDATE' ? { id: form.id } : {})
  }
  
  const res = await store.save(payload)
  if (res.ok) {
    toast.success('Quantifier saved successfully')
    showModal.value = false
    await loadData()
  } else {
    toast.error('Failed to save quantifier: ' + res.error)
  }
}

const toggleStatus = (row, type) => {
  confirmType.value = type
  confirmMsg.value = `${type === 'activate' ? 'Activate' : 'Deactivate'} ${row.flowmeter}?`
  confirmAction.value = async () => {
    const res = type === 'activate'
      ? await store.activate(row.id_reset)
      : await store.deactivate(row.id_reset)
    
    if (res.ok) {
      toast.success(`Quantifier ${type === 'activate' ? 'activated' : 'deactivated'} successfully`)
      await loadData()
    } else {
      toast.error(`Failed to ${type} quantifier: ` + res.error)
    }
  }
}

const executeConfirm = async () => {
  try {
    await confirmAction.value()
    confirmMsg.value = ''
  } catch (e) {
    toast.error('Failed: ' + e.message)
  }
}

const formatQty = (q) => parseFloat(q || 0).toFixed(3)
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>
