<template>
  <WipEntryModalShell
    :open="open"
    :title="panel?.entryLabel || 'Rundown Entry'"
    subtitle="WIP Rundown Entry"
    wide
    @close="close"
  >
    <template #alert>
      Rundown entry MUST BE in the SAME DAY as Feed entry! (Check Feed BATCH/TRACE NUMBER for system date - xYYMMDDxxxx)
    </template>
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Memuat form...</div>
    <form v-else class="space-y-4" @submit.prevent="submit">
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="text-[11px] font-bold uppercase text-slate-500">Rundown Trace No</label>
          <input v-model="form.batch_no" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold mt-1" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-slate-500">Entry Mode</label>
          <input v-model="form.mode" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold mt-1" />
        </div>
        <template v-if="!hideLastBlock">
          <div>
            <label class="text-[11px] font-bold uppercase text-slate-500">Last Rundown (MT) <span class="text-slate-400">{{ lastStatus }}</span></label>
            <input v-model="form.last_rundown" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold mt-1" />
          </div>
          <div>
            <label class="text-[11px] font-bold uppercase text-slate-500">Last Entry Date</label>
            <input v-model="form.last_entryDate" type="date" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold mt-1" />
          </div>
        </template>
        <div class="sm:col-span-2">
          <label class="text-[11px] font-bold uppercase text-slate-500">Sloc</label>
          <select v-model="form.tank" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-400 focus:ring-2 focus:ring-green-500/25 mt-1" @change="loadTankTails">
            <option v-for="t in tanks" :key="t.id_tank" :value="t.id_tank">{{ t.tank }}</option>
          </select>
        </div>
        <div class="sm:col-span-2">
          <label class="text-[11px] font-bold uppercase text-slate-500">Specific Sloc</label>
          <select v-model="form.tankNo" multiple class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-400 focus:ring-2 focus:ring-green-500/25 mt-1 min-h-[88px]">
            <option v-for="t in tankTails" :key="t.id_tank_tail" :value="t.id_tank_tail">{{ t.tankNo }}</option>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-slate-500">Current Rundown (MT)</label>
          <input v-model="form.curr_rundown" type="number" step="any" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-400 focus:ring-2 focus:ring-green-500/25 mt-1" />
        </div>
        <div>
          <label class="text-[11px] font-bold uppercase text-slate-500">Current Entry Date</label>
          <input v-model="form.curr_entryDate" type="date" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-green-400 focus:ring-2 focus:ring-green-500/25 mt-1" />
        </div>
      </div>
    </form>
    <template #footer>
      <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="close">Cancel</button>
      <button type="button" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" @click="submit">
        {{ saving ? 'Saving...' : 'Save' }}
      </button>
    </template>
  </WipEntryModalShell>
</template>

<script setup>
import { ref, watch } from 'vue'
import Swal from 'sweetalert2'
import WipEntryModalShell from './WipEntryModalShell.vue'
import { useTransactionWipStore } from '@/stores/transactionWip'
import { useToastStore } from '@/stores/toast'
import { todayInputDate, parseRundownTraceNo, parseLastBatch } from './wipFormat'

const props = defineProps({ open: Boolean, panel: Object, plantId: { type: [String, Number], default: null } })
const emit = defineEmits(['close', 'saved'])
const store = useTransactionWipStore()
const toast = useToastStore()
const loading = ref(false)
const saving = ref(false)
const hideLastBlock = ref(false)
const lastStatus = ref('-NORMAL-')
const tanks = ref([])
const tankTails = ref([])
const form = ref({
  mode: 'ADD',
  batch_no: '',
  last_rundown: '0',
  last_entryDate: '',
  tank: '',
  tankNo: [],
  curr_rundown: '',
  curr_entryDate: todayInputDate(),
  rundown_id: '',
  feature: '',
  tag_number: '',
})

async function bootstrap() {
  if (!props.panel || !props.plantId) return
  loading.value = true
  const pp = store.plantParams(props.plantId)
  form.value.rundown_id = props.panel.rundownId
  form.value.feature = props.panel.entryLabel
  form.value.tag_number = props.panel.quantifierDcs || ''
  form.value.curr_entryDate = todayInputDate()
  try {
    const batch = await store.fetchOption('rundown-number', { rundownID: props.panel.rundownId, ...pp })
    form.value.batch_no = parseRundownTraceNo(batch)
    const last = await store.fetchOption('rundown-last-batch', { rundownID: props.panel.rundownId, ...pp })
    const lastParsed = parseLastBatch(last)
    hideLastBlock.value = lastParsed.hideLast
    lastStatus.value = lastParsed.status
    form.value.last_rundown = lastParsed.qty
    form.value.last_entryDate = lastParsed.entryDate
    tanks.value = await store.fetchOption('rundown-tanks', { rundownID: props.panel.rundownId, ...pp })
    if (Array.isArray(tanks.value) && tanks.value.length) {
      form.value.tank = tanks.value[0].id_tank
      await loadTankTails()
    }
  } catch {
    toast.error('Gagal memuat form rundown')
  } finally {
    loading.value = false
  }
}

async function loadTankTails() {
  tankTails.value = await store.fetchOption('specific-feed-tanks', {
    sloc: form.value.tank,
    ...store.plantParams(props.plantId),
  })
}

async function submit() {
  if (Number(form.value.last_rundown) > Number(form.value.curr_rundown)) {
    toast.error('Current Rundown must be bigger than Last Rundown')
    return
  }
  const confirm = await Swal.fire({
    title: 'Confirm Action',
    text: `ADD ${props.panel.entryLabel}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, ADD it',
  })
  if (!confirm.isConfirmed) return
  saving.value = true
  try {
    const result = await store.saveRundown({ ...form.value }, props.plantId)
    if (result?.status === 1 || result?.success) {
      toast.success(result.message || 'Saved')
      emit('saved')
      close()
    } else toast.error(result?.message || 'Save failed')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed')
  } finally {
    saving.value = false
  }
}

function close() { emit('close') }
watch(() => props.open, (v) => { if (v) bootstrap() })
</script>