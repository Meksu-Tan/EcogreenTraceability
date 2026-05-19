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
      <div v-if="modelValue" class="fixed inset-0 z-[1050] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @mousedown.self="$emit('update:modelValue', false)">
        <Transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
          appear
        >
          <div 
            class="bg-white rounded-xl shadow-2xl w-full flex flex-col max-h-[90vh] overflow-hidden border border-gray-100" 
            :style="{ maxWidth }"
          >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h5 class="text-base font-bold text-slate-800 tracking-tight">{{ title }}</h5>
              <button
                class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all"
                @click="$emit('update:modelValue', false)"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 overflow-y-auto flex-1">
              <slot />
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex justify-end gap-3">
              <button 
                class="px-4 py-2 rounded-md text-sm font-bold text-slate-600 hover:bg-slate-200 hover:text-slate-800 transition-all flex items-center gap-2" 
                @click="$emit('update:modelValue', false)"
              >
                Batal
              </button>
              <button 
                class="px-5 py-2 rounded-md text-sm font-bold text-white bg-green-600 hover:bg-green-700 shadow-md shadow-green-200 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95" 
                :disabled="loading" 
                @click="$emit('submit')"
              >
                <i v-if="loading" class="fas fa-circle-notch animate-spin"></i>
                <i v-else class="fas fa-save"></i>
                {{ loading ? 'Saving...' : submitLabel }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  modelValue:  { type: Boolean, default: false },
  title:       { type: String, default: 'Modal' },
  submitLabel: { type: String, default: 'Simpan' },
  loading:     { type: Boolean, default: false },
  maxWidth:    { type: String, default: '520px' },
})
defineEmits(['update:modelValue', 'submit'])
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
