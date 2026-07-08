<template>
  <div class="pa-6">
    <VCard class="mb-4">
      <VCardTitle class="pa-5 pb-2">
        <VRow align="center" no-gutters>
          <VCol cols="auto" class="d-flex align-center ga-2">
            <VIcon icon="ri-file-list-3-line" color="primary" size="24" />
            <span class="text-h6 font-weight-bold d-block">Summary of Daily Transaction</span>
          </VCol>
        </VRow>
      </VCardTitle>
      <VCardText>
        <VRow dense>
          <VCol cols="12" md="4">
            <label class="text-caption font-weight-bold text-medium-emphasis text-uppercase">Select Entry Date</label>
            <VTextField
              v-model="entryDate"
              type="date"
              rounded="md"
              color="primary"
              density="compact"
              variant="outlined"
              class="mt-1"
              @update:model-value="loadAll"
            />
          </VCol>
          <VCol cols="12" md="2" class="d-flex align-end">
            <VBtn color="primary" prepend-icon="ri-search-line" block @click="loadAll">Search</VBtn>
          </VCol>
        </VRow>
      </VCardText>
    </VCard>

    <VCard v-if="loading" class="d-flex flex-column align-center justify-center pa-8 mb-4">
      <VProgressCircular indeterminate color="primary" size="32" />
      <span class="mt-3 text-body-2 text-medium-emphasis">Loading report data...</span>
    </VCard>

    <template v-else>
      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="primary" variant="flat" prepend-icon="ri-oil-line">
            RM Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ rm.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', rm)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', rm)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', rm)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', rm)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', rm)">Qty In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc', rm)">SLoc<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', rm)">Qty Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', rm)">Qty Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', rm)">Supplier / Batch SAP / Qty (MT)<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="rm.length === 0">
                <tr>
                  <td colspan="10" class="text-center pa-8">
                    <VIcon icon="ri-inbox-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No RM transactions for this date.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in paginatedRm" :key="i">
                  <td class="text-center">{{ (pageRm - 1) * perPage + i + 1 }}</td>
                  <td>{{ r.entry_date }}</td>
                  <td class="font-monospace">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-monospace">{{ r.to_trace_no }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:200px">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.in_qty }}</td>
                  <td>{{ r.sloc || '-' }}</td>
                  <td class="text-right font-monospace">{{ r.out_qty }}</td>
                  <td class="text-right font-monospace" :class="qtyColor(r)">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="rm.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center ga-3">
              <span class="text-caption text-medium-emphasis">Showing {{ (pageRm - 1) * perPage + 1 }} - {{ Math.min(pageRm * perPage, rm.length) }} of {{ rm.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,15,20]" rounded="md" color="primary" density="compact" variant="outlined" hide-details style="min-width:80px;max-width:100px" />
            </div>
            <VPagination
              v-if="totalPagesRm > 1"
              v-model="pageRm"
              :length="totalPagesRm"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
        </VCardText>
      </VCard>

      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="primary" variant="flat" prepend-icon="ri-settings-line">
            WIP Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ wip.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', wip)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', wip)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', wip)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', wip)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', wip)">WIP Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'section' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('section', wip)">Section<VIcon v-if="sortKey==='section'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', wip)">WIP In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', wip)">WIP Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', wip)">Supplier / Batch SAP / Qty (MT)<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="wip.length === 0">
                <tr>
                  <td colspan="10" class="text-center pa-8">
                    <VIcon icon="ri-inbox-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No WIP transactions for this date.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in paginatedWip" :key="i">
                  <td class="text-center">{{ (pageWip - 1) * perPage + i + 1 }}</td>
                  <td>{{ r.entry_date }}</td>
                  <td class="font-monospace">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-monospace">{{ r.to_trace_no }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:200px">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.wip_out || r.out_qty }}</td>
                  <td>{{ r.section || '-' }}</td>
                  <td class="text-right font-monospace">{{ r.wip_in || r.in_qty }}</td>
                  <td class="text-right font-monospace" :class="qtyColor(r)">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="wip.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center ga-3">
              <span class="text-caption text-medium-emphasis">Showing {{ (pageWip - 1) * perPage + 1 }} - {{ Math.min(pageWip * perPage, wip.length) }} of {{ wip.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,15,20]" rounded="md" color="primary" density="compact" variant="outlined" hide-details style="min-width:80px;max-width:100px" />
            </div>
            <VPagination
              v-if="totalPagesWip > 1"
              v-model="pageWip"
              :length="totalPagesWip"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
        </VCardText>
      </VCard>

      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="primary" variant="flat" prepend-icon="ri-swap-line">
            TRANSFER Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ transfer.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', transfer)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', transfer)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', transfer)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', transfer)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', transfer)">Qty In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc', transfer)">SLOC<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', transfer)">Qty Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', transfer)">Qty Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', transfer)">Supplier<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="transfer.length === 0">
                <tr>
                  <td colspan="10" class="text-center pa-8">
                    <VIcon icon="ri-inbox-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No Transfer transactions for this date.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in paginatedTransfer" :key="i">
                  <td class="text-center">{{ (pageTransfer - 1) * perPage + i + 1 }}</td>
                  <td>{{ r.entry_date }}</td>
                  <td class="font-monospace">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-monospace">{{ r.to_trace_no }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:200px">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.in_qty }}</td>
                  <td>{{ r.sloc || '-' }}</td>
                  <td class="text-right font-monospace">{{ r.out_qty }}</td>
                  <td class="text-right font-monospace" :class="qtyColor(r)">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="transfer.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center ga-3">
              <span class="text-caption text-medium-emphasis">Showing {{ (pageTransfer - 1) * perPage + 1 }} - {{ Math.min(pageTransfer * perPage, transfer.length) }} of {{ transfer.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,15,20]" rounded="md" color="primary" density="compact" variant="outlined" hide-details style="min-width:80px;max-width:100px" />
            </div>
            <VPagination
              v-if="totalPagesTransfer > 1"
              v-model="pageTransfer"
              :length="totalPagesTransfer"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
        </VCardText>
      </VCard>

      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="info" variant="flat" prepend-icon="ri-drop-line">
            BLENDING Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ blending.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', blending)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', blending)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', blending)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', blending)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', blending)">Qty In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'sloc' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('sloc', blending)">SLOC<VIcon v-if="sortKey==='sloc'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', blending)">Qty Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', blending)">Qty Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', blending)">Supplier<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="blending.length === 0">
                <tr><td colspan="10" class="text-center pa-6 text-medium-emphasis">No blending transactions for this date</td></tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in sortedBlending" :key="r.id_trace_head || i">
                  <td class="text-center text-caption text-medium-emphasis">{{ (pageBlending - 1) * perPage + i + 1 }}</td>
                  <td class="text-center">{{ r.entry_date }}</td>
                  <td class="font-weight-medium font-mono text-caption">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-weight-medium font-mono text-caption">{{ r.to_trace_no }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:160px" :title="r.material">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.in_qty }}</td>
                  <td class="text-medium-emphasis">{{ r.sloc || '-' }}</td>
                  <td class="text-right font-monospace">{{ r.out_qty }}</td>
                  <td class="text-right font-monospace">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <div v-if="blending.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center gap-2">
              <span class="text-caption text-medium-emphasis">Showing {{ (pageBlending - 1) * perPage + 1 }} - {{ Math.min(pageBlending * perPage, blending.length) }} of {{ blending.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,20,50]" density="compact" variant="outlined" hide-details style="width:80px" />
            </div>
            <VPagination v-if="totalPagesBlending > 1" v-model="pageBlending" :length="totalPagesBlending" :total-visible="5" density="comfortable" size="small" show-first-last-page />
          </div>
        </VCardText>
      </VCard>

      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="primary" variant="flat" prepend-icon="ri-package-line">
            PACKAGING Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ pck.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', pck)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', pck)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', pck)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'batch_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('batch_no', pck)">PPH Batch<VIcon v-if="sortKey==='batch_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', pck)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', pck)">Qty In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', pck)">Qty Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', pck)">Qty Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', pck)">Supplier<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="pck.length === 0">
                <tr>
                  <td colspan="10" class="text-center pa-8">
                    <VIcon icon="ri-inbox-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No Packaging transactions for this date.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in paginatedPck" :key="i">
                  <td class="text-center">{{ (pagePck - 1) * perPage + i + 1 }}</td>
                  <td>{{ r.entry_date }}</td>
                  <td class="font-monospace">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-monospace">{{ r.to_trace_no }}</td>
                  <td class="font-monospace">{{ r.batch_no || '-' }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:200px">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.in_qty }}</td>
                  <td class="text-right font-monospace">{{ r.out_qty }}</td>
                  <td class="text-right font-monospace" :class="qtyColor(r)">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="pck.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center ga-3">
              <span class="text-caption text-medium-emphasis">Showing {{ (pagePck - 1) * perPage + 1 }} - {{ Math.min(pagePck * perPage, pck.length) }} of {{ pck.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,15,20]" rounded="md" color="primary" density="compact" variant="outlined" hide-details style="min-width:80px;max-width:100px" />
            </div>
            <VPagination
              v-if="totalPagesPck > 1"
              v-model="pagePck"
              :length="totalPagesPck"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
        </VCardText>
      </VCard>

      <VCard class="mb-4" style="overflow-x: auto;">
        <VCardTitle class="d-flex align-center justify-space-between border-b pa-3 py-2">
          <VChip color="primary" variant="flat" prepend-icon="ri-ship-line">
            SHIPMENT Transaction
          </VChip>
          <span class="text-caption text-medium-emphasis">{{ shipment.length }} records</span>
        </VCardTitle>
        <VCardText class="pa-0">
          <div class="overflow-x-auto">
            <VTable density="compact" class="text-body-2">
              <thead>
                <tr>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-center" style="width:48px">No</th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'entry_date' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('entry_date', shipment)">Entry Date<VIcon v-if="sortKey==='entry_date'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'from_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('from_trace_no', shipment)">Prev Trace<VIcon v-if="sortKey==='from_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'to_trace_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('to_trace_no', shipment)">Trace<VIcon v-if="sortKey==='to_trace_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'so_no' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('so_no', shipment)">SO No<VIcon v-if="sortKey==='so_no'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'material' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('material', shipment)">Material<VIcon v-if="sortKey==='material'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'in_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('in_qty', shipment)">Qty In (MT)<VIcon v-if="sortKey==='in_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'out_qty' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('out_qty', shipment)">Qty Out (MT)<VIcon v-if="sortKey==='out_qty'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis text-right sortable-th" :class="{ active: sortKey === 'balance_supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('balance_supplier', shipment)">Qty Supplier (MT)<VIcon v-if="sortKey==='balance_supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                  <th class="text-caption font-weight-bold text-uppercase text-medium-emphasis sortable-th" :class="{ active: sortKey === 'supplier' }" style="cursor:pointer;user-select:none;white-space:nowrap" @click="toggleSort('supplier', shipment)">Supplier<VIcon v-if="sortKey==='supplier'" :icon="sortDir==='asc'?'ri-arrow-up-s-line':'ri-arrow-down-s-line'" size="14" class="sort-icon" /><VIcon v-else icon="ri-arrow-up-down-line" size="12" class="sort-icon" /></th>
                </tr>
              </thead>
              <tbody v-if="shipment.length === 0">
                <tr>
                  <td colspan="10" class="text-center pa-8">
                    <VIcon icon="ri-inbox-line" size="40" class="text-disabled mb-2" />
                    <p class="text-body-2 text-medium-emphasis">No Shipment transactions for this date.</p>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr v-for="(r, i) in paginatedShipment" :key="i">
                  <td class="text-center">{{ (pageShipment - 1) * perPage + i + 1 }}</td>
                  <td>{{ r.entry_date }}</td>
                  <td class="font-monospace">{{ r.from_trace_no || '-' }}</td>
                  <td class="font-monospace">{{ r.to_trace_no }}</td>
                  <td class="font-monospace">{{ r.so_no || '-' }}</td>
                  <td class="font-weight-medium text-truncate" style="max-width:200px">{{ r.material }}</td>
                  <td class="text-right font-monospace">{{ r.in_qty }}</td>
                  <td class="text-right font-monospace">{{ r.out_qty }}</td>
                  <td class="text-right font-monospace" :class="qtyColor(r)">{{ r.balance_supplier }}</td>
                  <td class="text-caption text-truncate" style="max-width:200px" :title="r.supplier">{{ r.supplier || '-' }}</td>
                </tr>
              </tbody>
            </VTable>
          </div>
          <VDivider />
          <div v-if="shipment.length > 0" class="d-flex flex-wrap justify-space-between align-center px-4 py-2 gap-2">
            <div class="d-flex align-center ga-3">
              <span class="text-caption text-medium-emphasis">Showing {{ (pageShipment - 1) * perPage + 1 }} - {{ Math.min(pageShipment * perPage, shipment.length) }} of {{ shipment.length }} records</span>
              <VSelect v-model="perPage" :items="[5,10,15,20]" rounded="md" color="primary" density="compact" variant="outlined" hide-details style="min-width:80px;max-width:100px" />
            </div>
            <VPagination
              v-if="totalPagesShipment > 1"
              v-model="pageShipment"
              :length="totalPagesShipment"
              :total-visible="5"
              density="comfortable"
              size="small"
              show-first-last-page
            />
          </div>
        </VCardText>
      </VCard>
    </template>
  </div>
</template>

<style scoped>
.sort-icon { vertical-align: middle; transition: opacity 0.15s; opacity: 0.35; }
.sortable-th:hover .sort-icon { opacity: 0.7; }
.sortable-th.active .sort-icon { opacity: 1 !important; color: rgb(var(--v-theme-primary)); }
</style>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useTsReportStore } from '@/modules/ts-tsreport/stores/tsReportStore'

const store = useTsReportStore()
const { rmSection: rm, wipSection: wip, transferSection: transfer, blendingSection: blending, pckSection: pck, shipmentSection: shipment, loading } = storeToRefs(store)

const entryDate = ref(new Date().toISOString().split('T')[0])

// Sort state
const sortKey = ref(null)
const sortDir = ref(null)

function detectColumnType(rows, colKey) {
  if (!rows || rows.length === 0) return 'text'
  for (const row of rows) {
    const val = row[colKey]
    if (val !== null && val !== undefined && val !== '') {
      return !isNaN(parseFloat(val)) && isFinite(val) ? 'number' : 'text'
    }
  }
  return 'text'
}

function toggleSort(key, rows) {
  if (sortKey.value === key) {
    if (sortDir.value === 'asc') {
      sortDir.value = 'desc'
    } else if (sortDir.value === 'desc') {
      sortKey.value = null
      sortDir.value = null
    }
  } else {
    sortKey.value = key
    sortDir.value = detectColumnType(rows, key) === 'text' ? 'asc' : 'desc'
  }
}

function sortData(rows) {
  if (!sortKey.value || !sortDir.value) return rows
  const key = sortKey.value
  const dir = sortDir.value
  const list = [...rows]
  const type = detectColumnType(rows, key)
  return list.sort((a, b) => {
    const va = a[key]
    const vb = b[key]
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    if (type === 'number') return dir === 'asc' ? va - vb : vb - va
    return dir === 'asc' ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va))
  })
}

// Pagination state
const perPage = ref(10)
const pageRm = ref(1)
const pageWip = ref(1)
const pageTransfer = ref(1)
const pageBlending = ref(1)
const pagePck = ref(1)
const pageShipment = ref(1)

// Sorted & paginated computed arrays
const sortedRm = computed(() => sortData(rm.value))
const paginatedRm = computed(() => sortedRm.value.slice((pageRm.value - 1) * perPage.value, pageRm.value * perPage.value))
const sortedWip = computed(() => sortData(wip.value))
const paginatedWip = computed(() => sortedWip.value.slice((pageWip.value - 1) * perPage.value, pageWip.value * perPage.value))
const sortedTransfer = computed(() => sortData(transfer.value))
const paginatedTransfer = computed(() => sortedTransfer.value.slice((pageTransfer.value - 1) * perPage.value, pageTransfer.value * perPage.value))
const sortedPck = computed(() => sortData(pck.value))
const paginatedPck = computed(() => sortedPck.value.slice((pagePck.value - 1) * perPage.value, pagePck.value * perPage.value))
const sortedShipment = computed(() => sortData(shipment.value))
const paginatedShipment = computed(() => sortedShipment.value.slice((pageShipment.value - 1) * perPage.value, pageShipment.value * perPage.value))

const sortedBlending = computed(() => sortData(blending.value))
const paginatedBlending = computed(() => sortedBlending.value.slice((pageBlending.value - 1) * perPage.value, pageBlending.value * perPage.value))

// Total pages computed values
const totalPagesRm = computed(() => Math.ceil(rm.value.length / perPage.value))
const totalPagesWip = computed(() => Math.ceil(wip.value.length / perPage.value))
const totalPagesTransfer = computed(() => Math.ceil(transfer.value.length / perPage.value))
const totalPagesBlending = computed(() => Math.ceil(blending.value.length / perPage.value))
const totalPagesPck = computed(() => Math.ceil(pck.value.length / perPage.value))
const totalPagesShipment = computed(() => Math.ceil(shipment.value.length / perPage.value))

const loadAll = async () => {
  pageRm.value = 1
  pageWip.value = 1
  pageTransfer.value = 1
  pageBlending.value = 1
  pagePck.value = 1
  pageShipment.value = 1
  const plantId = 0
  await store.fetchAllSections({
    entry_date: entryDate.value,
    id_plant: plantId
  })
}

onMounted(() => {
  loadAll()
})

const qtyColor = (row) => {
  const inQty = parseFloat(row.in_qty || row.wip_in || 0)
  const outQty = parseFloat(row.out_qty || row.wip_out || 0)
  const sup = parseFloat(row.balance_supplier || 0)
  const cmp = outQty > 0 ? outQty : inQty
  return cmp > 0 && Math.abs(cmp - sup) < 0.001
    ? 'text-success'
    : cmp > 0
      ? 'text-error'
      : ''
}

watch(perPage, () => {
  pageRm.value = 1
  pageWip.value = 1
  pageTransfer.value = 1
  pagePck.value = 1
  pageShipment.value = 1
})
</script>
