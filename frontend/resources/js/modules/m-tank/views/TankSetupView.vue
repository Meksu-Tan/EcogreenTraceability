<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h5 font-weight-bold">Setup Tank</h1>
        <div class="d-flex align-center gap-1 mt-1">
          <span class="text-caption text-medium-emphasis">TS Setup</span>
          <VIcon icon="ri-arrow-right-s-line" size="14" class="text-medium-emphasis" />
          <span class="text-caption font-weight-semibold text-primary">Tank</span>
        </div>
      </div>
      <div class="d-flex align-center gap-3">
        <div class="d-flex align-center gap-2 text-caption text-medium-emphasis">
          <VIcon icon="ri-time-line" size="14" />
          <span v-if="store.lastSyncAt">Last sync: {{ formatLastSync(store.lastSyncAt) }} by {{ store.lastSyncUser }}</span>
          <span v-else>No sync yet</span>
          <span class="font-weight-bold text-primary">|</span>
          <span>Next sync: {{ countdownDisplay }} ({{ nextSyncLabel }})</span>
        </div>
        <VBtn
          id="btn-sync-tank"
          color="primary"
          variant="tonal"
          :loading="syncing"
          prepend-icon="ri-refresh-line"
          @click="onSync"
        >
          Update Data
        </VBtn>
        <VBtn id="btn-tambah-tank" color="primary" prepend-icon="ri-add-line" @click="openModal">Add</VBtn>
      </div>
    </div>

    <VCard rounded="lg" elevation="1">
      <VCardTitle class="pa-5 pb-3 d-flex align-center gap-2">
        <VIcon icon="ri-water-flash-line" color="primary" size="20" />
        <span class="text-body-1 font-weight-bold">Data Tank</span>
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-0">
        <DataTable
          :columns="columns"
          :data="store.tanks"
          :loading="store.loading"
          row-key="id"
        >
          <template #cell-id="{ row }">
            <span class="text-caption text-medium-emphasis" style="font-family: var(--font-mono); font-weight: 600;">{{ row.id }}</span>
          </template>
          <template #cell-plant="{ row }">
            <span class="font-weight-bold">{{ row.plant_code }}</span>
            <span class="mx-2 text-disabled">|</span>
            <span>{{ row.plant_name }}</span>
          </template>
          <template #cell-tank_height="{ row }">
            <span class="text-body-2 font-weight-semibold">{{ parseFloat(row.tank_height).toLocaleString() }}</span>
            <span class="text-caption text-medium-emphasis ms-1">m</span>
          </template>
          <template #cell-description="{ row }">
            <span class="text-caption">{{ row.description || '-' }}</span>
          </template>
          <template #cell-status="{ row }">
            <VChip
              :color="row.status == 1 ? 'success' : 'error'"
              variant="tonal"
              size="x-small"
              :prepend-icon="row.status == 1 ? 'ri-checkbox-circle-line' : 'ri-close-circle-line'"
            >
              {{ row.status == 1 ? 'Active' : 'Inactive' }}
            </VChip>
          </template>
          <template #actions="{ row }">
            <div class="d-flex justify-center gap-1">
              <VBtn size="x-small" icon="ri-edit-line" color="primary" variant="tonal" @click.stop="onEdit(row)" />
              <VBtn
                size="x-small"
                :icon="row.status == 1 ? 'ri-close-line' : 'ri-check-line'"
                :color="row.status == 1 ? 'error' : 'success'"
                variant="tonal"
                @click.stop="onToggle(row)"
              />
            </div>
          </template>
        </DataTable>
      </VCardText>
    </VCard>

    <TankModal v-model="showModal" :edit-data="editData" :loading="submitting" @submit="onSubmit" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import DataTable from '@/modules/shared/components/DataTable.vue'
import TankModal from './TankModal.vue'
import { useSetupTankStore } from '@/modules/m-tank/stores'
import { useToastStore } from '@/stores/toast.js'
import { useConfirmStore } from '@/stores/confirm.js'

const store      = useSetupTankStore()
const toast      = useToastStore()
const confirmStore = useConfirmStore()
const showModal  = ref(false)
const editData   = ref(null)
const submitting = ref(false)
const syncing    = ref(false)

const SYNC_HOURS = [0, 8, 16]
const countdownSeconds = ref(0)
let countdownTimer = null
let autoSyncTimer = null

const columns = [
  { key: 'id',          label: 'ID' },
  { key: 'plant',       label: 'Plant Code | Name' },
  { key: 'tank_number', label: 'Tank Number' },
  { key: 'tank_height', label: 'Tank Height' },
  { key: 'description', label: 'Description' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created at' },
  { key: 'updated_at',  label: 'Updated at' },
]

const countdownDisplay = computed(() => {
  if (countdownSeconds.value <= 0) return 'now'
  const h = Math.floor(countdownSeconds.value / 3600)
  const m = Math.floor((countdownSeconds.value % 3600) / 60)
  const s = countdownSeconds.value % 60
  if (h > 0) return `${h}h ${m}m`
  if (m > 0) return `${m}m ${s}s`
  return `${s}s`
})

const nextSyncLabel = computed(() => {
  const next = getNextSyncTime()
  return next.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
})

function getNextSyncTime() {
  const now = new Date()
  const currentHour = now.getHours()
  for (const h of SYNC_HOURS) {
    if (currentHour < h) {
      const next = new Date(now)
      next.setHours(h, 0, 0, 0)
      return next
    }
  }
  const tomorrow = new Date(now)
  tomorrow.setDate(tomorrow.getDate() + 1)
  tomorrow.setHours(SYNC_HOURS[0], 0, 0, 0)
  return tomorrow
}

function startCountdown() {
  if (countdownTimer) clearInterval(countdownTimer)
  const nextSync = getNextSyncTime()
  countdownSeconds.value = Math.max(0, Math.floor((nextSync.getTime() - Date.now()) / 1000))

  countdownTimer = setInterval(() => {
    countdownSeconds.value = Math.max(0, countdownSeconds.value - 1)
    if (countdownSeconds.value <= 0) {
      onSync()
      setTimeout(startCountdown, 2000)
    }
  }, 1000)
}

function startAutoSync() {
  if (autoSyncTimer) clearTimeout(autoSyncTimer)
  function scheduleNext() {
    const next = getNextSyncTime()
    const delay = Math.max(1000, next.getTime() - Date.now())
    autoSyncTimer = setTimeout(() => {
      onSync()
      scheduleNext()
    }, delay)
  }
  scheduleNext()
}

function formatLastSync(isoString) {
  if (!isoString) return '-'
  const d = new Date(isoString)
  return d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(async () => {
  try {
    await Promise.all([store.fetchTanks(), store.fetchLastSync()])
  } catch (error) {
    toast.error('Failed to fetch tanks:', error)
  }
  startCountdown()
  startAutoSync()
})

onUnmounted(() => {
  if (countdownTimer) clearInterval(countdownTimer)
  if (autoSyncTimer) clearTimeout(autoSyncTimer)
})

function openModal() {
  editData.value = null
  showModal.value = true
}

function onEdit(row) {
  editData.value = row
  showModal.value = true
}

async function onToggle(row) {
  const isConfirmed = await confirmStore.show({
    title: 'Confirmation',
    message: `${row.status==1?'Deactivate':'Activate'} tank "${row.tank_number}" for plant "${row.plant_name}"?`
  })
  if (!isConfirmed) return
  const r = await store.toggleTank(row.id, row.status)
  if (r.status === 1) {
    toast.success(r.message)
  } else {
    toast.error(r.message)
  }
}

async function onSync() {
  syncing.value = true
  try {
    const r = await store.syncTanks()
    if (r.status === 1 || r.status === 2) {
      toast.success(r.message)
      startCountdown()
    } else {
      toast.error(r.message)
    }
  } catch (error) {
    toast.error('Failed to sync tanks', error)
  } finally {
    syncing.value = false
  }
}

async function onSubmit(data) {
  submitting.value = true
  try {
    const r = editData.value ? await store.editTank(editData.value.id, data) : await store.createTank(data)
    if (r.status===1) {
      toast.success(r.message)
      showModal.value = false
    } else {
      toast.error(r.message)
    }
  } finally {
    submitting.value = false
  }
}
</script>