<script setup>
import { computed } from 'vue';
import ClickButton from './ClickButton.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Select option'
    }
});

const emit = defineEmits(['update:modelValue']);

const selectedOption = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});

</script>

<template>
    <div class="flex items-center gap-2">
        <select v-model="selectedOption" class="form-input text-sm w-auto uppercase">
            <option value="">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.id || option.value" :value="option.id || option.value">
                {{ option.naam || option.label || option.text }}
            </option>
        </select>
        <click-button 
            v-if="selectedOption" 
            icon="✕" 
            buttonType="black"
            @click="selectedOption = ''"
        />
    </div>
</template>

<style scoped>
/* Scoped styles if needed, but mostly using global noir classes */
</style>
