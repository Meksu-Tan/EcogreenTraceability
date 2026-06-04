<template>
  <BaseModal
    :model-value="modelValue"
    :title="isEdit ? 'Edit Tank' : 'Tambah Tank'"
    :loading="loading"
    @update:modelValue="$emit('update:modelValue', $event)"
    @submit="handleSubmit"
  >
    <div v-if="!isEdit" class="flex flex-col gap-1.5 mb-4">
      <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">ID Tank (Optional)</label>
      <input
        id="tank-id"
        v-model="form.id"
        type="number"
        placeholder="Biarkan kosong untuk auto-generate"
        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
      />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Plant <span class="text-red-500">*</span></label>
        <select
          id="tank-plant-select"
          v-model="form.plant_code"
          @change="onPlantChange"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.plant_code ? 'border-red-300 bg-red-50' : 'border-gray-300'"
        >
          <option value="">Pilih Plant</option>
          <option v-for="p in plantStore.plants" :key="p.id_plant" :value="p.code_2">
            {{ p.code_2 }} - {{ p.description }}
          </option>
        </select>
        <div v-if="errors.plant_code" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.plant_code }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Nama Plant <span class="text-red-500">*</span></label>
        <input
          id="tank-plant-name"
          v-model="form.plant_name"
          type="text"
          placeholder="Nama Plant"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.plant_name ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.plant_name" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.plant_name }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Nomor Tank <span class="text-red-500">*</span></label>
        <input
          id="tank-number"
          v-model="form.tank_number"
          type="text"
          placeholder="e.g. 210T01"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.tank_number ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.tank_number" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.tank_number }}</div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wide">Tinggi Tank (Height) <span class="text-red-500">*</span></label>
        <input
          id="tank-height-input"
          v-model="form.tank_height"
          type="number"
          step="0.01"
          placeholder="e.g. 1460.00"
          class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:text-slate-500"
          :class="errors.tank_height ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white'"
        />
        <div v-if="errors.tank_height" class="text-[10px] font-bold text-red-500 mt-1 uppercase">{{ errors.tank_height }}</div>
      </div>
    </div>
  </BaseModal>
</template>

<script setup>
import { reactive, computed, watch, onMounted } from 'vue'
import BaseModal from '@/modules/shared/components/BaseModal.vue'
import { useSetupPlantStore } from '@/stores/plant'

const props  = defineProps({ modelValue: Boolean, editData: Object, loading: Boolean })
const emit   = defineEmits(['update:modelValue', 'submit'])

const plantStore = useSetupPlantStore()
const isEdit = computed(() => !!props.editData)

const form = reactive({ id: '', plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
const errors = reactive({ plant_code: '', plant_name: '', tank_number: '', tank_height: '' })

onMounted(() => {
  if (plantStore.plants.length === 0) {
    plantStore.fetchPlants()
  }
})

watch(() => props.editData, (val) => {
  Object.assign(form, val
    ? { id: val.id||'', plant_code: val.plant_code||'', plant_name: val.plant_name||'', tank_number: val.tank_number||'', tank_height: val.tank_height||'' }
    : { id: '', plant_code: '', plant_name: '', tank_number: '', tank_height: '' }
  )
  Object.assign(errors, { plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
})

function onPlantChange() {
  const selected = plantStore.plants.find(p => p.code_2 === form.plant_code)
  if (selected) {
    form.plant_name = selected.description || selected.code_3 || ''
  }
}

function validate() {
  let ok = true
  Object.assign(errors, { plant_code: '', plant_name: '', tank_number: '', tank_height: '' })
  if (!form.plant_code)  { errors.plant_code = 'Wajib diisi'; ok = false }
  if (!form.plant_name)  { errors.plant_name = 'Wajib diisi'; ok = false }
  if (!form.tank_number) { errors.tank_number = 'Wajib diisi'; ok = false }
  if (form.tank_height === '' || form.tank_height === null) { errors.tank_height = 'Wajib diisi'; ok = false }
  return ok
}

function handleSubmit() {
  if (!validate()) return
  emit('submit', { ...form })
}
</script>