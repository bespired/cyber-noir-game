<script setup>
import { RouterLink } from 'vue-router';

defineProps({
    conv: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group">
        <div class="flex justify-between items-start mb-2">
            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ conv.titel }}</h2>
            <span v-if="conv.personage" class="text-xs text-noir-muted bg-noir-dark px-2 py-1 rounded border border-noir-dark">
                {{ conv.personage.naam }}
            </span>
        </div>

        <p class="text-noir-muted text-xs font-mono mb-4">ID: {{ conv.id }}</p>

        <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
            <span class="text-xs text-noir-muted truncate max-w-[100px]">{{ conv.is_active ? 'ACTIEF' : 'INACTIEF' }}</span>
            <div class="flex flex-col gap-2 items-end">
                <RouterLink :to="`/dialogen/${conv.id}`" class="btn--link">
                    EDIT NODE LOGICA >
                </RouterLink>
                <RouterLink :to="`/dialogen/${conv.id}/emulate`" class="btn--link text-noir-accent">
                    EMULATE DIALOOG >
                </RouterLink>
            </div>
        </div>
    </div>
</template>
