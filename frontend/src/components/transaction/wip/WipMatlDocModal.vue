<template>
  <WipEntryModalShell
    :open="open"
    :title="isEdit ? 'Edit Material Document' : 'Add Material Document'"
    subtitle="Material Document (SAP)"
    @close="$emit('close')"
  >
    <form class="space-y-4" @submit.prevent="submit">
      <div>
        <label class="text-[11px] font-bold uppercase text-slate-500">Entry Mode</label>
        <input :value="mode" readonly class="w-full rounded-lg border bg-slate-50 px-3 py-2 text-sm mt-1" />
      </div>
      <div>
        <label class="text-[11px] font-bold uppercase text-slate-500">Material Document No (SAP)</label>
        <textarea v-model="number" required rows="6" class="w-full rounded-lg border px-3 py-2 text-sm mt-1 w-full"></textarea>
      </div>
    </form>
    <template #footer>
      <button type="button" class="rounded-lg border px-4 py-2 text-sm" @click="$emit('close')">Cancel</button>
      <button type="button" :disabled="saving" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50" @click="submit">Save</button>
    </template>
  </WipEntryModalShell>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import Swal from 'sweetalert2'
import WipEntryModalShell from './WipEntryModalShell.vue'
import { useTransactionWipStore } from '@/stores/transactionWip'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  open: Boolean,
  row: Object,
  plantId: { type: [String, Number], default: null },
})
const emit = defineEmits(['close', 'saved'])
const store = useTransactionWipStore()
const toast = useToastStore()
const saving = ref(false)
const number = ref('')
const isEdit = computed(() => Boolean(props.row?.material_document))
const mode = computed(() => (isEdit.value ? 'UPDATE' : 'ADD'))

watch(
  () => props.open,
  (v) => {
    if (v && props.row) {
      number.value = props.row.material_document || ''
    }
  }
)

async function submit() {
  const confirm = await Swal.fire({
    title: 'Confirm Action',
    text: `${mode.value} MATL DOC NUMBER?`,
    icon: 'warning',
    showCancelButton: true,
  })
  if (!confirm.isConfirmed) return
  saving.value = true
  try {
    const result = await store.saveMaterialDoc(
      {
        id: props.row.id_trace_head,
        number: number.value,
        mode: mode.value,
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