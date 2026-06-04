<template>
  <div class="section min-h-screen bg-[#fafdfb]">
    <div class="section-header bg-white shadow-sm rounded-lg mb-6 p-6 mx-6 mt-6 border-none">
      <h1 class="text-2xl font-bold text-slate-800">Reset Quantifier</h1>
    </div>

    <div class="section-body mx-6">
      <div class="grid grid-cols-1">
        <div class="bg-white rounded-lg shadow-sm p-6 border-none">
          <div class="flex flex-wrap gap-3 mb-6 pl-4 pb-2">
            <button @click="openCreateModal" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700 flex items-center gap-2 shadow-sm transition-all duration-300 cursor-pointer">
              <Icon icon="ri:add-line" class="w-4 h-4" /> New Reset Quantifier
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Flowmeter</label><select v-model="filters.flowmeter" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">All</option><option v-for="fm in flowmeters" :key="fm.flowmeter" :value="fm.flowmeter">{{ fm.flowmeter }}</option></select></div>
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Date From</label><input v-model="filters.date_from" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Date To</label><input v-model="filters.date_to" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Status</label><select v-model="filters.status" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">All</option><option value="1">Active</option><option value="0">Inactive</option></select></div>
          </div>
          <div class="flex gap-2 mb-6"><button @click="loadData" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700 shadow-sm transition-all duration-300 cursor-pointer"><Icon icon="ri:search-line" class="w-4 h-4 inline" /> Search</button><button @click="resetFilters" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700 shadow-sm transition-all duration-300 cursor-pointer"><Icon icon="ri:refresh-line" class="w-4 h-4 text-white inline" /> Reset</button></div>

          <div v-if="loading" class="p-8 text-center text-gray-500">Loading...</div>
          <div class="overflow-x-auto"><table class="w-full text-sm text-left text-gray-700">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b-2 border-gray-200"><tr>
          <th class="px-4 py-3 text-center w-12 border-r border-gray-200">No</th><th class="px-4 py-3 text-center w-24 border-r border-gray-200">Action</th><th class="px-4 py-3 text-left border-r border-gray-200">Flowmeter</th>
          <th class="px-4 py-3 text-right border-r border-gray-200">Reset Value</th><th class="px-4 py-3 text-left border-r border-gray-200">Remark</th><th class="px-4 py-3 text-center border-r border-gray-200">Status</th><th class="px-4 py-3 text-left border-r border-gray-200">Created At</th><th class="px-4 py-3 text-left">Created By</th>
        </tr></thead>
        <tbody>
          <tr v-if="data.length === 0"><td colspan="8" class="px-4 py-8 text-center text-gray-400"><Icon icon="ri:calculator-line" class="w-12 h-12 mx-auto mb-2 text-gray-300" /><p>No quantifier records.</p></td></tr>
          <tr v-for="(row, i) in data" :key="row.id_reset" class="odd:bg-white even:bg-[#f9f9f9] border-b hover:bg-gray-100 transition-colors">
            <td class="px-4 py-2 text-center text-gray-500 font-mono">{{ i+1 }}</td>
            <td class="px-4 py-2 text-center"><div class="flex gap-1 justify-center">
              <button @click="openEditModal(row)" class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200" title="Update"><Icon icon="ri:pencil-line" class="w-3.5 h-3.5" /></button>
              <button v-if="row.status===1" @click="toggleStatus(row, 'deactivate')" class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200" title="De-activate"><Icon icon="ri:delete-bin-line" class="w-3.5 h-3.5" /></button>
              <button v-else @click="toggleStatus(row, 'activate')" class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200" title="Activate"><Icon icon="ri:redo-line" class="w-3.5 h-3.5" /></button>
            </div></td>
            <td class="px-4 py-2 font-medium">{{ row.flowmeter }}</td>
            <td class="px-4 py-2 text-right font-mono">{{ formatQty(row.value) }}</td>
            <td class="px-4 py-2 max-w-xs truncate">{{ row.remark || '-' }}</td>
            <td class="px-4 py-2 text-center"><span v-if="row.status===1"><Icon icon="ri:checkbox-circle-line" class="w-5 h-5 text-green-500 mx-auto" /></span><span v-else><Icon icon="ri:close-circle-line" class="w-5 h-5 text-red-500 mx-auto" /></span></td>
            <td class="px-4 py-2 text-xs text-gray-500 font-mono">{{ row.created_at || '-' }}</td>
            <td class="px-4 py-2">{{ row.created_by || '-' }}</td>
          </tr>
        </tbody>
      </table></div>
        </div>
      </div>
    </div>
    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="showModal = false">
      <div class="absolute inset-0 bg-black/50" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4"><h2 class="text-lg font-semibold">{{ editMode==='ADD' ? 'New Quantifier' : 'Edit Quantifier' }}</h2><button @click="showModal=false" class="p-1 hover:bg-gray-100 rounded"><Icon icon="ri:close-line" class="w-5 h-5" /></button></div>
        <div class="flex-1 overflow-y-auto p-2">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Reset Date <span class="text-red-500">*</span></label><input v-model="form.reset_date" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Flowmeter <span class="text-red-500">*</span></label><select v-model="form.flowmeter" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"><option value="">-- Select --</option><option v-for="fm in flowmeters" :key="fm.flowmeter" :value="fm.flowmeter">{{ fm.flowmeter }}</option></select><p v-if="editMode==='ADD'" class="text-xs text-gray-400 mt-1">Leave empty for ALL flowmeters (bulk).</p></div>
            <div><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Value <span class="text-red-500">*</span></label><input v-model.number="form.value" type="number" step="0.001" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" /></div>
            <div class="md:col-span-2"><label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Remark</label><textarea v-model="form.remark" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Monthly reset"></textarea></div>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-6"><button @click="showModal=false" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 shadow-sm transition-all duration-300 cursor-pointer">Cancel</button><button @click="saveQuantifier" :disabled="saving" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-semibold disabled:opacity-50 hover:bg-green-700 shadow-sm transition-all duration-300 cursor-pointer">{{ saving ? 'Saving...' : 'Save' }}</button></div>
      </div>
    </div>
    <!-- Confirm -->
    <div v-if="confirmMsg" class="fixed inset-0 z-50 flex items-center justify-center" @click.self="confirmMsg = ''">
      <div class="absolute inset-0 bg-black/50" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-sm mx-4 p-6 text-center">
        <p class="text-slate-800 font-medium mb-6">{{ confirmMsg }}</p>
        <div class="flex justify-center gap-3"><button @click="confirmMsg = ''" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 shadow-sm transition-all duration-300 cursor-pointer">Cancel</button><button @click="executeConfirm" :disabled="saving" class="px-4 py-2 text-white rounded-lg text-sm font-semibold disabled:opacity-50 cursor-pointer" :class="confirmType==='activate'?'bg-green-600 hover:bg-green-700':'bg-red-600 hover:bg-red-700'">{{ saving ? 'Processing...' : 'Confirm' }}</button></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import quantifierApi from '../api'
const data = ref([]), flowmeters = ref([]), loading = ref(false), saving = ref(false), showModal = ref(false), editMode = ref('ADD'), confirmMsg = ref(''), confirmType = ref(''), confirmAction = ref(null)
const filters = reactive({ flowmeter: '', date_from: '', date_to: '', status: '' })
const form = reactive({ id: null, reset_date: '', flowmeter: '', value: 0, remark: '' })
onMounted(() => { loadFlowmeters(); loadData() })
const loadFlowmeters = async () => { try { const r = await quantifierApi.getFlowmeters(); flowmeters.value = r.data?.data || [] } catch { flowmeters.value = [] } }
const loadData = async () => { loading.value = true; try { const r = await quantifierApi.getQuantifierList({ ...filters }); data.value = r.data?.data || [] } catch { data.value = [] } finally { loading.value = false } }
const resetFilters = () => { filters.flowmeter = ''; filters.date_from = ''; filters.date_to = ''; filters.status = ''; loadData() }
const openCreateModal = () => { editMode.value = 'ADD'; form.id = null; form.reset_date = new Date().toISOString().split('T')[0]; form.flowmeter = ''; form.value = 0; form.remark = ''; showModal.value = true }
const openEditModal = (row) => { editMode.value = 'UPDATE'; form.id = row.id_reset; form.reset_date = row.reset_date; form.flowmeter = row.flowmeter; form.value = parseFloat(row.value||0); form.remark = row.remark||''; showModal.value = true }
const saveQuantifier = async () => { if (!form.reset_date) return; saving.value = true; try { await quantifierApi.storeQuantifier({ mode: editMode.value, reset_date: form.reset_date, flowmeter: form.flowmeter||null, value: form.value, remark: form.remark, ...(editMode.value==='UPDATE'?{id:form.id}:{}) }); showModal.value = false; await loadData() } catch(e) { alert('Failed: '+(e.response?.data?.message||e.message)) } finally { saving.value = false } }
const toggleStatus = (row, type) => { confirmType.value = type; confirmMsg.value = `${type==='activate'?'Activate':'Deactivate'} ${row.flowmeter}?`; confirmAction.value = async () => { if (type==='activate') await quantifierApi.activateQuantifier(row.id_reset); else await quantifierApi.deactivateQuantifier(row.id_reset); await loadData() } }
const executeConfirm = async () => { saving.value = true; try { await confirmAction.value(); confirmMsg.value='' } catch(e) { alert('Failed: '+(e.response?.data?.message||e.message)) } finally { saving.value = false } }
const formatQty = (q) => parseFloat(q||0).toFixed(3)
</script>
