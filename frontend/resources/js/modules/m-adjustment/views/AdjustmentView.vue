<template>
  <div class="pa-6">
    <VCard class="mb-4">
      <VCardTitle class="pa-5 pb-2">
        <h1 class="text-h6 font-weight-bold">Adjustment - {{ adjMode === 'wip' ? 'WIP' : 'WAREHOUSE' }}</h1>
      </VCardTitle>
      <VCardText>
        <div class="d-flex flex-wrap ga-2">
          <VBtn
            :color="adjMode === 'wip' ? 'primary' : 'secondary'"
            :variant="adjMode === 'wip' ? 'flat' : 'tonal'"
            prepend-icon="ri-bar-chart-line"
            @click="switchMode('wip')"
          >
            WIP Adjustment
          </VBtn>
          <VBtn
            :color="adjMode === 'wh' ? 'primary' : 'secondary'"
            :variant="adjMode === 'wh' ? 'flat' : 'tonal'"
            prepend-icon="ri-bar-chart-line"
            @click="switchMode('wh')"
          >
            WAREHOUSE Adjustment
          </VBtn>
        </div>
      </VCardText>
    </VCard>

    <VCard class="mb-4">
      <VCardText>
        <div class="d-flex flex-wrap ga-2 mb-4 pb-3 border-b">
          <template v-if="adjMode === 'wip'">
            <VBtn color="primary" prepend-icon="ri-edit-line" @click="openLastRecord">Adjustment Last Record</VBtn>
            <VBtn color="primary" prepend-icon="ri-stack-line" @click="openInit">Stock Initialization</VBtn>
            <VBtn color="primary" prepend-icon="ri-calendar-line" @click="openPeriod">Period Adjustment</VBtn>
            <VBtn color="primary" prepend-icon="ri-user-line" @click="openSupplier">Supplier Adjustment</VBtn>
          </template>
          <template v-else>
            <VBtn color="primary" prepend-icon="ri-edit-line" @click="openLastRecord">New Adjustment</VBtn>
            <VBtn color="primary" prepend-icon="ri-stack-line" @click="openInit">Stock Initialization</VBtn>
          </template>
        </div>

        <div v-if="loading" class="pa-4">
          <VSkeletonLoader type="table-thead, table-tbody@5" :loading="true" />
        </div>
        <template v-else>
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:40px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'adjust_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('adjust_no')">Adjustment No<VIcon v-if="sortKey==='adjust_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material_document' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material_document')">Material Document<VIcon v-if="sortKey==='material_document'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date')">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material')">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc')">SLoc<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('trace_no')">Adjusted Batch<VIcon v-if="sortKey==='trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'adjustment' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('adjustment')">Adjustment (MT)<VIcon v-if="sortKey==='adjustment'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier')">Supplier / Batch SAP / Qty<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'created_at' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('created_at')">Created At<VIcon v-if="sortKey==='created_at'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'created_by' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('created_by')">Created By<VIcon v-if="sortKey==='created_by'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center">Action</th>
                </tr>
              </thead>
              <tbody v-if="data.length > 0">
                <tr v-for="(row, i) in sortedList" :key="row.id_adjust_head">
                  <td class="text-center text-medium-emphasis font-monospace text-caption">{{ (listMeta.page - 1) * listMeta.perPage + i + 1 }}</td>
                  <td class="font-monospace text-caption">{{ row.adjust_no }}</td>
                  <td class="font-monospace text-caption">{{ row.material_document || row.material_doc || '-' }}</td>
                  <td class="font-monospace text-caption">{{ row.entry_date }}</td>
                  <td class="text-caption font-weight-medium text-truncate" style="max-width:160px" :title="row.material">{{ row.material }}</td>
                  <td class="font-monospace text-caption">{{ row.sloc || row.tank || '-' }}</td>
                  <td class="font-monospace text-caption">{{ row.trace_no || '-' }}</td>
                  <td class="text-right font-monospace text-caption">{{ row.adjustment || '-' }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="row.supplier">{{ row.supplier || '-' }}</td>
                  <td class="text-caption text-medium-emphasis">{{ row.created_at || '-' }}</td>
                  <td class="text-caption">{{ row.created_by || '-' }}</td>
                  <td class="text-center">
                    <div class="d-flex ga-1 justify-center">
                      <VBtn icon="ri-eye-line" size="x-small" color="primary" variant="tonal" @click="viewDetail(row)" />
                      <VBtn v-if="row.status === 1" icon="ri-checkbox-circle-line" size="x-small" color="success" variant="tonal" @click="approve(row)" />
                      <VBtn v-if="row.status === 2" icon="ri-play-circle-line" size="x-small" color="warning" variant="tonal" @click="execute(row)" />
                      <VBtn v-if="row.status === 1 || row.status === 2" icon="ri-close-circle-line" size="x-small" color="error" variant="tonal" @click="cancel(row)" />
                    </div>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr>
                  <td colspan="12" class="text-center pa-8">
                    <VIcon icon="ri-tune-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No adjustment records found.</p>
                  </td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="data.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 custom-pagination-footer gap-2">
            <div class="d-flex align-center ga-2">
              <span class="text-caption text-medium-emphasis">
                Showing {{ (listMeta.page - 1) * listMeta.perPage + 1 }} - {{ Math.min(listMeta.page * listMeta.perPage, listMeta.total) }} of {{ listMeta.total }} records
              </span>
              <VSelect
                v-model="perPage"
                :items="[5, 10, 15, 20]"
                density="compact"
                variant="outlined"
                hide-details
                style="width:80px"
              />
            </div>
            <VPagination
              v-slot:prev="{ onClick, disabled }"
              v-if="listMeta.lastPage > 1"
              v-model="listMeta.page"
              :length="listMeta.lastPage"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
              @update:model-value="changePage"
            />
          </div>
          <div v-else class="px-3 py-2 text-caption text-medium-emphasis custom-pagination-footer">
            0 records
          </div>
        </template>
      </VCardText>
    </VCard>

    <VDialog v-model="showLastRecordModal" max-width="500">
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">{{ adjMode === 'wip' ? 'Adjustment Last Record' : 'New Adjustment' }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showLastRecordModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense>
            <VCol cols="12" md="6">
              <VTextField :model-value="lastRecordForm.mode" label="Entry Mode" readonly density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="lastRecordForm.entryDate" label="Adjustment Entry Date" type="date" density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12">
              <VSelect
                v-model="lastRecordForm.idMaterial"
                label="Material"
                :items="materialOptions"
                item-title="material"
                item-value="id_material"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="lastRecordForm.idTank"
                label="Sloc"
                :items="tankOptions"
                item-title="tank"
                item-value="id_tank"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol v-if="adjMode === 'wip' && activeSpecificTanks.length > 0" cols="12">
              <VSelect
                v-model="lastRecordForm.idSloc"
                label="Specific Sloc"
                :items="specificTankOptions"
                item-title="tankNo"
                item-value="id_tank_tail"
                multiple
                chips
                closable-chips
                variant="outlined"
                density="compact"
                placeholder="Select Specific Sloc"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model.number="lastRecordForm.qty"
                label="New Balance Qty (MT)"
                type="number"
                step="0.001"
                min="0"
                density="compact"
                variant="outlined"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showLastRecordModal = false">Cancel</VBtn>
          <VBtn color="primary" :loading="saving" @click="submitLastRecord">{{ saving ? 'Saving...' : 'Save' }}</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showInitModal" max-width="800" scrollable>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">Stock Initialization Entry</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showInitModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense>
            <VCol cols="12" md="4">
              <VTextField :model-value="initForm.mode" label="Entry Mode" readonly density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="4">
              <VTextField v-model="initForm.entry_date" label="Date (Auto Detect)" type="date" density="compact" variant="outlined" @update:model-value="onInitDateChange" />
            </VCol>
            <VCol v-if="adjMode === 'wip'" cols="12" md="4">
              <VTextField v-model="initForm.material_doc" label="Material Document (SAP)" density="compact" variant="outlined" />
            </VCol>
            <VCol v-if="adjMode === 'wh'" cols="12" md="4">
              <VTextField v-model="initForm.po_no" label="PO No" density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="initForm.tank"
                label="Sloc"
                :items="tankOptions"
                item-title="tank"
                item-value="id_tank"
                density="compact"
                variant="outlined"
                @update:model-value="onInitTankChange"
              />
            </VCol>
            <VCol v-if="adjMode === 'wh'" cols="12" md="6">
              <VTextField v-model="initForm.batch_no" label="Batch No" density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="initForm.id_material"
                label="Material (Do not change after input supplier!)"
                :items="materialOptions"
                item-title="material"
                item-value="id_material"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol v-if="adjMode === 'wip' && activeSpecificTanks.length > 0" cols="12" md="6">
              <VSelect
                v-model="initForm.tankNo"
                label="Specific Sloc"
                :items="specificTankOptions"
                item-title="tankNo"
                item-value="id_tank_tail"
                multiple
                chips
                closable-chips
                variant="outlined"
                density="compact"
                placeholder="Select Specific Sloc"
              />
            </VCol>
          </VRow>

          <VRow class="mt-2 align-end justify-space-between" dense>
            <VCol cols="auto">
              <div class="d-flex ga-2">
                <VBtn color="secondary" prepend-icon="ri-add-line" @click="openInitSupplierForm">Add Supplier &amp; Qty</VBtn>
                <VBtn color="primary" :loading="saving" @click="submitInit">{{ saving ? 'Saving...' : 'Save Entry' }}</VBtn>
              </div>
            </VCol>
            <VCol cols="auto">
              <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Total Qty (MT)</label>
              <VTextField :model-value="initForm.qty" readonly density="compact" variant="outlined" class="mt-1 text-right" style="width:144px" />
            </VCol>
          </VRow>

          <VAlert v-if="showInitSupplierForm" type="info" variant="tonal" class="mt-3">
            <h4 class="text-body-2 font-weight-bold mb-2">Add Supplier</h4>
            <VRow dense>
              <VCol cols="12" md="4">
                <VSelect
                  v-model="initSupplierForm.idSupplier"
                  label="Supplier"
                  :items="supplierOptions"
                  item-title="supplier"
                  item-value="id_supplier"
                  density="compact"
                  variant="outlined"
                />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField v-model="initSupplierForm.batchSap" label="Batch SAP" placeholder="Enter Batch SAP" density="compact" variant="outlined" />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField v-model.number="initSupplierForm.qty" label="Qty (MT)" type="number" step="0.001" density="compact" variant="outlined" />
              </VCol>
            </VRow>
            <div class="d-flex justify-end ga-2 mt-3">
              <VBtn size="small" variant="outlined" color="medium-emphasis" @click="showInitSupplierForm = false">Cancel</VBtn>
              <VBtn size="small" color="primary" :loading="saving" @click="addSupplierToInit">Add</VBtn>
            </div>
          </VAlert>

          <div class="mt-3 overflow-x-auto border rounded">
            <VTable density="compact" class="text-caption">
              <thead class="bg-neutral-50">
                <tr>
                  <th class="text-center" style="width:40px">No</th>
                  <th class="text-center" style="width:60px">Action</th>
                  <th>Material</th>
                  <th>Supplier</th>
                  <th class="text-center">Batch SAP</th>
                  <th class="text-right">Qty (MT)</th>
                </tr>
              </thead>
              <tbody v-if="initSupplierList.length === 0">
                <tr><td colspan="6" class="text-center pa-4 text-disabled">No supplier added yet.</td></tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(item, i) in initSupplierList" :key="item.idTail">
                  <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                  <td class="text-center">
                    <VBtn icon="ri-delete-bin-line" size="x-small" color="error" variant="text" @click="removeSupplierFromInit(item.idTail)" />
                  </td>
                  <td>{{ item.material || '-' }}</td>
                  <td>{{ item.supplier || item.idSupplier }}</td>
                  <td class="text-center font-monospace">{{ item.batch_sap || '-' }}</td>
                  <td class="text-right font-monospace">{{ item.qty }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showInitModal = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showPeriodModal" max-width="960" scrollable>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">Stock Period Adjustment Entry</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showPeriodModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <div class="mb-4">
            <VBtn color="primary" prepend-icon="ri-add-line" @click="openNewPeriodForm">Create New Period</VBtn>
          </div>

          <VAlert v-if="showNewPeriodForm" type="info" variant="tonal" class="mb-4">
            <h4 class="text-body-2 font-weight-bold mb-2">New Period Entry</h4>
            <VRow dense>
              <VCol cols="12" md="4">
                <VTextField :model-value="periodNewForm.mode" label="Entry Mode" readonly density="compact" variant="outlined" />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField v-model="periodNewForm.period" label="Period (Month &amp; Year)" type="month" density="compact" variant="outlined" />
              </VCol>
              <VCol cols="12" md="4">
                <VTextField v-model="periodNewForm.batch" label="Batch SAP" density="compact" variant="outlined" />
              </VCol>
            </VRow>
            <div class="d-flex justify-end ga-2 mt-2">
              <VBtn size="small" variant="outlined" color="medium-emphasis" @click="showNewPeriodForm = false">Cancel</VBtn>
              <VBtn size="small" color="primary" :loading="saving" @click="submitNewPeriod">Save Period</VBtn>
            </div>
          </VAlert>

          <h3 class="text-body-2 font-weight-bold text-medium-emphasis mb-2">Period History</h3>
          <div class="overflow-x-auto border rounded">
            <VTable density="compact" class="text-body-2">
              <thead class="bg-neutral-50">
                <tr>
                  <th class="text-center" style="width:40px">No</th>
                  <th class="text-center" style="width:160px">Action</th>
                  <th>Batch</th>
                  <th>Period</th>
                  <th class="text-center">Uploaded File</th>
                  <th class="text-center">Adjust Status</th>
                  <th class="text-center">Lock Status</th>
                  <th>Created By</th>
                  <th>Created At</th>
                </tr>
              </thead>
              <tbody v-if="periodHeaders.length === 0">
                <tr><td colspan="9" class="text-center pa-6 text-disabled">No period history found.</td></tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(row, i) in periodHeaders" :key="row.id_pspa_head">
                  <td class="text-center text-medium-emphasis text-caption">{{ i + 1 }}</td>
                  <td class="text-center">
                    <div class="d-flex ga-1 justify-center align-center">
                      <VBtn icon="ri-eye-line" size="x-small" color="primary" variant="text" title="View Detail" @click="viewPeriodDetail(row)" />
                      
                      <!-- Hidden file input for uploading Excel to this specific row -->
                      <input
                        :id="'file-input-' + row.id_pspa_head"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        style="display: none"
                        @change="handleRowFileChange($event, row)"
                      />
                      <VBtn
                        v-if="row.status !== 3"
                        icon="ri-upload-2-line"
                        size="x-small"
                        color="success"
                        variant="text"
                        title="Upload Excel"
                        @click="triggerRowFileUpload(row.id_pspa_head)"
                      />

                      <VBtn v-if="row.status !== 3" icon="ri-lock-line" size="x-small" color="warning" variant="text" title="Lock Period" @click="lockPeriod(row.id_pspa_head)" />
                      <VBtn v-if="row.status !== 3" icon="ri-delete-bin-line" size="x-small" color="error" variant="text" title="Delete Period" @click="deletePeriod(row.id_pspa_head)" />
                    </div>
                  </td>
                  <td class="font-monospace text-caption">{{ row.batch_sap || '-' }}</td>
                  <td class="font-weight-medium">{{ row.period }}</td>
                  <td class="text-center">
                    <VChip
                      size="x-small"
                      :color="row.uploaded_file === 1 ? 'success' : 'error'"
                      variant="tonal"
                    >
                      {{ row.uploaded_file === 1 ? 'Yes' : 'No' }}
                    </VChip>
                  </td>
                  <td class="text-center">
                    <VChip
                      size="x-small"
                      :color="row.adjust_status === 1 ? 'success' : 'warning'"
                      variant="tonal"
                    >
                      {{ row.adjust_status === 1 ? 'Adjusted' : 'Pending' }}
                    </VChip>
                  </td>
                  <td class="text-center">
                    <VChip
                      size="x-small"
                      :color="row.status === 3 ? 'success' : 'warning'"
                      variant="tonal"
                    >
                      {{ row.status === 3 ? 'Locked' : 'Open' }}
                    </VChip>
                  </td>
                  <td class="text-caption">{{ row.created_by }}</td>
                  <td class="text-caption text-medium-emphasis">{{ row.created_at }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showPeriodModal = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showPeriodDetailModal" max-width="1200" scrollable>
      <VCard v-if="selectedPeriodHeader">
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">View Uploaded Data &amp; Adjustment - Period: {{ selectedPeriodHeader.period }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showPeriodDetailModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense class="mb-4">
            <VCol cols="12" md="4">
              <VTextField :model-value="selectedPeriodHeader.period" label="Period" readonly density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="4">
              <VTextField :model-value="selectedPeriodHeader.batch_sap || '-'" label="Batch SAP" readonly density="compact" variant="outlined" />
            </VCol>
          </VRow>

          <div class="d-flex ga-2 mb-4 pb-3 border-b">
            <VBtn
              color="primary"
              prepend-icon="ri-calculator-line"
              :disabled="selectedPeriodHeader.status === 3 || saving"
              @click="calculatePeriodOnHand(selectedPeriodHeader.id_pspa_head)"
            >
              1. Calc Qty (On-Hand)
            </VBtn>
            <VBtn
              color="primary"
              prepend-icon="ri-play-circle-line"
              :disabled="selectedPeriodHeader.status === 3 || saving"
              @click="doPeriodAdjustment(selectedPeriodHeader.id_pspa_head)"
            >
              2. Do Adjustment
            </VBtn>
          </div>

          <div class="overflow-x-auto border rounded">
            <VTable density="compact" class="text-caption">
              <thead class="bg-neutral-50">
                <tr>
                  <th class="text-center" style="width:40px">No</th>
                  <th class="text-center">Plant</th>
                  <th class="text-center">Tank</th>
                  <th>Sloc</th>
                  <th>Material</th>
                  <th class="text-right">Qty (PSPA)</th>
                  <th class="text-right">Qty (On-Hand)</th>
                  <th class="text-right">Total</th>
                  <th class="text-center">Adj. Type</th>
                  <th class="text-center">Adj. No</th>
                  <th class="text-center">Adj. Status</th>
                  <th class="text-center">CalOnHand At</th>
                </tr>
              </thead>
              <tbody v-if="!periodViewData || periodViewData.length === 0">
                <tr><td colspan="12" class="text-center pa-4 text-disabled">No detail rows found.</td></tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(item, i) in periodViewData" :key="item.id_pspa_detail">
                  <td class="text-center text-medium-emphasis">{{ i + 1 }}</td>
                  <td class="text-center">{{ item.plant || '-' }}</td>
                  <td class="text-center">{{ item.tank || '-' }}</td>
                  <td>{{ item.sloc || '-' }}</td>
                  <td>{{ item.material || '-' }}</td>
                  <td class="text-right font-monospace">{{ parseFloat(item.qty_pspa || 0).toFixed(3) }}</td>
                  <td class="text-right font-monospace">{{ item.qty_onhand !== null ? parseFloat(item.qty_onhand).toFixed(3) : '-' }}</td>
                  <td class="text-right font-monospace">{{ item.total !== null ? parseFloat(item.total).toFixed(3) : '-' }}</td>
                  <td class="text-center">{{ item.adj_type || '-' }}</td>
                  <td class="text-center font-monospace">{{ item.adjust_number || '-' }}</td>
                  <td class="text-center">
                    <VChip
                      v-if="item.adjust_status !== null"
                      size="x-small"
                      :color="item.adjust_status === 1 ? 'success' : item.adjust_status === 2 ? 'error' : 'warning'"
                      variant="tonal"
                    >
                      {{ item.adjust_status === 1 ? 'Success' : item.adjust_status === 2 ? 'Failed' : 'No Adj' }}
                    </VChip>
                    <span v-else>-</span>
                  </td>
                  <td class="text-center text-medium-emphasis">{{ item.populated_at || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showPeriodDetailModal = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showSupplierModal" max-width="700" scrollable>
      <VCard>
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">Supplier Adjustment Entry</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showSupplierModal = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense>
            <VCol cols="12" md="6">
              <VTextField :model-value="supplierAdjForm.mode" label="Entry Mode" readonly density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model="supplierAdjForm.entryDate" label="Date (Auto Detect)" type="date" density="compact" variant="outlined" />
            </VCol>
            <VCol cols="12">
              <VSelect
                v-model="supplierAdjForm.idMaterial"
                label="Material"
                :items="materialOptions"
                item-title="material"
                item-value="id_material"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="supplierAdjForm.idTank"
                label="Sloc"
                :items="tankOptions"
                item-title="tank"
                item-value="id_tank"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol v-if="adjMode === 'wip' && activeSpecificTanks.length > 0" cols="12">
              <VSelect
                v-model="supplierAdjForm.idSloc"
                label="Specific Sloc"
                :items="specificTankOptions"
                item-title="tankNo"
                item-value="id_tank_tail"
                multiple
                chips
                closable-chips
                variant="outlined"
                density="compact"
                placeholder="Select Specific Sloc"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="supplierAdjForm.adjustType"
                label="Adjustment Type"
                :items="[
                  { value: '-', title: '- Select Adjust Type -' },
                  { value: 'in', title: '- Adjust IN -' },
                  { value: 'out', title: '- Adjust OUT -' }
                ]"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VSelect
                v-model="supplierAdjForm.idSupplier"
                label="Supplier"
                :items="supplierOptions"
                item-title="supplier"
                item-value="id_supplier"
                density="compact"
                variant="outlined"
              />
              <span v-if="supplierAdjForm.idSupplier" class="text-caption text-medium-emphasis mt-1 d-block">Stock (MT): {{ supplierAdjStock }}</span>
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="supplierAdjForm.batchSap"
                label="Batch SAP"
                density="compact"
                variant="outlined"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField v-model.number="supplierAdjForm.qty" label="Adjustment Qty (MT)" type="number" step="0.1" density="compact" variant="outlined" />
            </VCol>
          </VRow>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showSupplierModal = false">Cancel</VBtn>
          <VBtn color="primary" :loading="saving" @click="submitSupplierAdj">{{ saving ? 'Saving...' : 'Save Entry' }}</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="showDetail" max-width="700" scrollable>
      <VCard v-if="detailData">
        <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
          <span class="text-h6 font-weight-bold">Adjustment Detail - {{ detailData.header?.adjust_no }}</span>
          <VBtn icon="ri-close-line" variant="text" size="small" color="medium-emphasis" @click="showDetail = false" />
        </VCardTitle>
        <VDivider />
        <VCardText class="pa-5">
          <VRow dense class="mb-4">
            <VCol cols="6"><span class="text-caption text-medium-emphasis">Material</span><p class="font-weight-medium text-body-2">{{ detailData.header?.material || '-' }}</p></VCol>
            <VCol cols="6"><span class="text-caption text-medium-emphasis">Entry Date</span><p class="font-weight-medium text-body-2">{{ detailData.header?.entry_date }}</p></VCol>
            <VCol cols="6"><span class="text-caption text-medium-emphasis">SLoc</span><p class="font-weight-medium text-body-2">{{ detailData.header?.sloc || '-' }}</p></VCol>
            <VCol cols="6">
              <span class="text-caption text-medium-emphasis">Status</span>
              <p>
                <VChip :color="statusColor(detailData.header?.status)" size="small" variant="tonal">
                  {{ detailData.header?.status_label || detailData.header?.status }}
                </VChip>
              </p>
            </VCol>
            <VCol cols="6"><span class="text-caption text-medium-emphasis">Before</span><p class="font-monospace text-body-2">{{ detailData.header?.before_adjust }} MT</p></VCol>
            <VCol cols="6"><span class="text-caption text-medium-emphasis">After</span><p class="font-monospace text-body-2">{{ detailData.header?.after_adjust }} MT</p></VCol>
          </VRow>
          <div v-if="detailData.details?.length" class="border-t pt-3">
            <h3 class="text-body-2 font-weight-bold text-medium-emphasis mb-2">Supplier Details</h3>
            <div class="d-flex flex-column ga-2">
              <VCard v-for="d in detailData.details" :key="d.id_adjust_detail" variant="outlined" class="pa-3">
                <p class="font-weight-medium text-body-2">{{ d.supplier || 'Unknown' }}</p>
                <p class="text-caption text-medium-emphasis">Batch: {{ d.batch_sap || '-' }} | Before: {{ d.before_adjust }} → After: {{ d.after_adjust }}</p>
              </VCard>
            </div>
          </div>
        </VCardText>
        <VDivider />
        <VCardActions class="pa-5 pt-3 justify-end gap-2">
          <VBtn variant="outlined" color="medium-emphasis" @click="showDetail = false">Close</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog :model-value="!!confirmMsg" max-width="400" @update:model-value="confirmMsg = ''">
      <VCard>
        <VCardText class="text-center pa-6">
          <VIcon :icon="confirmIcon" class="mb-3 text-warning" size="48" />
          <p class="text-body-1 font-weight-medium mb-4">{{ confirmMsg }}</p>
          <div class="d-flex justify-center ga-2">
            <VBtn variant="outlined" color="medium-emphasis" @click="confirmMsg = ''">Cancel</VBtn>
            <VBtn color="primary" :loading="saving" @click="executeConfirm">{{ saving ? 'Processing...' : 'Confirm' }}</VBtn>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdjustmentStore } from '../stores/adjustmentStore'
import { useToastStore } from '@/stores/toast'
import adjustmentApi from '@/modules/m-adjustment/services'

const store = useAdjustmentStore()
const toast = useToastStore()

const { data, loading, activeMaterials, activeTanks, activeSpecificTanks, periodHeaders, searchSuppliersList, listMeta } = storeToRefs(store)

const adjMode = ref('wip')
const saving = ref(false)
const showDetail = ref(false)
const detailData = ref(null)
const confirmMsg = ref('')
const confirmType = ref('')
const confirmAction = ref(null)

const showLastRecordModal = ref(false)
const showInitModal = ref(false)
const showInitSupplierForm = ref(false)
const showPeriodModal = ref(false)
const showNewPeriodForm = ref(false)
const showSupplierModal = ref(false)
const showPeriodDetailModal = ref(false)
const selectedPeriodHeader = ref(null)

const perPage = ref(listMeta.value.perPage || 10)

const sortKey = ref(null)
const sortDir = ref(null)

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
    if (sortDir.value === 'asc') {
      sortDir.value = 'desc'
    } else if (sortDir.value === 'desc') {
      sortKey.value = null
      sortDir.value = null
    }
  } else {
    sortKey.value = key
    sortDir.value = detectColumnType(key) === 'text' ? 'asc' : 'desc'
  }
}

const sortedList = computed(() => {
  if (!sortKey.value || !sortDir.value) return data.value
  const key = sortKey.value
  const dir = sortDir.value
  const rows = [...data.value]
  const type = detectColumnType(key)
  return rows.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    if (type === 'number') return dir === 'asc' ? va - vb : vb - va
    return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va))
  })
})

const periodNewFile = ref(null)

const lastRecordForm = reactive({
  mode: 'ADD',
  entryDate: new Date().toISOString().split('T')[0],
  idMaterial: '',
  idTank: '',
  idSloc: [],
  qty: 0
})

const initForm = reactive({
  mode: 'ADD',
  entry_no: '',
  entry_date: new Date().toISOString().split('T')[0],
  material_doc: '',
  po_no: '',
  batch_no: '',
  id_material: '',
  tank: '',
  tankNo: [],
  qty: '0.000'
})
const initSupplierForm = reactive({ idSupplier: '', batchSap: '', qty: 0 })
const initSupplierList = ref([])

const periodNewForm = reactive({ mode: 'ADD', period: '', batch: '' })

const supplierAdjForm = reactive({
  mode: 'ADD',
  entryDate: new Date().toISOString().split('T')[0],
  idMaterial: '',
  idTank: '',
  idSloc: [],
  adjustType: '-',
  idSupplier: '',
  batchSap: '',
  qty: 0
})
const supplierAdjStock = ref('N/A')
const supplierAdjBatchOptions = ref([])

const materialOptions = computed(() => activeMaterials.value || [])
const tankOptions = computed(() => {
  return (activeTanks.value || []).map(t => ({
    id_tank: t.id_tank || t.id_sloc,
    tank: String(t.tank || t.description || t.id_sloc || t.id_tank || 'Unknown')
  }))
})
const specificTankOptions = computed(() => {
  return (activeSpecificTanks.value || []).map(t => ({
    id_tank_tail: t.id_tank_tail || t.id_sloc,
    tankNo: String(t.tankNo || t.tankName || t.description || t.id_sloc || t.id_tank_tail || 'Unknown')
  }))
})
const supplierOptions = computed(() => searchSuppliersList.value || [])

const confirmIcon = computed(() => {
  if (confirmType.value === 'approve') return 'ri-checkbox-circle-line'
  if (confirmType.value === 'cancel') return 'ri-close-circle-line'
  return 'ri-play-circle-line'
})

onMounted(() => {
  loadData()
  loadFormOptions()
})

watch(adjMode, () => {
  loadData()
  loadFormOptions()
})

watch(perPage, (val) => {
  listMeta.value.perPage = val
  listMeta.value.page = 1
  loadData()
})

watch(() => lastRecordForm.idTank, async (val) => {
  lastRecordForm.idSloc = []
  if (!val) {
    activeSpecificTanks.value = []
    return
  }
  await store.fetchActiveSpecificTanks(val)
})

watch(() => supplierAdjForm.idMaterial, () => {
  supplierAdjForm.idSupplier = ''
  supplierAdjForm.batchSap = ''
  supplierAdjStock.value = 'N/A'
  supplierAdjBatchOptions.value = []
})

watch(() => supplierAdjForm.idTank, async (val) => {
  supplierAdjForm.idSupplier = ''
  supplierAdjForm.batchSap = ''
  supplierAdjStock.value = 'N/A'
  supplierAdjBatchOptions.value = []
  supplierAdjForm.idSloc = []
  if (!val) {
    activeSpecificTanks.value = []
    return
  }
  await store.fetchActiveSpecificTanks(val)
})

watch(() => supplierAdjForm.idSupplier, (val) => {
  if (!val) {
    supplierAdjStock.value = 'N/A'
    supplierAdjBatchOptions.value = []
    supplierAdjForm.batchSap = ''
    return
  }
  fetchSupplierAdjStockAndBatches().then(() => {
    if (supplierAdjBatchOptions.value.length > 0 && !supplierAdjForm.batchSap) {
      supplierAdjForm.batchSap = supplierAdjBatchOptions.value[0].value
    }
  })
})

const switchMode = (mode) => {
  adjMode.value = mode
  store.setPage(1)
}

const loadData = async () => {
  await store.fetchList({
    adj_type: adjMode.value === 'wip' ? 'wip' : 'wh',
    page: listMeta.value.page,
    per_page: listMeta.value.perPage
  })
}

const changePage = async (p) => {
  if (p < 1 || p > listMeta.value.lastPage) return
  store.setPage(p)
  await loadData()
}

const loadFormOptions = async () => {
  await store.fetchSuppliers({ supplier: '' })
  store.fetchActiveMaterials()
  store.fetchActiveTanks()
}

function toggleTankNo(id, checked) {
  if (checked) {
    if (!initForm.tankNo.includes(id)) {
      initForm.tankNo = [...initForm.tankNo, id]
    }
  } else {
    initForm.tankNo = initForm.tankNo.filter(t => t !== id)
  }
}

const openLastRecord = () => {
  Object.assign(lastRecordForm, {
    mode: 'ADD',
    entryDate: new Date().toISOString().split('T')[0],
    idMaterial: '',
    idTank: '',
    idSloc: [],
    qty: 0
  })
  showLastRecordModal.value = true
}

const openInit = () => {
  Object.assign(initForm, {
    mode: 'ADD',
    entry_no: '',
    entry_date: new Date().toISOString().split('T')[0],
    material_doc: '',
    po_no: '',
    batch_no: '',
    id_material: '',
    tank: '',
    tankNo: [],
    qty: '0.000'
  })
  initSupplierList.value = []
  showInitSupplierForm.value = false
  onInitDateChange()
  showInitModal.value = true
}

const openPeriod = async () => {
  await store.fetchPeriodHeaders()
  showNewPeriodForm.value = false
  showPeriodModal.value = true
}

const openNewPeriodForm = () => {
  periodNewForm.mode = 'ADD'
  periodNewForm.period = ''
  periodNewForm.batch = ''
  periodNewFile.value = null
  showNewPeriodForm.value = true
}

const onPeriodFileChange = (e) => {
  const file = e.target.files[0]
  periodNewFile.value = file || null
}

const openSupplier = () => {
  Object.assign(supplierAdjForm, {
    mode: 'ADD',
    entryDate: new Date().toISOString().split('T')[0],
    idMaterial: '',
    idTank: '',
    idSloc: [],
    adjustType: '-',
    idSupplier: '',
    batchSap: '',
    qty: 0
  })
  supplierAdjStock.value = 'N/A'
  supplierAdjBatchOptions.value = []
  showSupplierModal.value = true
}

const fetchSupplierAdjStockAndBatches = async () => {
  const { idMaterial, idTank, idSupplier } = supplierAdjForm
  if (!idMaterial || !idTank || !idSupplier) {
    supplierAdjStock.value = 'N/A'
    supplierAdjBatchOptions.value = []
    return
  }
  try {
    const [supplierRes, batchRes] = await Promise.all([
      adjustmentApi.getSupplierByFilter({ id_material: idMaterial, id_tank: idTank }),
      adjustmentApi.getBatchBySupplier({ id_material: idMaterial, id_tank: idTank, id_supplier: idSupplier })
    ])
    const suppliers = supplierRes.data?.data || []
    const matched = suppliers.find(s => String(s.id_supplier) === String(idSupplier))
    supplierAdjStock.value = matched ? parseFloat(matched.total_qty || 0).toFixed(3) : '0.000'

    const batches = batchRes.data?.data || []
    supplierAdjBatchOptions.value = batches.map(b => ({
      label: `${b.batch_sap} (Stock: ${parseFloat(b.qty || 0).toFixed(3)} MT)`,
      value: b.batch_sap
    }))
  } catch {
    supplierAdjStock.value = 'N/A'
    supplierAdjBatchOptions.value = []
  }
}

const openInitSupplierForm = () => {
  initSupplierForm.idSupplier = ''
  initSupplierForm.batchSap = ''
  initSupplierForm.qty = 0
  showInitSupplierForm.value = true
}

const submitLastRecord = async () => {
  if (!lastRecordForm.idMaterial || !lastRecordForm.qty) {
    toast.error('Please fill all required fields.')
    return
  }
  saving.value = true
  try {
    const payload = {
      entry_date: lastRecordForm.entryDate,
      id_material: lastRecordForm.idMaterial,
      id_tank: lastRecordForm.idTank,
      id_sloc: lastRecordForm.idSloc,
      qty: lastRecordForm.qty
    }
    const result = adjMode.value === 'wip'
      ? await store.storeAdjustment(payload)
      : await store.storeAdjustmentWhx(payload)
    if (result?.response === 1) {
      toast.success('Adjustment Saved')
      showLastRecordModal.value = false
      loadData()
    } else {
      toast.error(result?.message || 'Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const onInitDateChange = async () => {
  await store.fetchEntryNo({ entry_date: initForm.entry_date })
  if (store.entryNo) initForm.entry_no = store.entryNo
}

const onInitTankChange = async () => {
  if (!initForm.tank) {
    activeSpecificTanks.value = []
    return
  }
  await store.fetchActiveSpecificTanks(initForm.tank)
}

const addSupplierToInit = async () => {
  if (!initSupplierForm.idSupplier || !initSupplierForm.qty) {
    toast.error('Select supplier and enter quantity.')
    return
  }
  if (!initForm.entry_no) await onInitDateChange()
  saving.value = true
  try {
    const res = await store.addEntrySupplier({
      entry_no: initForm.entry_no,
      id_supplier: initSupplierForm.idSupplier,
      batch_sap: initSupplierForm.batchSap,
      qty: initSupplierForm.qty,
      entry_date: initForm.entry_date
    })
    if (res?.response === 1) {
      const supplierName = searchSuppliersList.value.find(s => s.id_supplier == initSupplierForm.idSupplier)?.supplier || initSupplierForm.idSupplier
      initSupplierList.value.push({
        idTail: res.idTail || Date.now(),
        idSupplier: initSupplierForm.idSupplier,
        supplier: supplierName,
        batch_sap: initSupplierForm.batchSap,
        qty: initSupplierForm.qty
      })
      const total = initSupplierList.value.reduce((acc, i) => acc + parseFloat(i.qty || 0), 0)
      initForm.qty = total.toFixed(3)
      initSupplierForm.idSupplier = ''
      initSupplierForm.batchSap = ''
      initSupplierForm.qty = 0
      showInitSupplierForm.value = false
      toast.success('Supplier added')
    } else {
      toast.error(res?.message || 'Failed to add supplier')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const removeSupplierFromInit = async (idTail) => {
  saving.value = true
  try {
    const res = await store.deleteSupplierTemp(idTail)
    if (res?.response === 1) {
      initSupplierList.value = initSupplierList.value.filter(i => i.idTail !== idTail)
      const total = initSupplierList.value.reduce((acc, i) => acc + parseFloat(i.qty || 0), 0)
      initForm.qty = total.toFixed(3)
      toast.success('Supplier removed')
    } else {
      toast.error(res?.message || 'Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const submitInit = async () => {
  if (!initForm.id_material || !initForm.tank) {
    toast.error('Please fill Material and Sloc.')
    return
  }
  saving.value = true
  try {
    const payload = {
      entry_no: initForm.entry_no,
      entry_date: initForm.entry_date,
      material_doc: initForm.material_doc,
      po_no: initForm.po_no,
      batch_no: initForm.batch_no,
      tank: initForm.tank,
      tankNo: initForm.tankNo,
      qty: initForm.qty,
      id_material: initForm.id_material
    }
    const result = adjMode.value === 'wip'
      ? await store.adjustmentInit(payload)
      : await store.adjustmentInitWhx(payload)
    if (result?.response === 1) {
      toast.success('Initialization Successful')
      showInitModal.value = false
      initSupplierList.value = []
      loadData()
    } else {
      toast.error(result?.message || 'Initialization Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const submitNewPeriod = async () => {
  if (!periodNewForm.period) {
    toast.error('Period is required.')
    return
  }
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('period', periodNewForm.period)
    formData.append('batch', periodNewForm.batch)
    
    const result = await store.periodHeadersUpload(formData)
    if (result?.response === 1) {
      toast.success(result?.message || 'Period Header Created Successfully')
      showNewPeriodForm.value = false
      await store.fetchPeriodHeaders()
    } else {
      toast.error(result?.message || 'Failed to create period header')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const triggerRowFileUpload = (idHead) => {
  const fileInput = document.getElementById('file-input-' + idHead)
  if (fileInput) fileInput.click()
}

const handleRowFileChange = async (event, row) => {
  const files = event.target.files
  if (!files || files.length === 0) return
  const file = files[0]
  
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('id_head', row.id_pspa_head)
    formData.append('file', file)
    
    const result = await store.periodHeadersUpload(formData)
    if (result?.response === 1) {
      toast.success(result?.message || 'Excel File Uploaded Successfully')
      await store.fetchPeriodHeaders()
    } else {
      toast.error(result?.message || 'Upload failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
    // reset input value so it triggers change event again next time
    event.target.value = ''
  }
}

const viewPeriodDetail = async (row) => {
  selectedPeriodHeader.value = row
  await store.fetchPeriodViewData(row.id_pspa_head)
  showPeriodDetailModal.value = true
}

const calculatePeriodOnHand = async (idHead) => {
  saving.value = true
  try {
    const res = await store.periodViewOnHand({ id_head: idHead })
    if (res?.response === 1) {
      toast.success(res?.message || 'On-Hand Qty Calculated Successfully')
      await store.fetchPeriodViewData(idHead)
      await store.fetchPeriodHeaders()
      // update selected period header to refresh count
      const updated = store.periodHeaders.find(h => h.id_pspa_head === idHead)
      if (updated) selectedPeriodHeader.value = updated
    } else {
      toast.error(res?.message || 'Calculation Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const doPeriodAdjustment = async (idHead) => {
  saving.value = true
  try {
    const res = await store.periodViewAdjustment({ id_head: idHead })
    if (res?.response === 1) {
      toast.success(res?.message || 'Period Adjustment Executed Successfully')
      await store.fetchPeriodViewData(idHead)
      await store.fetchPeriodHeaders()
      const updated = store.periodHeaders.find(h => h.id_pspa_head === idHead)
      if (updated) selectedPeriodHeader.value = updated
    } else {
      toast.error(res?.message || 'Adjustment Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const lockPeriod = async (idHead) => {
  saving.value = true
  try {
    const res = await store.periodHeaderLock({ id_head: idHead })
    if (res?.response === 1) {
      toast.success(res?.message || 'Period Locked Successfully')
      await store.fetchPeriodHeaders()
    } else {
      toast.error(res?.message || 'Lock Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const deletePeriod = async (idHead) => {
  if (!confirm('Are you sure you want to delete this period? All details will be deleted.')) return
  saving.value = true
  try {
    const res = await store.destroyAdjustmentPeriod(idHead)
    if (res?.response === 1) {
      toast.success(res?.message || 'Period Deleted Successfully')
      await store.fetchPeriodHeaders()
    } else {
      toast.error(res?.message || 'Delete Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const submitSupplierAdj = async () => {
  if (!supplierAdjForm.idMaterial || !supplierAdjForm.idTank || supplierAdjForm.adjustType === '-' || !supplierAdjForm.idSupplier || !supplierAdjForm.qty) {
    toast.error('Please fill all required fields.')
    return
  }
  saving.value = true
  try {
    const result = await store.adjustmentSupplier({
      entry_date: supplierAdjForm.entryDate,
      id_tank: supplierAdjForm.idTank,
      id_sloc: supplierAdjForm.idSloc,
      id_material: supplierAdjForm.idMaterial,
      id_supplier: supplierAdjForm.idSupplier,
      batch_sap: supplierAdjForm.batchSap,
      qty: supplierAdjForm.qty,
      adjust_type: supplierAdjForm.adjustType
    })
    if (result?.response === 1) {
      toast.success('Supplier Adjustment Saved')
      showSupplierModal.value = false
      loadData()
    } else {
      toast.error(result?.message || 'Failed')
    }
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const viewDetail = async (row) => {
  try {
    await store.fetchDetail(row.id_adjust_head)
    detailData.value = store.detail
    showDetail.value = true
  } catch {
    toast.error('Failed to load detail')
  }
}

const approve = (row) => {
  confirmType.value = 'approve'
  confirmMsg.value = `Approve adjustment ${row.adjust_no}?`
  confirmAction.value = async () => {
    const res = await store.approveAdjustment(row.id_adjust_head, { status: 2 })
    if (res?.response === 1) { toast.success('Approved'); loadData() }
    else toast.error(res?.message || 'Failed')
  }
}

const execute = (row) => {
  confirmType.value = 'execute'
  confirmMsg.value = `Execute adjustment ${row.adjust_no}? Stock will be updated.`
  confirmAction.value = async () => {
    const res = await store.executeAdjustment(row.id_adjust_head)
    if (res?.response === 1) { toast.success('Executed'); loadData() }
    else toast.error(res?.message || 'Failed')
  }
}

const cancel = (row) => {
  confirmType.value = 'cancel'
  confirmMsg.value = `Cancel adjustment ${row.adjust_no}?`
  confirmAction.value = async () => {
    const res = await store.cancelAdjustment(row.id_adjust_head, { reason: 'Cancelled by user' })
    if (res?.response === 1) { toast.success('Cancelled'); loadData() }
    else toast.error(res?.message || 'Failed')
  }
}

const executeConfirm = async () => {
  saving.value = true
  try {
    await confirmAction.value()
    confirmMsg.value = ''
  } catch (err) {
    toast.error(err.response?.data?.message || err.message)
  } finally {
    saving.value = false
  }
}

const statusColor = (s) => {
  const map = { 1: 'warning', 2: 'success', 3: 'error', 4: 'primary' }
  return map[s] || 'medium-emphasis'
}
</script>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>
