<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from "../inputs/LinkButton.vue";
import ThumbFooter from '../bars/ThumbFooter.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    personage: {
        type: Object,
        required: true
    },
    type: {
        type: String,
        default: 'persoon'
    }
});

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

const { t } = useI18n();
</script>

<template>
    <div :class="[
             'panel panel--hover group relative overflow-hidden flex',
             type === 'voertuig' ? 'flex-col' : 'gap-4'
         ]">
        <!-- Playable Badge -->
        <div v-if="personage.is_playable" class="absolute -right-6 top-4 bg-noir-accent text-black text-[10px] font-bold px-8 py-1 rotate-45 border-y border-white z-10 shadow-lg">
            PLAYABLE
        </div>

        <!-- Thumbnail (Vehicle Mode: Top, full width) -->
        <div v-if="type === 'voertuig'" class="mb-4">
            <img v-if="personage.artwork && personage.artwork.length > 0"
                :src="getImageUrl(personage.artwork[0].bestandspad)"
                :alt="personage.naam"
                class="w-full aspect-video object-cover rounded border border-noir-dark opacity-80 group-hover:opacity-100 transition-opacity"
            >
            <div v-else class="aspect-video bg-noir-darker rounded border border-noir-dark flex items-center justify-center text-noir-muted text-xs uppercase tracking-widest">
                NO_VISUAL_DATA
            </div>
        </div>

        <!-- Thumbnail (Personage Mode: Left, fixed width) -->
        <div v-else-if="personage.artwork && personage.artwork.length > 0" class="flex-shrink-0 w-30">
            <img
                :src="getImageUrl(personage.artwork[0].bestandspad)"
                :alt="personage.naam"
                class="w-full aspect-[2/3] object-cover rounded border border-noir-dark"
            >
        </div>

        <div class="flex-grow flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h2 class="text-xl font-bold text-white transition-colors leading-tight"
                        :class="type === 'voertuig' ? 'group-hover:text-noir-warning' : 'group-hover:text-noir-accent'">
                        {{ personage.naam }}
                    </h2>
                    <span class="text-xs text-noir-muted uppercase tracking-wider">{{ personage.rol }}</span>
                </div>
                <div class="h-2 w-2 rounded-full"
                     v-if="type === 'persoon'"
                     :class="personage.is_replicant ? 'bg-noir-danger' : 'bg-noir-success'"
                     :title="personage.is_replicant ? 'Replicant Gedetecteerd' : 'Mens Geverifieerd'">
                </div>
            </div>

            <p class="text-noir-text text-sm mb-4 line-clamp-3 flex-grow">{{ personage.beschrijving }}</p>

            <thumb-footer
                name     ="personage"
                :itemId  ="personage.id"
                :edit    ="true"
            />

            <!-- <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                <span class="text-xs text-noir-muted font-mono">
                    {{ type === 'voertuig' ? 'VEH_ID' : 'ID' }}: {{ String(personage.id).padStart(4, '0') }}
                </span>
                <link-button
                    name="personage-detail"
                    :params="{id: personage.id}"
                    :label="t('common.change')"
                    buttonType="link"
                />
            </div> -->
        </div>
    </div>
</template>
