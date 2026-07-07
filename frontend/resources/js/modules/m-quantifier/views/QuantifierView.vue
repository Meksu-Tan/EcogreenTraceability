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
                <th>Flowmeter</th>
                <th class="text-right">Reset Value</th>
                <th>Remark</th>
                <th class="text-center">Status</th>
                <th>Created At</th>
                <th>Created By</th>
              </tr>
            </thead>
            <tbody v-if="!data || data.length === 0">
              <tr>
                <td colspan="8" class="text-center pa-8">
                  <VIcon icon="ri-calculator-line" size="48" class="text-disabled mb-2" />
                  <p>No quantifier records.</p>
                </td>
              </tr>
            </tbody>
            <tbody v-else>
              <tr v-for="(row, i) in data" :key="row.id_reset">
                <td class="text-center text-medium-emphasis font-monospace">{{ (pagination.currentPage - 1) * pagination.perPage + i + 1 }}</td>
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

        <div v-if="pagination.total > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <div class="d-flex align-center gap-3">
            <span class="text-caption text-medium-emphasis">
              Showing {{ (pagination.currentPage - 1) * pagination.perPage + 1 }} - {{ Math.min(pagination.currentPage * pagination.perPage, pagination.total) }} of {{ pagination.total }} records
            </span>
            <VSelect
              v-model="pagination.perPage"
              :items="[5, 10, 15, 20]"
              density="compact"
              variant="outlined"
              hide-details
              style="min-width: 80px; max-width: 100px;"
              @update:model-value="onPerPageChange"
            />
          </div>
          <VPagination
            v-if="pagination.lastPage > 1"
            v-model="pagination.currentPage"
            :length="pagination.lastPage"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="onPageChange"
          />
        </div>
      </VCardText>
    </VCard>

    <VDialog v-model="showModal" max-width="960" scrollable>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">{{ editMode === 'ADD' ? 'New Quantifier' : 'Edit Quantifier' }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5 bg-neutral-50">
          <form @submit.prevent="saveQuantifier" class="d-flex flex-column ga-4">
            <VCard variant="outlined">
              <VCardTitle class="d-flex flex-wrap align-end justify-space-between border-b pa-4 ga-3">
                <span class="text-body-1 font-weight-bold">Reset Details</span>
              </VCardTitle>
              <VCardText class="pt-4">
                <VRow dense>
                  <VCol cols="12" sm="6" md="4">
                    <VTextField
                      v-model="form.reset_date"
                      label="Reset Date *"
                      type="date"
                      density="compact"
                      variant="outlined"
                    />
                  </VCol>
                  <VCol cols="12" sm="6" md="4">
                    <VSelect
                      v-model="form.flowmeter"
                      :items="flowmeterOptions"
                      label="Flowmeter"
                      density="compact"
                      variant="outlined"
                    />
                    <p v-if="editMode === 'ADD'" class="text-caption text-medium-emphasis mt-1">Leave empty for ALL flowmeters (bulk).</p>
                  </VCol>
                  <VCol cols="12" sm="6" md="4">
                    <VTextField
                      v-model.number="form.value"
                      label="Value *"
                      type="number"
                      step="0.001"
                      density="compact"
                      variant="outlined"
                    />
                  </VCol>
                </VRow>
                <VRow dense class="mt-2">
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
            </VCard>
            <VCard variant="outlined">
              <VCardText class="d-flex flex-wrap align-center justify-end gap-3">
                <div class="d-flex ga-2">
                  <VBtn variant="outlined" color="medium-emphasis" @click="showModal = false">Cancel</VBtn>
                  <VBtn type="submit" color="primary" prepend-icon="ri-save-line" :loading="saving">Save</VBtn>
                </div>
              </VCardText>
            </VCard>
          </form>
        </VCardText>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useQuantifierStore } from '../stores/quantifierStore'
import { useToastStore } from '@/stores/toast.js'

const store = useQuantifierStore()
const toast = useToastStore()

const { list: data, flowmeters, loading, saving, pagination } = storeToRefs(store)

const showModal = ref(false)
const editMode = ref('ADD')
const confirmMsg = ref('')
const confirmType = ref('')
const confirmAction = ref(null)

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
  store.resetCache()
  store.setPage(1)
  loadData()
}

const onPageChange = (page) => {
  store.setPage(page)
  loadData()
}

const onPerPageChange = (perPage) => {
  pagination.value.perPage = perPage
  store.setPage(1)
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
    store.resetCache()
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
      store.resetCache()
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
.custom-pagination-footer { border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity)); }
</style>
