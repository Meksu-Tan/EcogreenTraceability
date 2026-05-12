<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue" class="modal-overlay" @mousedown.self="$emit('update:modelValue', false)">
        <div class="modal-box" :style="{ maxWidth }">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button
              style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:18px;line-height:1;"
              @click="$emit('update:modelValue', false)"
            >
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="modal-body">
            <slot />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="$emit('update:modelValue', false)">
              <i class="fas fa-times"></i> Batal
            </button>
            <button class="btn btn-primary" :disabled="loading" @click="$emit('submit')">
              <i v-if="loading" class="fas fa-circle-notch spinner"></i>
              <i v-else class="fas fa-save"></i>
              {{ loading ? 'Menyimpan...' : submitLabel }}
            </button>
          </div>
        </div>
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
