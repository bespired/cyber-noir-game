<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from '../inputs/LinkButton.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    clue: {
        type: Object,
        required: true
    }
});

const { t } = useI18n();

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};
</script>

<template>
    <tr class="hover:bg-noir-dark/50 transition-colors group">
        <td class="p-4">
            <div v-if="clue.artwork && clue.artwork.length > 0" class="w-12 h-12">
                <img
                    :src="getImageUrl(clue.artwork[0].bestandspad)"
                    :alt="clue.titel"
                    class="w-full h-full aspect-square object-cover rounded border border-noir-dark"
                >
            </div>
            <div v-else class="w-12 h-12 bg-noir-dark rounded border border-noir-dark/50 flex items-center justify-center">
                <span class="text-xs text-noir-muted">N/A</span>
            </div>
        </td>
        <td class="p-4">
            <span v-if="clue.is_kritisch" class="badge badge--locked">KRITIEK</span>
            <span v-else class="badge badge--neutral">STANDAARD</span>
        </td>
        <td class="p-4 font-bold text-white group-hover:text-noir-accent transition-colors">{{ clue.titel }}</td>
        <td class="p-4 text-sm text-noir-text">
            <span v-if="clue.personage" class="text-noir-accent">{{ clue.personage.naam }}</span>
            <span v-else class="text-noir-muted italic">--</span>
        </td>
        <td class="p-4 text-sm text-noir-text">
            <span v-if="clue.locatie" class="text-noir-warning">{{ clue.locatie.naam }}</span>
            <span v-else class="text-noir-muted italic">--</span>
        </td>
        <td class="p-4 text-right">
            <LinkButton 
                name="aanwijzing-detail" 
                :params="{ id: clue.id }" 
                :label="t('common.change')" 
                buttonType="link" 
            />
        </td>
    </tr>
</template>
