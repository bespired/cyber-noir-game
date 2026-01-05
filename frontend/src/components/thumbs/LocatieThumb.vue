<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from '../inputs/LinkButton.vue';
import ThumbFooter from '../bars/ThumbFooter.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    locatie: {
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
    <div class="panel panel--hover group flex flex-col relative overflow-hidden">
        <!-- Thumbnail -->
        <div v-if="locatie.artwork && locatie.artwork.length > 0" class="mb-4">
            <RouterLink
                v-if="locatie.has_glb"
                :to="`/locaties/${locatie.id}/sector/${locatie.glb_sector_id}/3d`"
                class="block group/thumb relative"
            >
                <img
                    :src="getImageUrl(locatie.artwork[0].bestandspad)"
                    :alt="locatie.naam"
                    class="w-full aspect-video object-cover rounded border border-noir-dark opacity-80 group-hover:opacity-100 transition-opacity"
                >
                <div class="absolute inset-0 bg-noir-accent/20 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center rounded">
                    <span class="btn btn--accent btn--small">VIEW_3D</span>
                </div>
            </RouterLink>
            <img
                v-else
                :src="getImageUrl(locatie.artwork[0].bestandspad)"
                :alt="locatie.naam"
                class="w-full aspect-video object-cover rounded border border-noir-dark opacity-80 group-hover:opacity-100 transition-opacity"
            >
        </div>
        <div v-else class="mb-4 aspect-video bg-noir-darker rounded border border-noir-dark flex items-center justify-center text-noir-muted text-xs uppercase tracking-widest">
            NO_VISUAL_DATA
        </div>

        <h2 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors leading-tight">{{ locatie.naam }}</h2>

        <p class="text-noir-text text-sm mb-4 line-clamp-3 flex-grow">{{ locatie.beschrijving }}</p>

        <thumb-footer
            name     ="locatie"
            :itemId  ="locatie.id"
            :edit    ="true"
            :three   ="`${locatie.has_glb ? locatie.glb_sector_id : null}`"
        />

        <!-- <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
            <div class="flex items-center gap-2">
                <span class="text-xs text-noir-muted font-mono">LOC_ID: {{ String(locatie.id).padStart(4, '0') }}</span>
                <RouterLink
                    v-if="locatie.has_glb"
                    :to="`/locaties/${locatie.id}/sector/${locatie.glb_sector_id}/3d`"
                    class="badge badge--accent text-[10px] py-0.5 px-1.5 hover:bg-noir-accent hover:text-black transition-colors"
                    title="VIEW_3D_SCENE"
                >
                    3D_VIEW
                </RouterLink>
            </div>
            <LinkButton
                name="locatie-detail"
                :params="{ id: locatie.id }"
                :label="t('common.change')"
                buttonType="link"
            />
        </div> -->
    </div>
</template>
