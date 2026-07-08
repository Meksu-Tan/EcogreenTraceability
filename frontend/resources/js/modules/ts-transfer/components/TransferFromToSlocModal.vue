<template>
  <VDialog
    :model-value="isOpen"
    max-width="560"
    @update:model-value="$emit('update:isOpen', $event)"
  >
    <VCard rounded="lg">
      <VCardTitle class="d-flex align-center justify-space-between pa-5 pb-3">
        <span class="text-h6 font-weight-bold">Specific SLoc (From &rarr; To)</span>
        <VBtn
          icon="ri-close-line"
          variant="text"
          size="small"
          color="medium-emphasis"
          @click="closeModal"
        />
      </VCardTitle>
      <VDivider />
      <VCardText class="pa-5">
        <div v-if="loading" class="d-flex align-center justify-center py-6">
          <VProgressCircular indeterminate color="primary" size="32" class="me-2" />
          <span class="text-caption text-medium-emphasis">Loading specific sloc...</span>
        </div>
        <div v-else class="d-flex flex-column gap-6">
          <div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-2">From Specific SLoc</div>
            <div class="text-body-2 font-weight-medium mb-2">{{ fromDesc || '-' }}</div>
            <div class="d-flex flex-wrap ga-1">
              <VChip
                v-for="tank in fromTanks"
                :key="tank.id_sloc_tail"
                size="small"
                color="primary"
                variant="flat"
              >
                {{ tank.tankName || tank.tf_number || tank.id_sloc_tail }}
              </VChip>
              <span v-if="fromTanks.length === 0" class="text-caption text-disabled">No specific sloc available</span>
            </div>
          </div>
          <VDivider />
          <div>
            <div class="text-caption font-weight-bold text-uppercase text-medium-emphasis mb-2">To Specific SLoc</div>
            <div class="text-body-2 font-weight-medium mb-2">{{ toDesc || '-' }}</div>
            <div class="d-flex flex-wrap ga-1">
              <VChip
                v-for="tank in toTanks"
                :key="tank.id_sloc_tail"
                size="small"
                color="secondary"
                variant="flat"
              >
                {{ tank.tankName || tank.tf_number || tank.id_sloc_tail }}
              </VChip>
              <span v-if="toTanks.length === 0" class="text-caption text-disabled">No specific sloc available</span>
            </div>
          </div>
        </div>
      </VCardText>
      <VDivider />
      <VCardActions class="pa-5 pt-3 justify-end">
        <VBtn color="secondary" variant="text" @click="closeModal">Close</VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import transferApi from '../services/index.js'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  fromSlocIds: { type: Array, default: () => [] },
  toSlocIds: { type: Array, default: () => [] },
  fromDesc: { type: String, default: '' },
  toDesc: { type: String, default: '' }
})

const emit = defineEmits(['update:isOpen'])

const loading = ref(false)
const fromTanks = ref([])
const toTanks = ref([])

function closeModal() {
  emit('update:isOpen', false)
}

async function fetchTanksForIds(ids) {
  const uniqueIds = [...new Set((ids || []).map(Number).filter(Boolean))]
  if (uniqueIds.length === 0) return []
  const results = await Promise.all(
    uniqueIds.map(id => transferApi.getSpecificTanksRundown({ sloc: id }))
  )
  const allowed = new Set(uniqueIds)
  const all = []
  const seen = new Set()
  for (const res of results) {
    const tanks = res?.data || []
    for (const t of tanks) {
      if (allowed.has(Number(t.id_sloc_tail)) && !seen.has(t.id_sloc_tail)) {
        seen.add(t.id_sloc_tail)
        all.push(t)
      }
    }
  }
  return all
}

async function bootstrap() {
  loading.value = true
  try {
    const [from, to] = await Promise.all([
      fetchTanksForIds(props.fromSlocIds),
      fetchTanksForIds(props.toSlocIds)
    ])
    fromTanks.value = from
    toTanks.value = to
  } catch (e) {
    fromTanks.value = []
    toTanks.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.isOpen, (open) => {
  if (open) bootstrap()
})
</script>
