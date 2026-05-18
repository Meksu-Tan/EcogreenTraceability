<template>
  <div class="fixed inset-0 z-[1050] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @mousedown.self="$emit('close')">
    <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <div>
          <h3 class="text-base font-black text-slate-800">{{ modal.title }}</h3>
          <p class="mt-1 text-xs font-semibold text-slate-500">{{ modal.subtitle }}</p>
        </div>
        <button class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="overflow-y-auto p-5">
        <div v-if="modal.kind === 'entry' && modal.module === 'blending'" class="space-y-5">
          <div class="grid gap-4 md:grid-cols-4">
            <Field label="Entry Mode" readonly value="ADD" />
            <Field label="Entry Number (Auto)" readonly />
            <Field label="Date (Auto Detect)" type="date" required />
            <Field label="Material Document (SAP)" required />
          </div>
          <div class="grid gap-4 md:grid-cols-3">
            <SelectField label="Blended Material" required placeholder="- Select Material -" />
            <SelectField label="Storage Location (SLoc)" required placeholder="- Select Tank -" />
            <SelectField label="Specific Storage Location (SLoc)" placeholder="- Select Specific Sloc No -" multiple />
          </div>
          <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex gap-2">
              <button type="button" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700">
                Add Blend Source & Qty
              </button>
            </div>
            <Field label="Total Qty (MT)" readonly value="0" align="right" class="w-full md:w-64" />
          </div>
          <MiniTable :columns="['No', 'Action', 'Material', 'Qty (MT)']" empty-text="No blend source added" />
        </div>

        <div v-else-if="modal.kind === 'entry' && modal.module === 'transfer'" class="space-y-5">
          <div class="grid gap-4 md:grid-cols-4">
            <Field label="Entry Mode" readonly value="ADD" />
            <Field label="Entry Number (Auto)" readonly />
            <Field label="Date (Auto Detect)" type="date" required />
            <Field label="Material Document (SAP)" required />
          </div>
          <div class="grid gap-4 md:grid-cols-2">
            <SelectField label="Transfer Material" required placeholder="- Select Material -" />
            <SelectField label="Trf Type" required placeholder="- Select Trf -" :options="['- Trf IN -', '- Trf OUT -', '- Trf ALL -']" />
          </div>
          <div class="grid gap-4 md:grid-cols-3">
            <SelectField label="Source SLoc (change this for TRF IN)" required placeholder="- Select Sloc -" hint="Stock: N/A" />
            <SelectField label="Transfer SLoc (change this for TRF OUT)" required placeholder="- Select Sloc -" hint="Stock: N/A" />
            <Field label="Trf Qty (MT)" type="number" required align="right" />
          </div>
          <div class="grid gap-4 md:grid-cols-2">
            <SelectField label="Specific Source Sloc" multiple placeholder="- Select Specific Sloc No -" />
            <SelectField label="Specific Transfer Sloc" multiple placeholder="- Select Specific Sloc No -" />
          </div>
        </div>

        <div v-else-if="['doc', 'po', 'batch'].includes(modal.kind)" class="grid gap-4 md:grid-cols-2">
          <Field :label="modal.kind === 'po' ? 'PO Number' : modal.kind === 'batch' ? 'Batch Number' : 'Document Number'" />
          <Field label="Reference ID" />
        </div>

        <div v-else-if="modal.kind === 'subtank'" class="grid gap-4 md:grid-cols-2">
          <Field label="Main Sloc" />
          <label class="space-y-1 text-sm font-bold text-slate-700">
            <span>Tank Number</span>
            <select class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-green-500 focus:ring-2 focus:ring-green-500/20">
              <option>- Select sub tank -</option>
            </select>
          </label>
        </div>

        <div v-else class="rounded-md border border-slate-200 px-3 py-8 text-center text-slate-500">
          No data
        </div>
      </div>

      <div class="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4">
        <button class="rounded-md px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-200" @click="$emit('close')">
          Close
        </button>
        <button class="rounded-md bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700" type="button">
          Save
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Field from './_TransactionModalField.vue'
import SelectField from './_TransactionModalSelect.vue'
import MiniTable from './_TransactionMiniTable.vue'

defineProps({
  modal: { type: Object, required: true },
})

defineEmits(['close'])
</script>
