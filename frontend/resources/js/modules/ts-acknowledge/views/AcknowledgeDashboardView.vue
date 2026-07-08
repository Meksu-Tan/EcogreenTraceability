<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Acknowledge Dashboard</h1>
        <p class="text-body-2 text-medium-emphasis mb-0">Match EO/DLS quantity against DCS reading per WIP step.</p>
      </div>
      <div class="d-flex align-center ga-2 flex-wrap">
        <VSelect
          v-model="filters.plant_code"
          :items="plantOptions"
          label="Plant"
          density="compact"
          variant="outlined"
          hide-details
          style="min-width: 160px"
          @update:model-value="onFilterChange"
        />
        <VTextField
          v-if="filters.type === 'WIP'"
          v-model="filters.date"
          type="date"
          density="compact"
          variant="outlined"
          hide-details
          style="min-width: 160px"
          @update:model-value="onFilterChange"
        />
        <VSelect
          v-model="filters.type"
          :items="typeOptions"
          label="Type"
          density="compact"
          variant="outlined"
          hide-details
          style="min-width: 140px"
          @update:model-value="onTypeChange"
        />
        <VSelect
          v-if="filters.type === 'WIP'"
          v-model="filters.section_id"
          :items="sectionOptions"
          label="Section"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          style="min-width: 180px"
          @update:model-value="onFilterChange"
        />
        <VBtn prepend-icon="ri-refresh-line" variant="tonal" :loading="store.loading" @click="reload">Refresh</VBtn>
      </div>
    </div>

    <VAlert v-if="store.error" type="error" variant="tonal" class="mb-4">{{ store.error }}</VAlert>

    <div v-if="!store.sections.length && !store.loading" class="d-flex flex-column ga-5">
      <VCard variant="outlined" class="pa-12 text-center">
        <VIcon icon="ri-check-double-line" size="48" color="neutral" class="mb-4" />
        <h3 class="text-h6 font-weight-medium text-medium-emphasis">No data for selected filters</h3>
      </VCard>
    </div>

    <div v-else class="d-flex flex-column ga-5">
      <VCard v-for="section in store.sections" :key="section.section_id ?? section.section_code" rounded="lg" elevation="1">
        <VCardTitle class="d-flex align-center justify-space-between flex-wrap ga-2">
          <span class="text-subtitle-1 font-weight-bold">{{ section.section_name }}</span>
          <div class="d-flex align-center ga-2">
            <VChip size="small" color="primary" variant="tonal">{{ section.section_code }}</VChip>
            <VBtn
              v-if="filters.type === 'WIP'"
              size="small"
              variant="outlined"
              color="secondary"
              prepend-icon="ri-download-cloud-2-line"
              :loading="sectionFetchLoading(section)"
              @click="onFetchAllDcs(section)"
            >
              Fetch All DCS
            </VBtn>
          </div>
        </VCardTitle>
        <VDivider />
        <VTable density="comfortable" hover>
          <thead>
            <tr>
              <th v-if="filters.type === 'WIP'">Label</th>
              <th v-else>No</th>
              <th>Material</th>
              <th>Trace No</th>
              <th>EO/DLS Qty</th>
              <th>DCS Qty</th>
              <th>Keterangan</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Created By</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="mode in section.modes" :key="mode.mode">
              <tr v-for="(step, idx) in mode.steps" :key="step.step_id ?? step.transaction_id">
                <td v-if="filters.type === 'WIP'" class="font-weight-medium">{{ step.label }}</td>
                <td v-else class="font-weight-medium">{{ idx + 1 }}</td>
                <td class="text-caption">{{ step.material_id || step.material_name || '-' }}</td>
                <td class="text-caption">{{ step.trace_no || '-' }}</td>
                <td>{{ step.eo_dls_qty ?? '-' }}</td>
                 <td>
                   <div class="d-flex align-center ga-2">
                     <span>{{ step.dcs_qty ?? '-' }}</span>
                     <VBtn
                       v-if="(filters.type === 'WIP' && step.tank) || (filters.type !== 'WIP' && step.sloc_id)"
                       size="x-small"
                       variant="text"
                       color="primary"
                       :loading="store.savingRows[rowKey(section, step)]"
                       @click="onFetchDcs(section, step)"
                     >
                       <VIcon icon="ri-refresh-line" size="14" />
                     </VBtn>
                   </div>
                 </td>
                <td class="text-caption">{{ step.keterangan || '-' }}</td>
                <td>
                  <VChip :color="statusColor(step.match_status)" size="x-small" variant="tonal">
                    {{ step.match_status }}
                  </VChip>
                </td>
                <td class="text-caption">{{ formatDate(step.updated_at) }}</td>
                <td class="text-caption">{{ step.created_by || '-' }}</td>
                <td class="text-right">
                  <div class="d-flex align-center justify-end ga-1">
                    <VBtn
                      v-if="filters.type === 'WIP' && isMismatch(step)"
                      size="small"
                      variant="tonal"
                      color="warning"
                      :loading="store.savingRows[rowKey(section, step)]"
                      @click="onSyncDcs(section, step)"
                    >
                      Sync
                    </VBtn>
                    <VBtn size="small" variant="tonal" color="primary" :loading="store.saving" @click="onSave(section, step)">Save</VBtn>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </VTable>
      </VCard>

      <div v-if="store.pagination.total > 0" class="d-flex flex-wrap justify-space-between align-center custom-pagination-footer pa-3 rounded border gap-2">
        <div class="d-flex align-center gap-3">
          <span class="text-caption text-medium-emphasis">
            Showing {{ (page - 1) * perPage + 1 }} - {{ Math.min(page * perPage, store.pagination.total) }} of {{ store.pagination.total }} records
          </span>
          <VSelect
            v-model="perPage"
            :items="[5, 10, 15, 20]"
            density="compact"
            variant="outlined"
            hide-details
            style="min-width: 80px; max-width: 100px;"
            @update:model-value="onPerPageChange"
          />
        </div>
        <VPagination
          v-if="store.pagination.last_page > 1"
          v-model="page"
          :length="store.pagination.last_page"
          :total-visible="5"
          density="comfortable"
          size="small"
          show-first-last-page
          @update:model-value="changePage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, ref } from 'vue'
import { useAcknowledgeStore } from '../stores/acknowledgeStore.js'
import { useSetupPlantStore } from '@/stores/plant.js'
import { useToastStore } from '@/stores/toast.js'

const store = useAcknowledgeStore()
const plantStore = useSetupPlantStore()
const toast = useToastStore()

const typeOptions = ['WIP', 'TRANSFER', 'BLENDING']

const page = ref(1)
const perPage = ref(15)

const filters = reactive({
  plant_code: '',
  date: new Date().toISOString().slice(0, 10),
  type: 'WIP',
  section_id: null,
})

const plantOptions = computed(() =>
  (plantStore.plants || []).map(p => ({ title: `${p.code_2} - ${p.description}`, value: p.code_3 }))
)

const sectionOptions = computed(() =>
  (store.allSections || []).map(s => ({ title: s.name, value: s.id }))
)

function statusColor(status) {
  const map = { match: 'success', mismatch: 'error', pending: 'warning' }
  return map[status] || 'neutral'
}

function formatDate(value) {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
}

function rowKey(section, step) {
  return `${section.section_id ?? section.section_code}-${step.step_type}-${step.step_id ?? step.transaction_id}`
}

function isMismatch(step) {
  return step.match_status === 'mismatch' && step.dcs_qty !== null && step.eo_dls_qty !== null
}

function sectionFetchLoading(section) {
  return section.modes.some(mode => mode.steps.some(step => store.savingRows[rowKey(section, step)]))
}

function reload() {
  if (!filters.plant_code) return
  store.fetchDashboard({ ...filters, page: page.value, per_page: perPage.value })
}

function onFilterChange() {
  page.value = 1
  reload()
}

function onTypeChange() {
  page.value = 1
  if (filters.type === 'WIP') {
    if (!filters.date) filters.date = new Date().toISOString().slice(0, 10)
    filters.section_id = null
  } else {
    filters.section_id = null
  }
  reload()
}

function changePage(p) {
  if (p < 1 || p > store.pagination.last_page) return
  page.value = p
  reload()
}

function onPerPageChange() {
  page.value = 1
  reload()
}

async function onFetchDcs(section, step) {
  const key = rowKey(section, step)
  store.setRowLoading(key, true)
  try {
    const payload = {
      plant_code: filters.plant_code,
      date: filters.date,
      type: filters.type,
      scope: 'row',
    }
    if (filters.type === 'WIP') {
      payload.section_id = section.section_id
      payload.step_type = step.step_type
      payload.step_id = step.step_id
    } else {
      payload.step_id = step.transaction_id
    }
    const data = await store.fetchDcs(payload)
    if (data && data.dcs_qty !== undefined) {
      step.dcs_qty = data.dcs_qty
      step.qty_source = 'dcs'
      step.match_status = data.dcs_qty !== null && step.eo_dls_qty !== null && Math.abs(step.eo_dls_qty - data.dcs_qty) < 0.001 ? 'match' : 'mismatch'
      toast.success('DCS fetched successfully')
    } else {
      toast.error('DCS tag not found or fetch failed')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to fetch DCS')
  } finally {
    store.setRowLoading(key, false)
  }
}

async function onFetchAllDcs(section) {
  store.setRowLoading(`section-${section.section_id ?? section.section_code}`, true)
    try {
      const results = await store.fetchDcs({
        plant_code: filters.plant_code,
        date: filters.date,
        type: filters.type,
        scope: 'all',
        section_id: section.section_id,
      })
      const resultMap = new Map(results.map(r => [`${r.section_id}-${r.step_type}`, r]))
      for (const mode of section.modes) {
        for (const step of mode.steps) {
          const key = `${section.section_id}-${step.step_type}`
          const result = resultMap.get(key)
          if (result && result.dcs_qty !== undefined) {
            step.dcs_qty = result.dcs_qty
            step.qty_source = 'dcs'
            step.match_status = result.dcs_qty !== null && step.eo_dls_qty !== null && Math.abs(step.eo_dls_qty - result.dcs_qty) < 0.001 ? 'match' : 'mismatch'
          }
        }
      }
      toast.success('DCS fetched for all rows')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to fetch DCS for all rows')
  } finally {
    store.setRowLoading(`section-${section.section_id ?? section.section_code}`, false)
  }
}

async function onSyncDcs(section, step) {
  const key = rowKey(section, step)
  store.setRowLoading(key, true)
  try {
    const payload = {
      plant_code: filters.plant_code,
      date: filters.date,
      type: filters.type,
      section_id: section.section_id,
      step_type: step.step_type,
      step_id: step.step_id,
    }
    if (filters.type !== 'WIP') {
      payload.transaction_id = step.transaction_id
    }
    await store.syncDcs(payload)
    step.eo_dls_qty = step.dcs_qty
    step.qty_source = 'dcs'
    step.match_status = 'match'
    toast.success('DCS synced to EO/DLS')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to sync DCS')
  } finally {
    store.setRowLoading(key, false)
  }
}

async function onSave(section, step) {
  const payload = {
    plant_code: filters.plant_code,
    entry_date: filters.date,
    type: filters.type,
    eo_dls_qty: step.eo_dls_qty,
    dcs_qty: step.dcs_qty,
    qty_source: step.qty_source || 'eo_dls',
  }
  if (filters.type === 'WIP') {
    payload.section_id = section.section_id
    payload.step_type = step.step_type
    payload.step_id = step.step_id
  } else {
    payload.transaction_id = step.transaction_id
  }
  const r = await store.saveAcknowledge(payload)
  if (r.status === 1) {
    toast.success(r.message)
    reload()
  } else {
    toast.error(r.message)
  }
}

onMounted(async () => {
  if (plantStore.plants.length === 0) await plantStore.fetchPlants()
  const defaultPlant = plantStore.plants[0]
  if (defaultPlant) filters.plant_code = defaultPlant.code_3
  reload()
})
</script>
