<template>
  <div class="pa-6">
    <VRow justify="space-between" align="center" class="mb-4">
      <VCol cols="auto">
        <VRow align="center" no-gutters>
          <VCol cols="auto">
            <h1 class="text-h5 font-weight-bold">Raw Material Lists</h1>
            <div class="d-flex align-center gap-2 mt-1">
              <span class="text-body-2 text-medium-emphasis">Location:</span>
              <VChip
                size="small"
                color="primary"
                variant="tonal"
                prepend-icon="ri-factory-line"
              >
                {{ plantSelectionStore.selectedPlantName || 'All Plants' }}
              </VChip>
            </div>
          </VCol>
          <VCol cols="auto" class="ml-6 pl-6 border-s">
            <PlantSelector @change="fetchData" />
          </VCol>
        </VRow>
      </VCol>
      <VCol cols="auto">
        <VRow no-gutters>
          <VBtn
            color="primary"
            prepend-icon="ri-add-line"
            class="mr-2"
            @click="openCreateModal"
          >
            New RM Entry
          </VBtn>
          <VBtn
            color="primary"
            prepend-icon="ri-arrow-right-line"
            class="mr-2"
            @click="openTransferModal"
          >
            Transfer to Feed Tank
          </VBtn>
          <VAlert type="error" variant="tonal" density="compact" class="font-weight-bold">
            QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY.
          </VAlert>
        </VRow>
      </VCol>
    </VRow>

    <VCard class="mb-4">
      <VCardTitle class="bg-neutral-50 text-uppercase text-body-2 font-weight-bold py-3">
        STORAGE TANK LOG
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Trace No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Entry Date</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Matl Doc</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">PurchO</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Manufacturer</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sloc</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Init Material (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Init Supplier (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">On-Hand (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="min-width:240px">Supplier / Batch SAP / Init Qty (MT) / Remark</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center">Status</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created At</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created By</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="16" class="pa-0">
                  <VSkeletonLoader type="table-tbody@5" :loading="true" />
                </td>
              </tr>
              <tr v-else-if="!hasEntries">
                <td colspan="16" class="text-center text-medium-emphasis py-4">No RM entries found</td>
              </tr>
              <tr v-for="(entry, index) in entries" :key="entry.id_balance_head" v-else>
                <td class="text-center text-caption text-medium-emphasis">{{ (currentPageStorage - 1) * itemsPerPage + index + 1 }}</td>
                <td class="font-weight-medium font-mono text-caption">{{ entry.trace_no }}</td>
                <td class="text-caption">{{ formatDate(entry.entry_date) }}</td>
                <td class="text-caption">
                  <a href="#" @click.prevent="openMatlDocEdit(entry)" class="text-medium-emphasis text-decoration-underline">
                    {{ entry.material_document || '-' }}
                  </a>
                </td>
                <td class="text-caption">
                  <a href="#" @click.prevent="openPoEdit(entry)" class="text-medium-emphasis text-decoration-underline">
                    {{ entry.po_so || '-' }}
                  </a>
                </td>
                <td class="text-caption font-weight-medium">{{ entry.material }}</td>
                <td class="text-caption">{{ entry.manufacturer_name || '-' }}</td>
                <td class="text-caption">
                  <a href="#" @click.prevent="openChildTanksModal(entry)" class="text-medium-emphasis text-decoration-underline">
                    {{ entry.sloc_description || 'N/A' }}
                  </a>
                </td>
                <td class="text-right font-weight-medium text-caption" :class="entry.init_qty === entry.balance_supplier ? 'text-success' : 'text-error'">{{ entry.init_qty }}</td>
                <td class="text-right font-weight-medium text-caption" :class="entry.init_qty === entry.balance_supplier ? 'text-success' : 'text-error'">{{ entry.balance_supplier }}</td>
                <td class="text-right font-weight-bold text-caption">{{ entry.qty }}</td>
                <td class="text-caption">
                  <div class="supplier-scroll text-caption" style="max-height:80px;overflow-y:auto;white-space:pre-wrap">
                    <template v-if="entry.supplier">
                      <VChip
                        v-for="(sup, si) in (typeof entry.supplier === 'string' ? entry.supplier.split('|') : [entry.supplier])"
                        :key="si"
                        size="x-small"
                        color="primary"
                        variant="flat"
                        class="mr-1 mb-1"
                      >
                        {{ sup.trim() }}
                      </VChip>
                    </template>
                    <template v-else>-</template>
                  </div>
                </td>
                <td class="text-center">
                  <VIcon
                    :icon="entry.status == 1 ? 'ri-check-line' : 'ri-close-line'"
                    :color="entry.status == 1 ? 'success' : 'error'"
                    size="small"
                  />
                </td>
                <td class="text-caption text-medium-emphasis">{{ entry.created_at ? new Date(entry.created_at).toLocaleString() : '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ entry.created_by }}</td>
                <td class="text-center">
                  <VBtn
                    icon="ri-delete-bin-line"
                    size="x-small"
                    color="error"
                    variant="tonal"
                    :disabled="entry.traced !== 'N/A'"
                    @click="deactivateEntry(entry.id_balance_head)"
                  />
                  <VBtn
                    icon="ri-edit-line"
                    size="x-small"
                    color="primary"
                    variant="tonal"
                    @click="openUpdateModal(entry)"
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
        <div v-if="store.pagination.total > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <span class="text-caption text-medium-emphasis">
            Showing {{ (currentPageStorage - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPageStorage * itemsPerPage, store.pagination.total) }} of {{ store.pagination.total }} records
          </span>
          <VPagination
            v-if="totalPagesStorage > 1"
            v-model="currentPageStorage"
            :length="totalPagesStorage"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
            @update:model-value="changeStoragePage"
          />
        </div>
      </VCardText>
    </VCard>

    <VCard class="mb-4">
      <VCardTitle class="bg-neutral-50 text-uppercase text-body-2 font-weight-bold py-3">
        FEED TANK LOG
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <div class="overflow-x-auto">
          <VTable density="compact" class="text-body-2">
            <thead>
              <tr>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">TraceNo (From >>> To)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Entry Date</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Matl Doc</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Material</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Sloc</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Init Material (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">Init Supplier (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right">On-Hand (MT)</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="min-width:240px">Supplier / Batch SAP / Init Qty (MT) / Remark</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center">Status</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created At</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis">Created By</th>
                <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:80px">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="feedLoading">
                <td colspan="14" class="pa-0">
                  <VSkeletonLoader type="table-tbody@5" :loading="true" />
                </td>
              </tr>
              <tr v-else-if="feedLogsSafe.length === 0">
                <td colspan="14" class="text-center text-medium-emphasis py-4">No feed logs found</td>
              </tr>
              <tr v-for="(log, index) in paginatedFeedLogs" :key="log.id_trace_head" v-else>
                <td class="text-center text-caption text-medium-emphasis">{{ (currentPageFeed - 1) * itemsPerPage + index + 1 }}</td>
                <td class="text-caption font-mono">
                  <div class="text-caption" v-for="(pair, idx) in (log.trace_pairs_array || [log.from_trace_no + ' >>> ' + log.to_trace_no])" :key="idx">
                    {{ pair.replace('>>>', ' >>> ') }}
                  </div>
                </td>
                <td class="text-caption">{{ formatDate(log.entry_date) }}</td>
                <td class="text-caption font-mono">{{ log.material_document || '-' }}</td>
                <td class="font-weight-medium text-caption">{{ log.material_name }}</td>
                <td class="text-caption">
                  <a href="#" @click.prevent="openChildTanksModal(log)" class="text-medium-emphasis text-decoration-underline">
                    {{ log.sloc_description || 'N/A' }}
                  </a>
                </td>
                <td class="text-right font-weight-medium text-caption">{{ log.in_qty }}</td>
                <td class="text-right font-weight-medium text-caption">{{ log.in_qty }}</td>
                <td class="text-right font-weight-bold text-caption">{{ log.in_qty }}</td>
                <td class="text-caption">
                  <div class="supplier-scroll text-caption" style="max-height:80px;overflow-y:auto;white-space:pre-wrap">
                    <template v-if="log.supplier">
                      <VChip
                        v-for="(sup, si) in (typeof log.supplier === 'string' ? log.supplier.split('|') : [log.supplier])"
                        :key="si"
                        size="x-small"
                        color="primary"
                        variant="flat"
                        class="mr-1 mb-1"
                      >
                        {{ sup.trim() }}
                      </VChip>
                    </template>
                    <template v-else>-</template>
                  </div>
                </td>
                <td class="text-center">
                  <VIcon icon="ri-check-line" color="success" size="small" />
                </td>
                <td class="text-caption text-medium-emphasis">{{ log.created_at ? new Date(log.created_at).toLocaleString() : '-' }}</td>
                <td class="text-caption text-medium-emphasis">{{ log.created_by }}</td>
                <td class="text-center">
                  <VBtn
                    icon="ri-delete-bin-line"
                    size="x-small"
                    color="error"
                    variant="tonal"
                    @click="deactivateTransfer(log.id_trace_head)"
                  />
                </td>
              </tr>
            </tbody>
          </VTable>
        </div>
        <div v-if="feedLogsSafe.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
          <span class="text-caption text-medium-emphasis">
            Showing {{ (currentPageFeed - 1) * itemsPerPage + 1 }} - {{ Math.min(currentPageFeed * itemsPerPage, feedLogsSafe.length) }} of {{ feedLogsSafe.length }} records
          </span>
          <VPagination
            v-if="totalPagesFeed > 1"
            v-model="currentPageFeed"
            :length="totalPagesFeed"
            :total-visible="5"
            density="comfortable"
            size="small"
            show-first-last-page
          />
        </div>
      </VCardText>
    </VCard>

    <RmEntryModal
      :is-open="isCreateModalOpen"
      :edit-id="editingEntryId"
      @close="isCreateModalOpen = false; editingEntryId = null"
      @saved="fetchData"
    />
    <TransferModal
      :is-open="isTransferModalOpen"
      @close="isTransferModalOpen = false"
      @saved="fetchData"
    />

    <!-- Modal for showing specific child tanks -->
    <VDialog v-model="isChildTanksModalOpen" max-width="500">
      <VCard>
        <VCardTitle class="bg-neutral-50 text-uppercase text-body-2 font-weight-bold py-3">
          Specific SLOC / Tanks Selected
        </VCardTitle>
        <VDivider />
        <VCardText class="py-4">
          <div class="mb-4">
            <strong>Storage Location:</strong> {{ selectedEntry?.sloc_description || 'N/A' }}
          </div>
          <div>
            <strong>Tank Farm Number:</strong>
            <div class="d-flex flex-wrap ga-1 mt-2">
              <VChip
                v-for="(tank, idx) in (selectedEntry?.sloc_tank_number ? selectedEntry.sloc_tank_number.split(',') : [])"
                :key="idx"
                color="primary"
                variant="flat"
                size="small"
                class="mr-1 mb-1"
              >
                {{ tank.trim() }}
              </VChip>
            </div>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="text" @click="isChildTanksModalOpen = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
<!--
    <SubSlocEditModal
      v-model:is-open="isSlocModalOpen"
      :id-head="selectedEntry?.id_balance_head"
      :id-sloc="selectedEntry?.raw_id_sloc"
      :main-sloc="selectedEntry?.tank_name || ''"
      @success="isSlocModalOpen = false; fetchData()"
    />
    <MaterialDocModal
      v-model:is-open="isMatlDocModalOpen"
      :id="matlDocEntry?.id_balance_head"
      :current-value="matlDocMode === 'po' ? matlDocEntry?.po_so : matlDocEntry?.material_document"
      :mode="matlDocMode"
      @success="isMatlDocModalOpen = false; fetchData()"
    />
-->
  </div>
</template>

<script setup>
import { useConfirmStore } from '@/stores/confirm.js'
import { ref, computed, onMounted, watch } from 'vue'
import { useTsRawRmEntryStore } from '@/modules/ts-raw/stores'
import { usePlantSelectionStore } from '@/stores/plant.js'
import PlantSelector from '@/modules/shared/components/PlantSelector.vue'
import RmEntryModal from '@/modules/ts-raw/components/RmEntryModal.vue'
import TransferModal from '@/modules/ts-raw/components/TransferModal.vue'
// import SubSlocEditModal from '@/modules/ts-raw/components/SubSlocEditModal.vue'
// import MaterialDocModal from '@/modules/ts-raw/components/MaterialDocModal.vue'
import { useToastStore } from '@/stores/toast.js'

const store = useTsRawRmEntryStore()
const confirmStore = useConfirmStore()
const toastStore = useToastStore()
const plantSelectionStore = usePlantSelectionStore()

const isCreateModalOpen = ref(false)
const isTransferModalOpen = ref(false)
const isSlocModalOpen = ref(false)
const isMatlDocModalOpen = ref(false)
const matlDocMode = ref('matlDoc')
const matlDocEntry = ref(null)
const selectedEntry = ref(null)
const editingEntryId = ref(null)
const isChildTanksModalOpen = ref(false)

onMounted(() => {
  fetchData()
})

const itemsPerPage = 5
const currentPageStorage = ref(1)
const currentPageFeed = ref(1)

const loading = computed(() => store.loading)
const feedLoading = computed(() => store.feedLoading)
const entries = computed(() => store.entries)
const hasEntries = computed(() => entries.value.length > 0)

const totalPagesStorage = computed(() => store.pagination.lastPage)

const feedLogsSafe = computed(() =>
  Array.isArray(store.feedLogs) ? store.feedLogs : []
)

const paginatedFeedLogs = computed(() => {
  const start = (currentPageFeed.value - 1) * itemsPerPage
  return feedLogsSafe.value.slice(start, start + itemsPerPage)
})

const totalPagesFeed = computed(() => {
  return Math.ceil(feedLogsSafe.value.length / itemsPerPage)
})

function buildPlantParams() {
  const id = plantSelectionStore.selectedPlantId
  return { id_plant: id === null || id === undefined || id === '' ? 0 : id }
}

async function fetchData() {
  const params = buildPlantParams()
  currentPageStorage.value = 1
  currentPageFeed.value = 1

  await Promise.all([
    store.fetchEntries({ ...params, page: 1, per_page: itemsPerPage }),
    store.fetchFeedLogs(params),
    store.fetchTanks(params, true),
    store.fetchMaterials(),
    store.searchSuppliers('')
  ])
}

async function fetchStoragePage() {
  const params = buildPlantParams()
  await store.fetchEntries({ ...params, page: currentPageStorage.value, per_page: itemsPerPage })
}

async function changeStoragePage(p) {
  if (p < 1 || p > totalPagesStorage.value) return
  currentPageStorage.value = p
  await fetchStoragePage()
}

function openCreateModal() {
  isTransferModalOpen.value = false
  isCreateModalOpen.value = true
}

function openTransferModal() {
  isCreateModalOpen.value = false
  isTransferModalOpen.value = true
}

function openUpdateModal(entry) {
  editingEntryId.value = entry.id_balance_head
  isCreateModalOpen.value = true
}

function openSlocEdit(entry) {
  selectedEntry.value = entry
  isSlocModalOpen.value = true
}

function openChildTanksModal(entry) {
  selectedEntry.value = entry
  isChildTanksModalOpen.value = true
}

function openMatlDocEdit(entry) {
  matlDocEntry.value = entry
  matlDocMode.value = 'matlDoc'
  isMatlDocModalOpen.value = true
}

function openPoEdit(entry) {
  matlDocEntry.value = entry
  matlDocMode.value = 'po'
  isMatlDocModalOpen.value = true
}

async function deactivateEntry(id) {
  const isConfirmed = await confirmStore.show({
    title: 'Are you sure?',
    message: 'De-Activate this data'
  })

  if (isConfirmed) {
    try {
      await store.deactivateEntry(id)
      toastStore.success('Entry has been deactivated.')
      fetchData()
    } catch (error) {
      toastStore.error(error.message || 'Failed to deactivate')
    }
  }
}

async function deactivateTransfer(id) {
  const isConfirmed = await confirmStore.show({
    title: 'Are you sure?',
    message: 'De-Activate this feed log entry?'
  })

  if (isConfirmed) {
    try {
      await store.deleteFeedLog(id)
      toastStore.success('Feed log entry has been deactivated.')
      fetchData()
    } catch (error) {
      toastStore.error(error.message || 'Failed to deactivate')
    }
  }
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString()
}

function formatSuppliers(supplierString) {
  if (!supplierString) return '-'
  return supplierString.split(' | ').join('\n')
}
</script>

