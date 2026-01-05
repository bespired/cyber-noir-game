<script setup>
import ClickButton from './inputs/ClickButton.vue';

defineProps({
  isOpen: Boolean,
  title: String,
  okLabel: {
    type: String,
    default: 'OK'
  },
  cancelLabel: {
    type: String,
    default: 'Annuleren'
  },
  showFooter: {
    type: Boolean,
    default: true
  },
  okButtonType: {
    type: String,
    default: 'green'
  }
})

defineEmits(['close', 'ok'])
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-md">
    <div class="bg-noir-panel border border-noir-dark w-full max-w-lg rounded shadow-2xl overflow-hidden relative" @click.stop>
      <!-- Header -->
      <div class="flex justify-between items-center bg-noir-darker p-4 border-b border-noir-dark">
        <h3 class="text-xl font-bold text-white uppercase tracking-wider">{{ title }}</h3>
        <button @click="$emit('close')" class="text-noir-muted hover:text-white transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 max-h-[80vh] overflow-y-auto">
        <slot></slot>
      </div>

      <!-- Footer -->
      <div v-if="showFooter" class="bg-noir-darker p-4 border-t border-noir-dark flex justify-end gap-3">
        <ClickButton 
          :label="cancelLabel" 
          buttonType="black" 
          @click="$emit('close')" 
        />
        <ClickButton 
          :label="okLabel" 
          :buttonType="okButtonType" 
          @click="$emit('ok')" 
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
  /* Ensure the modal is always above other content */
  .z-\[100\] {
    z-index: 100;
  }
</style>
