<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-show="isOpen"
        class="fixed inset-0 z-[100] overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
    :aria-hidden="!isOpen"
  >
    <div class="relative flex min-h-full items-center justify-center py-10 px-4 sm:px-6">
      <div
        class="fixed inset-0 z-[1] bg-black/50 backdrop-blur-sm transition-opacity duration-300"
        aria-hidden="true"
        @click="closeModal"
      />

      <div
        class="relative z-[2] mx-auto flex w-full max-w-5xl max-h-[min(92vh,940px)] flex-col overflow-hidden rounded-2xl bg-white text-left shadow-[0_25px_50px_-12px_rgba(15,23,42,0.22)] ring-1 ring-slate-900/[0.05]"
      >
        <!-- Header -->
        <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 via-green-600 to-green-600 px-6 py-4 sm:px-8">
          <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-green-100/90">Transfer</p>
            <h3 id="modal-title" class="truncate text-lg font-bold tracking-tight text-white sm:text-xl">
              Transfer ke feed tank
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
            <p class="text-sm font-semibold text-slate-600">Memuat form transfer…</p>
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
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
              <div class="mb-5 border-b border-slate-100 pb-4">
                <h4 class="text-sm font-bold text-slate-800 sm:text-base">Ringkasan transfer</h4>
                <p class="mt-1 text-xs text-slate-500">Dari storage ke feed tank — isi tangki sumber &amp; tujuan lalu tambahkan material.</p>
              </div>

              <div
                v-if="isPlantLocked"
                class="mb-4 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-3.5 py-2.5 text-xs font-semibold text-green-800"
              >
                <Icon icon="ri:lock-line" class="w-4 h-4 opacity-70" />
                Sloc terkunci ke plant: {{ plantSelectionStore.selectedPlantName }}
              </div>

              <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Nomor entri (auto)</label>
                  <input
                    :value="form.entry_no"
                    type="text"
                    readonly
                    :placeholder="entryNoPlaceholder"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 font-mono text-sm font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Tanggal transfer</label>
                  <input
                    v-model="form.entry_date"
                    type="date"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
                <div class="space-y-1.5 sm:col-span-2 lg:col-span-1">
                  <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material document</label>
                  <input
                    v-model="form.material_document"
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm uppercase shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                  />
                </div>
              </div>

              <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 sm:p-5">
                  <h4 class="mb-4 flex items-center gap-2 border-b border-slate-200/80 pb-2 text-xs font-bold uppercase tracking-wide text-slate-700">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-green-600 text-[10px] text-white">1</span>
                    Tangki sumber (storage)
                  </h4>
                  <div class="space-y-4">
                    <div class="space-y-1.5">
                      <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc sumber</label>
                      <select
                        v-model="form.source_tank"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                        @change="onSourceTankChange"
                      >
                        <option value="">— Pilih Sloc —</option>
                        <option v-for="tank in tanks" :key="tank.tank" :value="tank.tank">
                          {{ tank.tank }}
                        </option>
                      </select>
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sub-Sloc</label>
                      <div class="max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 shadow-inner">
                        <div class="grid grid-cols-2 gap-2">
                          <label
                            v-for="detail in sourceTankDetails"
                            :key="detail.id_tank_tail"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-transparent px-2 py-1.5 text-xs font-medium text-slate-700 transition hover:border-green-200 hover:bg-green-50/60"
                          >
                            <input
                              v-model="form.source_tank_id"
                              type="checkbox"
                              :value="detail.id_sloc"
                              class="h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
                            />
                            <span>{{ detail.tankNo }}</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200/90 bg-slate-50/50 p-4 sm:p-5">
                  <h4 class="mb-4 flex items-center gap-2 border-b border-slate-200/80 pb-2 text-xs font-bold uppercase tracking-wide text-slate-700">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-green-600 text-[10px] text-white">2</span>
                    Tangki tujuan (feed)
                  </h4>
                  <div class="space-y-4">
                    <div class="space-y-1.5">
                      <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sloc tujuan</label>
                      <select
                        v-model="form.trf_tank"
                        required
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                        @change="onTrfTankChange"
                      >
                        <option value="">— Pilih Sloc —</option>
                        <option v-for="tank in destTanks" :key="tank.tank" :value="tank.tank">
                          {{ tank.tank }}
                        </option>
                      </select>
                    </div>
                    <div class="space-y-1.5">
                      <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Sub-Sloc</label>
                      <div class="max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 shadow-inner">
                        <div class="grid grid-cols-2 gap-2">
                          <label
                            v-for="detail in trfTankDetails"
                            :key="detail.id_tank_tail"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-transparent px-2 py-1.5 text-xs font-medium text-slate-700 transition hover:border-green-200 hover:bg-green-50/60"
                          >
                            <input
                              v-model="form.trf_tank_id"
                              type="checkbox"
                              :value="detail.id_sloc"
                              class="h-4 w-4 rounded border-slate-300 text-green-600 focus:ring-green-500"
                            />
                            <span>{{ detail.tankNo }}</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex flex-col gap-4 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-slate-900"
                    @click="isMaterialModalOpen = true"
                  >
                    <Icon icon="ri:layers-line" class="w-4 h-4 text-xs opacity-90" />
                    Material &amp; Qty
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="!canSubmit || loading"
                    @click="handleSubmit"
                  >
                    Konfirmasi transfer
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

            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
              <div class="border-b border-slate-100 bg-slate-50/90 px-4 py-3 sm:px-5">
                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-600">Material yang ditransfer</h4>
              </div>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                  <thead>
                    <tr class="bg-slate-50/80 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                      <th class="w-12 px-4 py-3">No</th>
                      <th class="px-4 py-3">Aksi</th>
                      <th class="min-w-[160px] px-4 py-3">Material</th>
                      <th class="px-4 py-3 text-right">Qty (MT)</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-if="materialList.length === 0">
                      <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">
                        Belum ada material — gunakan "Material &amp; Qty".
                      </td>
                    </tr>
                    <tr v-for="(mat, index) in materialList" :key="mat.id" class="transition hover:bg-slate-50/80">
                      <td class="px-4 py-3 text-center text-slate-500">{{ index + 1 }}</td>
                      <td class="px-4 py-3">
                        <button
                          type="button"
                          class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 hover:text-red-800"
                          @click="removeMaterial(mat.id)"
                        >
                          <Icon icon="ri:delete-bin-line" class="w-4 h-4" />
                        </button>
                      </td>
                      <td class="max-w-[280px] px-4 py-3 text-slate-800">{{ mat.material }}</td>
                      <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900">{{ mat.qty }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </form>
        </div>

        <!-- Material (nested) -->
        <div v-if="isMaterialModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
          <div
            class="absolute inset-0 bg-black/40 backdrop-blur-sm"
            aria-hidden="true"
            @click="isMaterialModalOpen = false"
          />
          <div class="relative z-[1] w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
            <div class="flex items-center justify-between bg-gradient-to-r from-green-700 to-green-700 px-5 py-4">
              <h3 class="text-base font-bold text-white">Tambah material &amp; qty</h3>
              <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-green-100 transition hover:bg-white/10 hover:text-white"
                @click="isMaterialModalOpen = false"
              >
                <Icon icon="ri:close-line" class="w-4 h-4" />
              </button>
            </div>
            <div class="space-y-4 p-5 sm:p-6">
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Material</label>
                <select
                  v-model="materialForm.id_material"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                >
                  <option value="">— Pilih material —</option>
                  <option v-for="material in materials" :key="material.id_material" :value="material.id_material">
                    {{ material.material }}
                  </option>
                </select>
              </div>
              <div class="space-y-1.5">
                <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Qty (MT)</label>
                <input
                  v-model="materialForm.qty"
                  type="number"
                  step="0.001"
                  placeholder="0.000"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-right text-sm font-bold tabular-nums shadow-sm focus:border-green-400 focus:outline-none focus:ring-2 focus:ring-green-500/25"
                />
              </div>
              <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <button
                  type="button"
                  class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-200"
                  @click="isMaterialModalOpen = false"
                >
                  Batal
                </button>
                <button
                  type="button"
                  class="rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                  :disabled="!canAddMaterial"
                  @click="addMaterial"
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
            class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-200"
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
            <span>{{ loading ? 'Menyimpan…' : 'Konfirmasi transfer' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  </Transition>
</Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useTsRawRmEntryStore } from '@/modules/ts-raw/stores'
import { usePlantSelectionStore } from '@/stores/plant'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  isOpen: { type: Boolean, required: true }
})

const emit = defineEmits(['close', 'saved'])

const store = useTsRawRmEntryStore()
const plantSelectionStore = usePlantSelectionStore()
const toastStore = useToastStore()

// State
const isMaterialModalOpen = ref(false)
const initLoading = ref(false)
const initError = ref(null)
const sourceTankDetails = ref([])
const trfTankDetails = ref([])

const form = ref({
  entry_date: new Date().toISOString().split('T')[0],
  entry_no: '',
  source_tank: '',
  source_tank_id: [],
  trf_tank: '',
  trf_tank_id: [],
  material_document: ''
})

const materialForm = ref({
  id_material: '',
  qty: ''
})

// Computed
const loading = computed(() => store.loading)
const tanks = computed(() => store.tanks)
const destTanks = computed(() => {
  if (form.value.source_tank) {
    const selectedSourceTank = tanks.value.find(t => t.tank === form.value.source_tank)
    if (selectedSourceTank && selectedSourceTank.id_plant) {
      return store.destTanks.filter(t => t.id_plant === selectedSourceTank.id_plant)
    }
  }
  return store.destTanks
})
const materials = computed(() => store.materials)
const materialList = computed(() => store.supplierList)
const totalQty = computed(() => store.totalQty)

const canAddMaterial = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_no &&
         materialForm.value.id_material &&
         materialForm.value.qty &&
         parseFloat(materialForm.value.qty) > 0
})

const canSubmit = computed(() => {
  return !initLoading.value &&
         !initError.value &&
         form.value.entry_date &&
         form.value.entry_no &&
         form.value.source_tank &&
         form.value.source_tank_id.length > 0 &&
         form.value.trf_tank &&
         form.value.trf_tank_id.length > 0 &&
         materialList.value.length > 0
})

const isPlantLocked = computed(() => {
  const id = plantSelectionStore.selectedPlantId
  return id !== null && id !== undefined && id !== '' && Number(id) !== 0
})

const entryNoPlaceholder = computed(() => {
  if (form.value.entry_no) return ''
  if (isPlantLocked.value) return 'Menghasilkan nomor…'
  return 'Pilih plant atau sloc sumber'
})

async function generateEntryNumber(extra = {}) {
  const params = {
    id_plant: plantSelectionStore.selectedPlantId ?? 0,
    ...extra
  }
  await store.generateTransferNumber(params)
  form.value.entry_no = store.trfNumber || ''
  return form.value.entry_no
}

async function loadTankOptions() {
  const params = { id_plant: plantSelectionStore.selectedPlantId }
  await Promise.all([
    store.fetchTanks(params, true),
    store.fetchDestTanks(params)
  ])
}

async function autoSelectTanksWhenSingle() {
  if (!isPlantLocked.value) return
  if (!form.value.source_tank && tanks.value.length === 1) {
    form.value.source_tank = tanks.value[0].tank
    await onSourceTankChange()
  }
}

// Methods
async function bootstrap() {
  initLoading.value = true
  initError.value = null
  store.resetForm()

  form.value = {
    entry_date: new Date().toISOString().split('T')[0],
    entry_no: '',
    source_tank: '',
    source_tank_id: [],
    trf_tank: '',
    trf_tank_id: [],
    material_document: ''
  }
  materialForm.value = { id_material: '', qty: '' }
  sourceTankDetails.value = []
  trfTankDetails.value = []
  isMaterialModalOpen.value = false

  try {
    await Promise.all([
      loadTankOptions(),
      store.fetchMaterials()
    ])

    if (isPlantLocked.value) {
      await generateEntryNumber()
      await autoSelectTanksWhenSingle()
    }
  } catch (error) {
    toastStore.error('Initialization error:', error)
    initError.value =
      error.response?.data?.message ||
      error.message ||
      'Gagal memuat data form. Pastikan API Laravel (Sanctum) dan MySQL dapat dijangkau dari frontend.'
  } finally {
    initLoading.value = false
  }

  if (form.value.entry_no && !initError.value) {
    try {
      await store.fetchSupplierList(form.value.entry_no)
    } catch (error) {
      toastStore.error('Material temp list load:', error)
      initError.value =
        error.response?.data?.message ||
        error.message ||
        'Gagal memuat daftar material sementara.'
    }
  }
}

async function onSourceTankChange() {
  form.value.source_tank_id = []
  if (form.value.source_tank) {
    await store.fetchTankDetails(form.value.source_tank, plantSelectionStore.selectedPlantId)
    sourceTankDetails.value = [...store.tankDetails]
    if (sourceTankDetails.value.length === 1) {
      form.value.source_tank_id = [sourceTankDetails.value[0].id_sloc]
    }
    
    // Automatically set destination feed tank
    form.value.trf_tank = form.value.source_tank.replace('Storage', 'Feed')
    await onTrfTankChange()

    if (!isPlantLocked.value) {
      await generateEntryNumber({
        id_plant: 0,
        tank_desc: form.value.source_tank
      })
    }
  } else {
    sourceTankDetails.value = []
    form.value.trf_tank = ''
    trfTankDetails.value = []
  }
}

async function onTrfTankChange() {
  form.value.trf_tank_id = []
  if (form.value.trf_tank) {
    await store.fetchTankDetails(form.value.trf_tank, plantSelectionStore.selectedPlantId)
    trfTankDetails.value = [...store.tankDetails]
    if (trfTankDetails.value.length === 1) {
      form.value.trf_tank_id = [trfTankDetails.value[0].id_sloc]
    }
  } else {
    trfTankDetails.value = []
  }
}

async function addMaterial() {
  if (!canAddMaterial.value) return
  const selectedTankObj = tanks.value.find(t => t.tank === form.value.source_tank)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    await store.addSupplier({
      entry_no: form.value.entry_no,
      id_material: materialForm.value.id_material,
      qty: parseFloat(materialForm.value.qty),
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId
    })
    materialForm.value = { id_material: '', qty: '' }
    isMaterialModalOpen.value = false
  } catch (error) {
    toastStore.error('Add material error:', error)
  }
}

async function removeMaterial(id) {
  if (confirm('Remove this material?')) {
    await store.deleteSupplier(id, form.value.entry_no)
  }
}

async function handleSubmit() {
  if (!canSubmit.value) return

  if (form.value.source_tank_id.length === 0 || form.value.trf_tank_id.length === 0) {
    toastStore.error('Please select both source and destination tanks')
    return
  }

  const selectedTankObj = tanks.value.find(t => t.tank === form.value.source_tank)
  const autoPlantId = selectedTankObj ? selectedTankObj.id_plant : 0

  try {
    await store.transferEntry({
      ...form.value,
      source_tank: form.value.source_tank_id,
      tank_no: [],
      trf_tank: form.value.trf_tank_id,
      trf_tank_no: [],
      id_plant: plantSelectionStore.selectedPlantId || autoPlantId
    })
    emit('saved')
    closeModal()
  } catch (error) {
    toastStore.error('Submit error:', error)
    const errorMsg = error.response?.data?.message || error.message || 'Transfer failed'
    toastStore.error(errorMsg)
    if (form.value.entry_no) {
      try {
        await store.clearTempList(form.value.entry_no)
      } catch (e) {
        toastStore.error('Failed to clear temp list on submit error:', e)
      }
    }
    await bootstrap()
  }
}

function closeModal() {
  if (document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
  emit('close')
}

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      void bootstrap()
    } else {
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur()
      }
      initLoading.value = false
      initError.value = null
      isMaterialModalOpen.value = false
    }
  },
  { flush: 'post' }
)

watch(
  () => plantSelectionStore.selectedPlantId,
  async (plantId, prevId) => {
    if (!props.isOpen || plantId === prevId) return

    form.value.source_tank = ''
    form.value.trf_tank = ''
    form.value.tank_no = []
    form.value.trf_tank_no = []
    sourceTankDetails.value = []
    trfTankDetails.value = []
    form.value.entry_no = ''

    try {
      await loadTankOptions()
      if (isPlantLocked.value) {
        await generateEntryNumber()
        await autoSelectTanksWhenSingle()
        if (form.value.entry_no) {
          await store.fetchSupplierList(form.value.entry_no)
        }
      }
    } catch (error) {
      toastStore.error('Plant switch reload:', error)
    }
  }
)
</script>
