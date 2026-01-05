<script setup>
import ClickButton from '../inputs/ClickButton.vue';

defineProps({
    note: {
        type: Object,
        required: true
    }
});

defineEmits(['toggle', 'delete']);
</script>

<template>
    <div :class="['panel group relative overflow-hidden',
                  note.is_afgerond ? 'border-noir-success/50 opacity-75' : 'panel--hover']">

        <div class="flex justify-between items-start mb-4 relative z-10">
            <h2 :class="['text-xl font-bold transition-colors', note.is_afgerond ? 'text-noir-success line-through decoration-2' : 'text-white group-hover:text-noir-accent']">
                {{ note.titel }}
            </h2>
            <div class="flex">
                <click-button icon="✓" :buttonType="`${note.is_afgerond?'done':'green'}`" @click="$emit('toggle', note)" />
                <click-button icon="🗑️" buttonType="red" @click="$emit('delete', note.id)" />
            </div>
        </div>

        <p :class="['text-sm mb-4 whitespace-pre-wrap max-h-[7.5rem] overflow-y-auto noir-scrollbar', note.is_afgerond ? 'text-noir-muted' : 'text-noir-text']">{{ note.inhoud }}</p>

        <div class="text-xs text-noir-muted border-t border-noir-dark pt-4 mt-auto flex justify-between">
            <span>{{ new Date(note.created_at).toLocaleDateString() }}</span>
            <span>{{ note.is_afgerond ? 'ARCHIEF' : 'ACTIEF' }}</span>
        </div>
    </div>
</template>
