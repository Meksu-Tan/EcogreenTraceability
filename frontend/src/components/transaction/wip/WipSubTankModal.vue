<template>
  <WipEntryModalShell
    :open="open"
    title="Assign Specific Sloc (Sub Tank)"
    subtitle="WIP Entry"
    @close="$emit('close')"
  >
    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="text-[11px] font-bold uppercase text-slate-500">Main Sloc</label>
        <input :value="row?.sloc" readonly class="w-full rounded-lg border bg-slate-50 px-3 py-2 text-sm mt-1" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-slate-500">Select Specific Sloc</label>
        <select v-model="selectedTails" multiple required class="w-full rounded-lg border px-3 py-2 text-sm mt-1 min-h-[120px]">
          <option v-for="t in tankTails" :key="t.id_tank_tail" :value="String(t.id_tank_tail)">{{ t.tankNo }}</option>
        </select>
      </div>
    </form>
    <template #footer>
      <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="$emit('close')">Cancel</button>
      <button type="button" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" @click="submit">Save</button>
    </template>
  </WipEntryModalShell>
</template>

<script setup>
import { ref, watch } from 'vue'
import Swal from 'sweetalert2'
import WipEntryModalShell from './WipEntryModalShell.vue'
import { useTransactionWipStore } from '@/stores/transactionWip'
import { useToastStore } from '@/stores/toast'
import { parseTankTails } from './wipFormat'

const props = defineProps({
  open: Boolean,
  row: Object,
  plantId: { type: [String, Number], default: null },
})
const emit = defineEmits(['close', 'saved'])
const store = useTransactionWipStore()
const toast = useToastStore()
const saving = ref(false)
const tankTails = ref([])
const selectedTails = ref([])

async function loadTails() {
  if (!props.row?.id_sloc) return
  tankTails.value = await store.fetchOption('specific-feed-tanks', {
    sloc: props.row.id_sloc,
    ...store.plantParams(props.plantId),
  })
  selectedTails.value = parseTankTails(props.row.id_tank_tail)
}

watch(() => props.open, (v) => { if (v) loadTails() })

async function submit() {
  const confirm = await Swal.fire({
    title: 'Confirm',
    text: 'Save specific sloc for this entry?',
    icon: 'warning',
    showCancelButton: true,
  })
  if (!confirm.isConfirmed) return
  saving.value = true
  try {
    const result = await store.saveSubTank(
      {
        idHead: props.row.id_balance_head ?? props.row.id_trace_head,
        idTank: props.row.id_sloc,
        idTankTail: selectedTails.value,
        mainSloc: props.row.sloc,
      },
      props.plantId
    )
    if (result?.status === 1 || result?.success) {
      toast.success(result.message || 'Saved')
      emit('saved')
      emit('close')
    } else toast.error(result?.message || 'Failed')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed')
  } finally {
    saving.value = false
  }
}
</script>