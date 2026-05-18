<template>
  <Teleport to="body">
    <div
      v-show="open"
      class="fixed inset-0 z-[100] overflow-y-auto"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="fixed inset-0 z-[1] bg-white/[0.14] backdrop-blur-2xl"
        aria-hidden="true"
        @click="$emit('close')"
      />
      <div class="relative z-[2] flex min-h-full items-center justify-center py-10 px-4">
        <div
          class="flex w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5"
          :class="wide ? 'max-w-2xl' : 'max-w-lg'"
        >
          <div class="flex shrink-0 items-center justify-between gap-4 bg-gradient-to-r from-green-600 to-green-600 px-6 py-4">
            <div class="min-w-0">
              <p class="text-[10px] font-bold uppercase tracking-widest text-green-100/90">{{ subtitle }}</p>
              <h3 class="truncate text-lg font-bold text-white">{{ title }}</h3>
            </div>
            <button
              type="button"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white hover:bg-white/25"
              @click="$emit('close')"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div v-if="$slots.alert" class="border-b bg-amber-50 px-6 py-2 text-xs text-amber-900">
            <slot name="alert" />
          </div>
          <div class="max-h-[min(75vh,720px)] overflow-y-auto p-6">
            <slot />
          </div>
          <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: 'WIP Entry' },
  subtitle: { type: String, default: 'WIP Transaction' },
  wide: { type: Boolean, default: false },
})
defineEmits(['close'])
</script>
