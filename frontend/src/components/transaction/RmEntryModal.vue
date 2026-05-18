<template>
  <!-- Teleport avoids clipping/stacking bugs when modal is inside layout regions with overflow (decoupled SPA). -->
  <Teleport to="body">
  <div
    v-show="isOpen"
    class="fixed inset-0 z-[100] overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    :aria-hidden="!isOpen"
  >
    <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
      <!-- Latar: blur halaman di belakang (bukan overlay abu-abu pekat) -->
      <div
        class="fixed inset-0 z-[1] bg-white/[0.14] backdrop-blur-2xl backdrop-saturate-150 transition-opacity duration-300"
        aria-hidden="true"
        @click="closeModal"
      />

      <div
        class="relative z-[2] mx-auto flex w-full max-w-5xl max-h-[min(92vh,940px)] flex-col overflow-hidden rounded-2xl bg-white text-left shadow-[0_25px_50px_-12px_rgba(15,23,42,0.22)] ring-1 ring-slate-900/[0.05]"
      >
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 via-green-600 to-green-600 px-6 py-4 sm:px-8">
          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">RM Entry</p>
            <h3 id="modal-title" class="truncate text-lg font-bold tracking-tight text-white sm:text-xl">
              Raw Material Entry
            </h3>
          </div>
          <button
            type="button"
            @click="closeModal"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white transition hover:bg-white/25"
            aria-label="Tutup"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="relative min-h-0 flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-5 py-5 sm:px-8 sm:py-6">
          <div
            v-if="initLoading"
            class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 rounded-xl bg-white/75 backdrop-blur-sm"
          >
            <svg class="h-11 w-11 animate-spin text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <p class="text-sm font-semibold text-slate-600">Memuat form RM Entry…</p>
          </div>
          <div
            v-if="initError && !initLoading"
            class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200/80 bg-red-50/95 px-4 py-3.5 text-sm text-red-800 shadow-sm"
          >
            <span class="min-w-0 flex-1 leading-snug">{{ initError }}</span>
            <button
              type="button"
              class="shrink-0 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-red-700"
              @click="bootstrap"
            >
              Coba lagi
            </button>
          </div>
          <form @submit.prevent="handleSubmit" :class="{ 'pointer-events-none opacity-45': initLoading }" class="space-y-5">
            <!-- Kartu utama -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
              <div class="mb-5 flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 pb-4">
                <h4 class="text-sm font-bold text-slate-800 sm:text-base">Detail entri</h4>
                <p class="max-w-md text-xs leading-relaxed text-green-700 sm:text-[11px]">
                  Jangan ubah material setelah menambah supplier.
                </p>
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Mode</label>
                  <input
                    v-model="form.mode"
                    type="text"
                    readonly
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-bold text-slate-800"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Plant</label>
                  <select
                    v-model="form.id_plant"
                    required
                    :disabled="form.mode === 'UPDATE'"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:cursor-not-allowed"
                    @change="onPlantChange"
                  >
                    <option value="">Pilih plant</option>
                    <option v-for="plant in plants" :key="plant.id_plant" :value="plantValue(plant)">
                      {{ plant.description }}
                    </option>
                  </select>
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Nomor entri (auto)</label>
                  <input
                    v-model="form.rm_number"
                    type="text"
                    readonly
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-sm font-bold text-slate-900"
                  />
                </div>
                <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Tanggal</label>
                  <input
                    v-model="form.entry_date"
                    type="date"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
              </div>

              <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc</label>
                  <select
                    v-model="form.id_tank"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                    @change="onTankChange"
                  >
                    <option value="">— Pilih Sloc —</option>
                    <option v-for="tank in tanks" :key="tank.id_tank" :value="tank.id_tank">
                      {{ tank.tank }}
                    </option>
                  </select>
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material doc (SAP)</label>
                  <input
                    v-model="form.material_document"
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
                <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Purchase order (PO)</label>
                  <input
                    v-model="form.po_so"
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
              </div>

              <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
                <div class="space-y-1.5 lg:col-span-3">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material</label>
                  <select
                    v-model="form.id_material"
                    required
                    :disabled="form.mode === 'UPDATE'"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25 disabled:bg-slate-100 disabled:cursor-not-allowed"
                  >
                    <option value="">— Pilih material —</option>
                    <option v-for="material in materials" :key="material.id_material" :value="material.id_material">
                      {{ material.material }}
                    </option>
                  </select>
                </div>
                <div class="space-y-1.5 lg:col-span-2">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sub-Sloc</label>
                  <div class="max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/80 p-3 shadow-inner">
                    <p v-if="tankDetails.length === 0" class="py-2 text-center text-xs italic text-slate-400">Pilih Sloc terlebih dahulu</p>
                    <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                      <label
                        v-for="detail in tankDetails"
                        :key="detail.id_tank_tail"
                        class="flex cursor-pointer items-center gap-2 rounded-lg border border-transparent bg-white px-2 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:border-green-200 hover:bg-green-50/50"
                      >
                        <input
                          v-model="form.id_tank_tail"
                          type="checkbox"
                          :value="detail.id_tank_tail"
                          class="h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
                        />
                        <span>{{ detail.tankNo }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-slate-900"
                    @click="isSupplierModalOpen = true"
                  >
                    <i class="fas fa-user-plus text-xs opacity-90" />
                    Supplier & Qty
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="!canSubmit || loading"
                    @click="handleSubmit"
                  >
                    Simpan entri
                  </button>
                </div>
                <div class="flex items-center justify-end gap-3 sm:min-w-[200px]">
                  <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Total (MT)</span>
                  <input
                    :value="totalQty"
                    type="text"
                    readonly
                    class="w-36 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right text-sm font-bold tabular-nums text-slate-900"
                  />
                </div>
              </div>
            </div>

            <!-- Tabel supplier -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
              <div class="border-b border-slate-100 bg-slate-50/90 px-4 py-3 sm:px-5">
                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Daftar supplier</h4>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                  <thead>
                    <tr class="bg-slate-50/80 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                      <th class="w-12 px-4 py-3">No</th>
                      <th class="px-4 py-3">Aksi</th>
                      <th class="min-w-[120px] px-4 py-3">Material</th>
                      <th class="min-w-[140px] px-4 py-3">Supplier</th>
                      <th class="px-4 py-3">Batch SAP</th>
                      <th class="px-4 py-3 text-right">Qty (MT)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-if="supplierList.length === 0">
                      <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-400">
                        Belum ada supplier — gunakan tombol “Supplier &amp; Qty”.
                      </td>
                    </tr>
                    <tr v-for="(sup, index) in supplierList" :key="sup.id" class="transition hover:bg-slate-50/80">
                      <td class="px-4 py-3 text-center text-slate-500">{{ index + 1 }}</td>
                      <td class="px-4 py-3">
                        <button
                          type="button"
                          class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 hover:text-red-800"
                          @click="removeSupplier(sup.id)"
                        >
                          <i class="fas fa-trash text-sm" />
                        </button>
                      </td>
                      <td class="max-w-[220px] px-4 py-3 text-slate-800">{{ sup.material }}</td>
                      <td class="max-w-[200px] px-4 py-3 text-slate-700">{{ sup.supplier }}</td>
                      <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ sup.batch_sap }}</td>
                      <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">{{ sup.qty }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </form>
        </div>

        <!-- Supplier (nested) — sama: blur ringan -->
        <div
          v-if="isSupplierModalOpen"
          class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6"
        >
          <div
            class="absolute inset-0 bg-white/25 backdrop-blur-md"
            aria-hidden="true"
            @click="isSupplierModalOpen = false"
          />
          <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
            <div class="flex items-center justify-between bg-gradient-to-r from-slate-800 to-slate-900 px-5 py-4">
              <h3 class="text-base font-bold text-white">Tambah supplier &amp; qty</h3>
              <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white"
                @click="isSupplierModalOpen = false"
              >
                <i class="fas fa-times" />
              </button>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Supplier</label>
                <select
                  v-model="supplierForm.id_supplier"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  @change="onSupplierChange"
                >
                  <option value="">— Pilih supplier —</option>
                  <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.text }}
                  </option>
                </select>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Batch SAP (auto)</label>
                  <input
                    v-model="supplierForm.batch_sap"
                    type="text"
                    readonly
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-sm text-slate-700"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Qty (MT)</label>
                  <input
                    v-model="supplierForm.qty"
                    type="number"
                    step="0.001"
                    placeholder="0.000"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-right text-sm font-bold tabular-nums shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
              </div>
              <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                  @click="isSupplierModalOpen = false"
                >
                  Batal
                </button>
                <button
                  type="button"
                  class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                  :disabled="!canAddSupplier"
                  @click="addSupplier"
                >
                  Tambahkan
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-slate-200/90 bg-white px-5 py-4 sm:px-8">
          <button
            type="button"
            @click="closeModal"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
          >
            Tutup
          </button>
          <button
            type="button"
            @click="handleSubmit"
            :disabled="!canSubmit || loading"
            class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-slate-300"
          >
            <svg v-if="loading" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ loading ? 'Menyimpan…' : 'Simpan RM Entry' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTransactionRmEntryStore } from '@/stores/transactionRmEntry'
import { usePlantSelectionStore } from '@/stores/plantSelection'
import { useSetupPlantStore } from '@/stores/setupPlant'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  entry: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const store = useTransactionRmEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const plantStore = useSetupPlantStore()

// State
const isSupplierModalOpen = ref(false)
const initLoading = ref(false)
const initError = ref(null)

const form = ref({
  mode: 'ADD',
  id_plant: '',
  entry_date: new Date().toISOString().split('T')[0],
  rm_number: '',
  id_material: '',
  id_tank: '',
  id_tank_tail: [],
  material_document: '',
  po_so: '',
  total_qty: 0
})

const supplierForm = ref({
  id_supplier: '',
  batch_sap: '',
  qty: ''
})

// Computed
const loading = computed(() => store.loading)
const tanks = computed(() => store.tanks)
const tankDetails = computed(() => store.tankDetails)
const materials = computed(() => store.materials)
const suppliers = computed(() => store.suppliers)
const supplierList = computed(() => store.supplierList)
const totalQty = computed(() => store.totalQty)
const plants = computed(() => plantStore.plants)

const canAddSupplier = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         supplierForm.value.id_supplier &&
         supplierForm.value.batch_sap &&
         supplierForm.value.qty &&
         parseFloat(supplierForm.value.qty) > 0 &&
         form.value.id_material
})

const canSubmit = computed(() => {
  const qtyStr = totalQty.value ? String(totalQty.value).replace(/,/g, '') : '0'
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_date &&
         form.value.id_plant &&
         form.value.rm_number &&
         form.value.id_material &&
         form.value.id_tank &&
         form.value.id_tank_tail.length > 0 &&
         supplierList.value.length > 0 &&
         parseFloat(qtyStr) > 0
})

// Cepat: hanya nomor + master data di jalur kritis; daftar supplier di-load saat sub-modal dibuka
async function bootstrap() {
  initLoading.value = true
  initError.value = null
  store.resetForm()

  if (props.entry) {
    let tankTails = []
    if (props.entry.id_tank_tail) {
      try {
        tankTails = typeof props.entry.id_tank_tail === 'string'
          ? JSON.parse(props.entry.id_tank_tail)
          : props.entry.id_tank_tail
      } catch (e) {
        console.error(e)
      }
    }
    const parsedTails = Array.isArray(tankTails) ? tankTails.map(Number) : []

    form.value = {
      mode: 'UPDATE',
      id_balance_head: props.entry.id_balance_head,
      id_plant: props.entry.id_plant || '',
      entry_date: props.entry.entry_date || new Date().toISOString().split('T')[0],
      rm_number: props.entry.trace_no || '',
      id_material: props.entry.id_material || '',
      id_tank: props.entry.id_tank || '',
      id_tank_tail: parsedTails,
      material_document: props.entry.material_document || '',
      po_so: props.entry.po_so || '',
      total_qty: props.entry.init_qty ? parseFloat(String(props.entry.init_qty).replace(/,/g, '')) : 0
    }
  } else {
    form.value = {
      mode: 'ADD',
      id_plant: plantSelectionStore.selectedPlantId || '',
      entry_date: new Date().toISOString().split('T')[0],
      rm_number: '',
      id_material: '',
      id_tank: '',
      id_tank_tail: [],
      material_document: '',
      po_so: '',
      total_qty: 0
    }
  }

  supplierForm.value = { id_supplier: '', batch_sap: '', qty: '' }
  isSupplierModalOpen.value = false

  try {
    await Promise.all([
      plantStore.fetchPlants(),
      store.fetchTanks(),
      store.fetchMaterials()
    ])
    
    if (props.entry) {
      if (form.value.id_tank) {
        await store.fetchTankDetails(form.value.id_tank)
      }
    } else {
      if (form.value.id_plant) await loadRmNumber()
    }
  } catch (error) {
    console.error('Initialization error:', error)
    initError.value =
      error.response?.data?.message ||
      error.message ||
      'Gagal memuat data form. Pastikan API Laravel (Sanctum) dan MySQL dapat dijangkau dari frontend.'
  } finally {
    initLoading.value = false
  }

  if (form.value.rm_number && !initError.value) {
    try {
      const params = {}
      if (form.value.mode === 'UPDATE') {
        params.mode = 'UPDATE'
        params.id_balance_head = form.value.id_balance_head
      }
      await store.fetchSupplierList(form.value.rm_number, params)
      await store.fetchTotalQty(form.value.rm_number, params)
    } catch (error) {
      console.error('Supplier list load:', error)
      initError.value =
        error.response?.data?.message ||
        error.message ||
        'Gagal memuat baris supplier.'
    }
  }
}

async function loadRmNumber() {
  if (!form.value.id_plant) {
    form.value.rm_number = ''
    return
  }

  await store.generateRmNumber({ id_plant: form.value.id_plant })
  form.value.rm_number = store.rmNumber || ''

  if (!form.value.rm_number) {
    initError.value = 'Nomor RM tidak dihasilkan. Periksa hak akses, id_plant user, dan koneksi database (MySQL).'
  }
}

async function onPlantChange() {
  initError.value = null
  store.supplierList = []
  store.totalQty = '0.000'
  form.value.rm_number = ''
  await loadRmNumber()
  if (form.value.rm_number) {
    await store.fetchSupplierList(form.value.rm_number)
  }
}

async function onTankChange() {
  form.value.id_tank_tail = []
  if (form.value.id_tank) {
    await store.fetchTankDetails(form.value.id_tank)
  }
}

async function onSupplierChange() {
  if (supplierForm.value.id_supplier) {
    const batchCode = await store.generateBatchCode(supplierForm.value.id_supplier)
    supplierForm.value.batch_sap = batchCode
  }
}

async function addSupplier() {
  if (!canAddSupplier.value) return

  try {
    const payload = {
      entry_no: form.value.rm_number,
      id_supplier: supplierForm.value.id_supplier,
      id_material: form.value.id_material,
      qty: parseFloat(supplierForm.value.qty),
      batch_sap: supplierForm.value.batch_sap,
      id_plant: form.value.id_plant
    }

    if (form.value.mode === 'UPDATE') {
      payload.mode = 'UPDATE'
      payload.idHead = form.value.id_balance_head
    }

    await store.addSupplier(payload)

    supplierForm.value = {
      id_supplier: '',
      batch_sap: '',
      qty: ''
    }
    isSupplierModalOpen.value = false
  } catch (error) {
    console.error('Add supplier error:', error)
  }
}

async function removeSupplier(id) {
  if (confirm('Are you sure you want to remove this supplier?')) {
    const params = {}
    if (form.value.mode === 'UPDATE') {
      params.mode = 'UPDATE'
      params.id_balance_head = form.value.id_balance_head
    }
    await store.deleteSupplier(id, form.value.rm_number, params)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return

  try {
    const data = {
      ...form.value,
      total_qty: parseFloat(String(totalQty.value ?? '0').replace(/,/g, ''), 10)
    }

    await store.createEntry(data)
    emit('saved')
    closeModal()
  } catch (error) {
    console.error('Submit error:', error)
  }
}

function plantValue(plant) {
  return plant?.code_3 || plant?.id_plant
}

function closeModal() {
  emit('close')
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      void bootstrap()
    } else {
      initLoading.value = false
      initError.value = null
      isSupplierModalOpen.value = false
    }
  },
  { flush: 'post' }
)

watch(isSupplierModalOpen, async (open) => {
  if (!open || store.suppliers.length > 0) return
  try {
    await store.searchSuppliers('')
  } catch (e) {
    console.error(e)
  }
})
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
