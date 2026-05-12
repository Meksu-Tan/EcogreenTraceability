<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Material' : 'Tambah Material'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div class="form-group">
      <label class="form-label">Kode Material <span style="color:red">*</span></label>
      <input id="mat-code" v-model="form.code" type="text" class="form-control" :class="{'is-invalid': errors.code}" placeholder="Contoh: RM-001" />
      <div v-if="errors.code" class="form-error">{{ errors.code }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">Kode Non-EUDR</label>
      <input id="mat-code-noneudr" v-model="form.code_noneudr" type="text" class="form-control" placeholder="Opsional" />
    </div>
    <div class="form-group">
      <label class="form-label">Deskripsi <span style="color:red">*</span></label>
      <input id="mat-description" v-model="form.description" type="text" class="form-control" :class="{'is-invalid': errors.description}" placeholder="Nama material" />
      <div v-if="errors.description" class="form-error">{{ errors.description }}</div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Tipe <span style="color:red">*</span></label>
        <select id="mat-type" v-model="form.type" class="form-control form-select" :class="{'is-invalid': errors.type}">
          <option value="">-- Pilih --</option>
          <option value="WIP">WIP</option>
          <option value="RM">Raw Material</option>
          <option value="FG">Finished Good</option>
        </select>
        <div v-if="errors.type" class="form-error">{{ errors.type }}</div>
      </div>
      <div class="form-group">
        <label class="form-label">Yield (%)</label>
        <input id="mat-yield" v-model="form.yield" type="number" class="form-control" min="0" max="100" step="0.1" />
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">QTF Feed</label>
        <input id="mat-qtf-feed" v-model="form.qtf_feed" type="text" class="form-control" />
      </div>
      <div class="form-group">
        <label class="form-label">QTF Rundown</label>
        <input id="mat-qtf-rundown" v-model="form.qtf_rundown" type="text" class="form-control" />
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Kode Supplier Material</label>
      <input id="mat-code-supplier" v-model="form.code_matl_supplier" type="text" class="form-control" />
    </div>
    <div class="form-group">
      <label class="form-label" style="display:flex;align-items:center;gap:.5rem;">
        <input id="mat-status-packaging" v-model="form.status_packaging" type="checkbox" true-value="1" false-value="0" />
        Aktif untuk Packaging
      </label>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editData:   { type: Object, default: null },
  loading:    { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'submit'])

const isEdit = computed(() => !!props.editData)

import { computed } from 'vue'

const form = reactive({
  code: '', code_noneudr: '', description: '', type: '',
  yield: 100, qtf_feed: '', qtf_rundown: '',
  code_matl_supplier: '', status_packaging: '0',
})

const errors = reactive({ code: '', description: '', type: '' })

watch(() => props.editData, (val) => {
  if (val) {
    Object.assign(form, {
      code: val.code || '', code_noneudr: val.code_noneudr || '',
      description: val.description || '', type: val.type || '',
      yield: val.yield || 100, qtf_feed: val.qtf_feed || '',
      qtf_rundown: val.qtf_rundown || '',
      code_matl_supplier: val.code_matl_supplier || '',
      status_packaging: String(val.status_packaging || '0'),
    })
  } else {
    Object.assign(form, { code: '', code_noneudr: '', description: '', type: '', yield: 100, qtf_feed: '', qtf_rundown: '', code_matl_supplier: '', status_packaging: '0' })
  }
  Object.assign(errors, { code: '', description: '', type: '' })
})

function validate() {
  errors.code = errors.description = errors.type = ''
  let ok = true
  if (!form.code)        { errors.code = 'Kode wajib diisi'; ok = false }
  if (!form.description) { errors.description = 'Deskripsi wajib diisi'; ok = false }
  if (!form.type)        { errors.type = 'Tipe wajib dipilih'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>
