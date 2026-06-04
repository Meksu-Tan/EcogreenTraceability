<template>
  <div class="section min-h-screen bg-[#fafdfb]">
    <!-- Page Header -->
    <div class="section-header bg-white shadow-sm rounded-lg mb-6 p-6 mx-6 mt-6 border-none">
      <h1 class="text-2xl font-bold text-slate-800">Adjustment - {{ adjMode === 'wip' ? 'WIP' : 'WAREHOUSE' }}</h1>
    </div>

    <div class="section-body mx-6">
      <!-- Mode Toggle -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-6 border-none">
        <div class="flex flex-wrap gap-2 px-2">
          <button @click="switchMode('wip')"
            :class="adjMode === 'wip' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800'"
            class="px-4 py-2 rounded font-semibold flex items-center gap-2 shadow-sm transition-all duration-200 hover:bg-green-600 hover:text-white cursor-pointer">
            <Icon icon="fa6-solid:bars" class="w-4 h-4" /> WIP Adjustment
          </button>
          <button @click="switchMode('wh')"
            :class="adjMode === 'wh' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800'"
            class="px-4 py-2 rounded font-semibold flex items-center gap-2 shadow-sm transition-all duration-200 hover:bg-green-600 hover:text-white cursor-pointer">
            <Icon icon="fa6-solid:bars" class="w-4 h-4" /> WAREHOUSE Adjustment
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="bg-white rounded-lg shadow-sm p-6 border-none">
        <div class="flex flex-wrap gap-3 mb-6 pl-2 pb-3 border-b">
          <!-- WIP Buttons -->
          <template v-if="adjMode === 'wip'">
            <button @click="openLastRecord" class="btn-adj"><Icon icon="fa6-solid:pen-to-square" class="w-4 h-4" /> Adjustment Last Record</button>
            <button @click="openInit" class="btn-adj"><Icon icon="fa6-solid:layer-group" class="w-4 h-4" /> Stock Initialization</button>
            <button @click="openPeriod" class="btn-adj"><Icon icon="fa6-solid:calendar-days" class="w-4 h-4" /> Period Adjustment</button>
            <button @click="openSupplier" class="btn-adj"><Icon icon="fa6-solid:user-tie" class="w-4 h-4" /> Supplier Adjustment</button>
          </template>
          <!-- WAREHOUSE Buttons -->
          <template v-else>
            <button @click="openLastRecord" class="btn-adj"><Icon icon="fa6-solid:pen-to-square" class="w-4 h-4" /> New Adjustment</button>
            <button @click="openInit" class="btn-adj"><Icon icon="fa6-solid:layer-group" class="w-4 h-4" /> Stock Initialization</button>
          </template>
        </div>

        <!-- Data Table -->
        <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
        <div v-else>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
              <thead class="text-xs uppercase bg-gray-50 border-b-2 border-gray-200 text-gray-600">
                <tr>
                  <th class="px-3 py-3 text-center w-10">No</th>
                  <th class="px-3 py-3 text-center">Action</th>
                  <th class="px-3 py-3">Adjustment No</th>
                  <th class="px-3 py-3">Material Document</th>
                  <th class="px-3 py-3">Entry Date</th>
                  <th class="px-3 py-3">Material</th>
                  <th class="px-3 py-3">SLoc</th>
                  <th class="px-3 py-3">Adjusted Batch</th>
                  <th class="px-3 py-3 text-right">Adjustment (MT)</th>
                  <th class="px-3 py-3">Supplier / Batch SAP / Qty</th>
                  <th class="px-3 py-3">Created At</th>
                  <th class="px-3 py-3">Created By</th>
                </tr>
              </thead>
              <tbody v-if="data.length > 0">
                <tr v-for="(row, i) in data" :key="row.id_adjust_head"
                  class="border-b odd:bg-white even:bg-gray-50 hover:bg-green-50 transition-colors">
                  <td class="px-3 py-2 text-center text-gray-500 font-mono text-xs">{{ i + 1 }}</td>
                  <td class="px-3 py-2 text-center">
                    <div class="flex gap-1 justify-center">
                      <button @click="viewDetail(row)" class="px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700 cursor-pointer" title="View">V</button>
                      <button v-if="row.status === 1" @click="approve(row)" class="px-2 py-1 text-xs rounded bg-blue-500 text-white hover:bg-blue-600" title="Approve">A</button>
                      <button v-if="row.status === 2" @click="execute(row)" class="px-2 py-1 text-xs rounded bg-purple-500 text-white hover:bg-purple-600" title="Execute">E</button>
                      <button v-if="row.status === 1 || row.status === 2" @click="cancel(row)" class="px-2 py-1 text-xs rounded bg-red-500 text-white hover:bg-red-600" title="Cancel">X</button>
                    </div>
                  </td>
                  <td class="px-3 py-2 font-mono text-xs">{{ row.adjust_no }}</td>
                  <td class="px-3 py-2 font-mono text-xs">{{ row.material_document || row.material_doc || '-' }}</td>
                  <td class="px-3 py-2 font-mono text-xs">{{ row.entry_date }}</td>
                  <td class="px-3 py-2 text-xs font-medium max-w-[160px] truncate" :title="row.material">{{ row.material }}</td>
                  <td class="px-3 py-2 font-mono text-xs">{{ row.sloc || row.tank || '-' }}</td>
                  <td class="px-3 py-2 font-mono text-xs">{{ row.trace_no || '-' }}</td>
                  <td class="px-3 py-2 text-right font-mono text-xs">{{ row.adjustment || '-' }}</td>
                  <td class="px-3 py-2 text-xs max-w-[200px] truncate" :title="row.supplier">{{ row.supplier || '-' }}</td>
                  <td class="px-3 py-2 text-xs text-gray-500">{{ row.created_at || '-' }}</td>
                  <td class="px-3 py-2 text-xs">{{ row.created_by || '-' }}</td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr>
                  <td colspan="12" class="p-8 text-center text-gray-400">
                    <Icon icon="ri:tune-line" class="w-10 h-10 mx-auto mb-3 text-gray-300" />
                    <p>No adjustment records found.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="px-3 py-2 border-t bg-gray-50 text-xs text-gray-500">{{ data.length }} records</div>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: ADJUSTMENT LAST RECORD / NEW ADJUSTMENT ===== -->
    <div v-if="showLastRecordModal" class="modal-backdrop" @click.self="showLastRecordModal = false">
      <div class="modal-box max-w-lg">
        <div class="modal-header">
          <h2>{{ adjMode === 'wip' ? 'ADJUSTMENT DATA (ONLY FOR STOCK QTY NOT EQUAL 0)' : 'New Adjustment' }}</h2>
          <button @click="showLastRecordModal = false" class="btn-close-modal"><Icon icon="ri:close-line" /></button>
        </div>
        <div class="modal-body-scroll">
          <div class="form-grid-2">
            <!-- Entry Mode -->
            <div class="form-group">
              <label class="form-label">Entry Mode</label>
              <input :value="lastRecordForm.mode" type="text" readonly class="form-control-readonly" />
            </div>
            <!-- Entry Date -->
            <div class="form-group">
              <label class="form-label">Adjustment Entry Date</label>
              <input v-model="lastRecordForm.entryDate" type="date" class="form-control" />
            </div>
          </div>
          <!-- Material -->
          <div class="form-group">
            <label class="form-label">Material</label>
            <select v-model="lastRecordForm.idMaterial" class="form-control">
              <option value="">- Select Material -</option>
              <option v-for="m in activeMaterials" :key="m.id_material" :value="m.id_material">{{ m.material }}</option>
            </select>
          </div>
          <div class="form-grid-2">
            <!-- Sloc -->
            <div class="form-group">
              <label class="form-label">Sloc</label>
              <select v-model="lastRecordForm.idTank" class="form-control">
                <option value="">- No Sloc -</option>
                <option v-for="t in activeTanks" :key="t.id_tank" :value="t.id_tank">{{ t.tank }}</option>
              </select>
            </div>
            <!-- New Balance Qty -->
            <div class="form-group">
              <label class="form-label">New Balance Qty (MT)</label>
              <input v-model="lastRecordForm.qty" type="number" step="0.001" min="0" class="form-control" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showLastRecordModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitLastRecord" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save' }}</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: STOCK INITIALIZATION ===== -->
    <div v-if="showInitModal" class="modal-backdrop" @click.self="showInitModal = false">
      <div class="modal-box max-w-4xl">
        <div class="modal-header">
          <h2>Stock Initialization Entry</h2>
          <button @click="showInitModal = false" class="btn-close-modal"><Icon icon="ri:close-line" /></button>
        </div>
        <div class="modal-body-scroll">
          <!-- Row 1: Mode, Date, Material Doc -->
          <div class="form-grid-3">
            <div class="form-group">
              <label class="form-label">Entry Mode</label>
              <input :value="initForm.mode" type="text" readonly class="form-control-readonly" />
            </div>
            <div class="form-group">
              <label class="form-label">Date (Auto Detect)</label>
              <input v-model="initForm.entry_date" type="date" class="form-control" @change="onInitDateChange" />
            </div>
            <div class="form-group" v-if="adjMode === 'wip'">
              <label class="form-label">Material Document (SAP)</label>
              <input v-model="initForm.material_doc" type="text" class="form-control" />
            </div>
            <div class="form-group" v-if="adjMode === 'wh'">
              <label class="form-label">PO No</label>
              <input v-model="initForm.po_no" type="text" class="form-control" />
            </div>
          </div>
          <!-- Row 2: Sloc, Batch No (WH only) -->
          <div class="form-grid-3">
            <div class="form-group">
              <label class="form-label">Sloc</label>
              <select v-model="initForm.tank" class="form-control" @change="onInitTankChange">
                <option value="">- No Sloc -</option>
                <option v-for="t in activeTanks" :key="t.id_tank" :value="t.id_tank">{{ t.tank }}</option>
              </select>
            </div>
            <div class="form-group" v-if="adjMode === 'wh'">
              <label class="form-label">Batch No</label>
              <input v-model="initForm.batch_no" type="text" class="form-control" />
            </div>
          </div>
          <!-- Row 3: Material, Specific Sloc -->
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Material (Do not change after input supplier!)</label>
              <select v-model="initForm.id_material" class="form-control">
                <option value="">- Select Material -</option>
                <option v-for="m in activeMaterials" :key="m.id_material" :value="m.id_material">{{ m.material }}</option>
              </select>
            </div>
            <div class="form-group" v-if="adjMode === 'wip' && activeSpecificTanks.length > 0">
              <label class="form-label">Specific Sloc</label>
              <div class="border rounded p-2 max-h-28 overflow-y-auto bg-gray-50">
                <label v-for="st in activeSpecificTanks" :key="st.id_tank_tail" class="flex items-center gap-2 text-sm py-0.5 cursor-pointer">
                  <input type="checkbox" :value="st.id_tank_tail" v-model="initForm.tankNo" class="rounded" />
                  {{ st.tankNo }}
                </label>
              </div>
            </div>
          </div>
          <!-- Row 4: Add Supplier & Qty button + Total Qty -->
          <div class="flex items-end justify-between mb-3">
            <div class="flex gap-2">
              <button @click="openInitSupplierForm" class="px-4 py-2 rounded font-semibold bg-slate-700 text-white hover:bg-slate-800 text-sm flex items-center gap-2">
                <Icon icon="fa6-solid:plus" class="w-3 h-3" /> Add Supplier & Qty
              </button>
              <button @click="submitInit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save Entry' }}</button>
            </div>
            <div class="text-right">
              <label class="text-xs text-gray-500 font-semibold">Total Qty (MT)</label>
              <input :value="initForm.qty" type="text" readonly class="form-control-readonly text-right font-bold w-36 block" />
            </div>
          </div>
          <!-- Add Supplier inline form (shown when showInitSupplierForm) -->
          <div v-if="showInitSupplierForm" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-3">
            <h4 class="text-sm font-bold text-blue-700 mb-3">Add Supplier</h4>
            <div class="grid grid-cols-3 gap-3 items-end">
              <div>
                <label class="form-label">Supplier</label>
                <select v-model="initSupplierForm.idSupplier" class="form-control">
                  <option value="">Select Supplier</option>
                  <option v-for="s in searchSuppliersList" :key="s.id_supplier" :value="s.id_supplier">{{ s.supplier }}</option>
                </select>
              </div>
              <div>
                <label class="form-label">Batch SAP</label>
                <input v-model="initSupplierForm.batchSap" type="text" class="form-control" placeholder="Auto or enter" />
              </div>
              <div>
                <label class="form-label">Qty (MT)</label>
                <input v-model="initSupplierForm.qty" type="number" step="0.001" class="form-control" />
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-3">
              <button @click="showInitSupplierForm = false" class="btn-secondary text-xs px-3 py-1.5">Cancel</button>
              <button @click="addSupplierToInit" :disabled="saving" class="btn-primary text-xs px-3 py-1.5">Add</button>
            </div>
          </div>
          <!-- Supplier List Table -->
          <div class="overflow-x-auto border rounded">
            <table class="w-full text-xs">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 text-center">No</th>
                  <th class="p-2 text-center">Action</th>
                  <th class="p-2 text-left">Material</th>
                  <th class="p-2 text-left">Supplier</th>
                  <th class="p-2 text-center">Batch SAP</th>
                  <th class="p-2 text-right">Qty (MT)</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(item, i) in initSupplierList" :key="item.idTail" class="border-b hover:bg-gray-50">
                  <td class="p-2 text-center text-gray-500">{{ i + 1 }}</td>
                  <td class="p-2 text-center">
                    <button @click="removeSupplierFromInit(item.idTail)" class="px-2 py-0.5 rounded bg-red-500 text-white hover:bg-red-600 text-xs">X</button>
                  </td>
                  <td class="p-2">{{ item.material || '-' }}</td>
                  <td class="p-2">{{ item.supplier || item.idSupplier }}</td>
                  <td class="p-2 text-center font-mono">{{ item.batch_sap || '-' }}</td>
                  <td class="p-2 text-right font-mono">{{ item.qty }}</td>
                </tr>
                <tr v-if="initSupplierList.length === 0">
                  <td colspan="6" class="p-4 text-center text-gray-400">No supplier added yet.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showInitModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: PERIOD ADJUSTMENT ===== -->
    <div v-if="showPeriodModal" class="modal-backdrop" @click.self="showPeriodModal = false">
      <div class="modal-box max-w-5xl">
        <div class="modal-header">
          <h2>Stock Period Adjustment Entry</h2>
          <button @click="showPeriodModal = false" class="btn-close-modal"><Icon icon="ri:close-line" /></button>
        </div>
        <div class="modal-body-scroll">
          <!-- Create New Period button -->
          <div class="mb-4">
            <button @click="openNewPeriodForm" class="btn-primary flex items-center gap-2">
              <Icon icon="fa6-solid:plus" class="w-3 h-3" /> Create New Period
            </button>
          </div>
          <!-- New Period Inline Form -->
          <div v-if="showNewPeriodForm" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
            <h4 class="text-sm font-bold text-blue-700 mb-3">New Period Entry</h4>
            <div class="grid grid-cols-3 gap-3">
              <div class="form-group">
                <label class="form-label">Entry Mode</label>
                <input :value="periodNewForm.mode" type="text" readonly class="form-control-readonly" />
              </div>
              <div class="form-group">
                <label class="form-label">Period (Month & Year)</label>
                <input v-model="periodNewForm.period" type="month" class="form-control" />
              </div>
              <div class="form-group">
                <label class="form-label">Batch SAP</label>
                <input v-model="periodNewForm.batch" type="text" class="form-control" />
              </div>
            </div>
            <div class="flex justify-end gap-2 mt-2">
              <button @click="showNewPeriodForm = false" class="btn-secondary text-xs px-3 py-1.5">Cancel</button>
              <button @click="submitNewPeriod" :disabled="saving" class="btn-primary text-xs px-3 py-1.5">Save Period</button>
            </div>
          </div>

          <!-- Period History Table -->
          <h3 class="text-sm font-bold text-slate-700 mb-2">Period History</h3>
          <div class="overflow-x-auto border rounded-lg">
            <table class="w-full text-sm">
              <thead class="text-xs uppercase bg-gray-100 border-b text-gray-600">
                <tr>
                  <th class="px-3 py-2 text-center w-10">No</th>
                  <th class="px-3 py-2 text-center w-28">Action</th>
                  <th class="px-3 py-2">Period</th>
                  <th class="px-3 py-2 text-center">Status</th>
                  <th class="px-3 py-2 text-right">Rows</th>
                  <th class="px-3 py-2 text-right">Total Physical</th>
                  <th class="px-3 py-2 text-right">Total Book</th>
                  <th class="px-3 py-2">Created By</th>
                  <th class="px-3 py-2">Created At</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in periodHeaders" :key="row.id_pspa_head" class="border-b hover:bg-gray-50">
                  <td class="px-3 py-2 text-center text-gray-500 text-xs">{{ i + 1 }}</td>
                  <td class="px-3 py-2 text-center">
                    <div class="flex gap-1 justify-center">
                      <button @click="toast.info('View period detail')" class="px-2 py-0.5 text-xs rounded bg-green-600 text-white hover:bg-green-700 cursor-pointer" title="View">V</button>
                      <button v-if="row.status !== 3" @click="toast.info('Lock Period')" class="px-2 py-0.5 text-xs rounded bg-yellow-500 text-white hover:bg-yellow-600" title="Lock">L</button>
                      <button v-if="row.status !== 3" @click="toast.info('Delete Period')" class="px-2 py-0.5 text-xs rounded bg-red-500 text-white hover:bg-red-600" title="Delete">X</button>
                    </div>
                  </td>
                  <td class="px-3 py-2 font-medium">{{ row.period }}</td>
                  <td class="px-3 py-2 text-center">
                    <span class="inline-flex px-2 py-0.5 text-[10px] rounded-full font-semibold"
                      :class="row.status === 3 ? 'bg-green-100 text-green-700' : row.status === 2 ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'">
                      {{ row.status_label || row.status }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-right font-mono text-xs">{{ row.count_rows || 0 }}</td>
                  <td class="px-3 py-2 text-right font-mono text-xs">{{ row.total_physical || '0.000' }}</td>
                  <td class="px-3 py-2 text-right font-mono text-xs">{{ row.total_book || '0.000' }}</td>
                  <td class="px-3 py-2 text-xs">{{ row.created_by }}</td>
                  <td class="px-3 py-2 text-xs text-gray-500">{{ row.created_at }}</td>
                </tr>
                <tr v-if="periodHeaders.length === 0">
                  <td colspan="9" class="p-6 text-center text-gray-400">No period history found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showPeriodModal = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: SUPPLIER ADJUSTMENT ===== -->
    <div v-if="showSupplierModal" class="modal-backdrop" @click.self="showSupplierModal = false">
      <div class="modal-box max-w-2xl">
        <div class="modal-header">
          <h2>Supplier Adjustment Entry</h2>
          <button @click="showSupplierModal = false" class="btn-close-modal"><Icon icon="ri:close-line" /></button>
        </div>
        <div class="modal-body-scroll">
          <!-- Row 1: Mode, Date -->
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Entry Mode</label>
              <input :value="supplierAdjForm.mode" type="text" readonly class="form-control-readonly" />
            </div>
            <div class="form-group">
              <label class="form-label">Date (Auto Detect)</label>
              <input v-model="supplierAdjForm.entryDate" type="date" class="form-control" />
            </div>
          </div>
          <!-- Material (full width) -->
          <div class="form-group">
            <label class="form-label">Material</label>
            <select v-model="supplierAdjForm.idMaterial" class="form-control">
              <option value="">- Select Material -</option>
              <option v-for="m in activeMaterials" :key="m.id_material" :value="m.id_material">{{ m.material }}</option>
            </select>
          </div>
          <!-- Sloc, Adjustment Type -->
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Sloc</label>
              <select v-model="supplierAdjForm.idTank" class="form-control">
                <option value="">- No Sloc -</option>
                <option v-for="t in activeTanks" :key="t.id_tank" :value="t.id_tank">{{ t.tank }}</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Adjustment Type</label>
              <select v-model="supplierAdjForm.adjustType" class="form-control">
                <option value="-">- Select Adjust Type -</option>
                <option value="in">- Adjust IN -</option>
                <option value="out">- Adjust OUT -</option>
              </select>
            </div>
          </div>
          <!-- Supplier, Batch SAP -->
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Supplier</label>
              <select v-model="supplierAdjForm.idSupplier" class="form-control">
                <option value="">- Select Supplier -</option>
                <option v-for="s in searchSuppliersList" :key="s.id_supplier" :value="s.id_supplier">{{ s.supplier }}</option>
              </select>
              <span class="text-xs text-gray-500 mt-1 block" v-if="supplierAdjForm.idSupplier">Stock: N/A</span>
            </div>
            <div class="form-group">
              <label class="form-label">Batch SAP</label>
              <select v-model="supplierAdjForm.batchSap" class="form-control">
                <option value="">- Select Batch SAP -</option>
              </select>
            </div>
          </div>
          <!-- Adjustment Qty -->
          <div class="form-group w-1/2">
            <label class="form-label">Adjustment Qty (MT)</label>
            <input v-model="supplierAdjForm.qty" type="number" step="0.1" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showSupplierModal = false" class="btn-secondary">Cancel</button>
          <button @click="submitSupplierAdj" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save Entry' }}</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL: DETAIL VIEW ===== -->
    <div v-if="showDetail && detailData" class="modal-backdrop" @click.self="showDetail = false">
      <div class="modal-box max-w-2xl">
        <div class="modal-header">
          <h2>Adjustment Detail - {{ detailData.header?.adjust_no }}</h2>
          <button @click="showDetail = false" class="btn-close-modal"><Icon icon="ri:close-line" /></button>
        </div>
        <div class="p-4">
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div><span class="text-xs text-gray-500">Material</span><p class="font-medium text-sm">{{ detailData.header?.material || '-' }}</p></div>
            <div><span class="text-xs text-gray-500">Entry Date</span><p class="font-medium text-sm">{{ detailData.header?.entry_date }}</p></div>
            <div><span class="text-xs text-gray-500">Before</span><p class="font-mono text-sm">{{ detailData.header?.before_adjust }} MT</p></div>
            <div><span class="text-xs text-gray-500">After</span><p class="font-mono text-sm">{{ detailData.header?.after_adjust }} MT</p></div>
            <div><span class="text-xs text-gray-500">Status</span><p><span class="inline-flex px-2 py-1 text-xs rounded-full font-medium" :class="statusClass(detailData.header?.status)">{{ detailData.header?.status_label || detailData.header?.status }}</span></p></div>
          </div>
          <div v-if="detailData.details?.length" class="border-t pt-3">
            <h3 class="text-sm font-bold text-gray-700 mb-2">Supplier Details</h3>
            <div class="space-y-2">
              <div v-for="d in detailData.details" :key="d.id_adjust_detail" class="bg-gray-50 rounded p-3 text-xs">
                <p class="font-medium">{{ d.supplier || 'Unknown' }}</p>
                <p class="text-gray-500">Batch: {{ d.batch_sap || '-' }} | Before: {{ d.before_adjust }} → After: {{ d.after_adjust }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showDetail = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- ===== CONFIRM DIALOG ===== -->
    <div v-if="confirmMsg" class="modal-backdrop" @click.self="confirmMsg = ''">
      <div class="modal-box max-w-sm text-center">
        <div class="p-6">
          <Icon :icon="confirmIcon" class="w-12 h-12 mx-auto mb-4 text-amber-500" />
          <p class="text-slate-800 font-medium mb-6">{{ confirmMsg }}</p>
          <div class="flex justify-center gap-3">
            <button @click="confirmMsg = ''" class="btn-secondary">Cancel</button>
            <button @click="executeConfirm" :disabled="saving" class="btn-primary">{{ saving ? 'Processing...' : 'Confirm' }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useAdjustmentStore } from '../stores/adjustmentStore'
import { useToastStore } from '@/stores/toast'
import adjustmentApi from '../api'

const store = useAdjustmentStore()
const toast = useToastStore()

const { data, loading, activeMaterials, activeTanks, activeSpecificTanks } = storeToRefs(store)
const { periodHeaders } = storeToRefs(store)

// ——— Local UI State ———
const adjMode = ref('wip')
const saving = ref(false)
const showDetail = ref(false)
const detailData = ref(null)
const confirmMsg = ref('')
const confirmType = ref('')
const confirmAction = ref(null)

// Modal visibility
const showLastRecordModal = ref(false)
const showInitModal = ref(false)
const showInitSupplierForm = ref(false)
const showPeriodModal = ref(false)
const showNewPeriodForm = ref(false)
const showSupplierModal = ref(false)

// Supplier list for dropdowns
const searchSuppliersList = ref([])

// ——— Form State ———
const lastRecordForm = reactive({
  mode: 'ADD',
  entryDate: new Date().toISOString().split('T')[0],
  idMaterial: '',
  idTank: '',
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
  adjustType: '-',
  idSupplier: '',
  batchSap: '',
  qty: 0
})

// ——— Computed ———
const confirmIcon = computed(() => {
  if (confirmType.value === 'approve') return 'ri:checkbox-circle-line'
  if (confirmType.value === 'cancel') return 'ri:close-circle-line'
  return 'ri:play-circle-line'
})

// ——— Lifecycle ———
onMounted(() => {
  loadData()
  loadFormOptions()
})

watch(adjMode, () => {
  loadData()
  loadFormOptions()
})

// ——— Data Loading ———
const switchMode = (mode) => {
  adjMode.value = mode
}

const loadData = async () => {
  await store.fetchList({ adj_type: adjMode.value === 'wip' ? 'wip' : 'wh' })
}

const loadFormOptions = async () => {
  try {
    const resSups = await adjustmentApi.searchSuppliers({ supplier: '' })
    searchSuppliersList.value = resSups.data?.data || []
  } catch { searchSuppliersList.value = [] }
  store.fetchActiveMaterials()
  store.fetchActiveTanks()
}

// ——— Modal Open Functions ———
const openLastRecord = () => {
  Object.assign(lastRecordForm, {
    mode: 'ADD',
    entryDate: new Date().toISOString().split('T')[0],
    idMaterial: '',
    idTank: '',
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
  showNewPeriodForm.value = true
}

const openSupplier = () => {
  Object.assign(supplierAdjForm, {
    mode: 'ADD',
    entryDate: new Date().toISOString().split('T')[0],
    idMaterial: '',
    idTank: '',
    adjustType: '-',
    idSupplier: '',
    batchSap: '',
    qty: 0
  })
  showSupplierModal.value = true
}

const openInitSupplierForm = () => {
  initSupplierForm.idSupplier = ''
  initSupplierForm.batchSap = ''
  initSupplierForm.qty = 0
  showInitSupplierForm.value = true
}

// ——— Submit: Last Record ———
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

// ——— Submit: Init ———
const onInitDateChange = async () => {
  await store.fetchEntryNo({ entry_date: initForm.entry_date })
  if (store.entryNo) initForm.entry_no = store.entryNo
}

const onInitTankChange = async () => {
  if (!initForm.tank) { activeSpecificTanks.value = []; return }
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
      // Update total qty
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

// ——— Submit: Period ———
const submitNewPeriod = async () => {
  if (!periodNewForm.period) {
    toast.error('Period is required.')
    return
  }
  saving.value = true
  try {
    toast.info('Period adjustment backend API integration needed.')
    showNewPeriodForm.value = false
    await store.fetchPeriodHeaders()
  } finally {
    saving.value = false
  }
}

// ——— Submit: Supplier Adj ———
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

// ——— Actions ———
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

const statusClass = (s) => {
  const map = { 1: 'bg-yellow-100 text-yellow-700', 2: 'bg-green-100 text-green-700', 3: 'bg-red-100 text-red-700', 4: 'bg-blue-100 text-blue-700' }
  return map[s] || 'bg-gray-100 text-gray-500'
}
</script>

<style scoped>
@reference "tailwindcss";
/* ===== Utility Classes ===== */
.btn-adj {
  @apply px-4 py-2 rounded font-semibold flex items-center gap-2 shadow-sm bg-green-600 text-white hover:bg-green-700 transition-colors duration-200 text-sm cursor-pointer;
}
.btn-primary {
  @apply px-4 py-2 rounded font-semibold bg-green-600 text-white hover:bg-green-700 transition-colors duration-200 disabled:opacity-50 text-sm cursor-pointer;
}
.btn-secondary {
  @apply px-4 py-2 rounded font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors duration-200 text-sm cursor-pointer;
}

/* ===== Modal ===== */
.modal-backdrop {
  @apply fixed inset-0 z-50 flex items-center justify-center;
  background: rgba(0, 0, 0, 0.5);
}
.modal-box {
  @apply relative bg-white rounded-lg shadow-xl w-full mx-4 flex flex-col;
  max-height: 90vh;
}
.modal-header {
  @apply flex items-center justify-between p-4 border-b;
}
.modal-header h2 {
  @apply text-base font-bold text-slate-800;
}
.modal-body-scroll {
  @apply flex-1 overflow-y-auto p-4;
}
.modal-footer {
  @apply p-3 border-t flex justify-end gap-2;
}
.btn-close-modal {
  @apply w-8 h-8 flex items-center justify-center rounded bg-green-600 text-white hover:bg-green-700 transition-colors cursor-pointer;
}

/* ===== Forms ===== */
.form-group {
  @apply mb-3;
}
.form-label {
  @apply block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide;
}
.form-control {
  @apply w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-green-600 focus:border-green-600 transition-colors;
}
.form-control-readonly {
  @apply w-full px-3 py-2 border border-gray-200 rounded text-sm bg-gray-100 text-gray-600 cursor-not-allowed;
}
.form-grid-2 {
  @apply grid grid-cols-1 md:grid-cols-2 gap-3 mb-1;
}
.form-grid-3 {
  @apply grid grid-cols-1 md:grid-cols-3 gap-3 mb-1;
}
</style>
