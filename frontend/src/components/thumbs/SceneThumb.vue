<script setup>
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import LinkButton from '../inputs/LinkButton.vue';
import ThumbFooter from '../bars/ThumbFooter.vue';

defineProps({
    scene: {
        type: Object,
        required: true
    }
});

const getStatusColor = (status) => {
    switch (status) {
        case 'active':    return 'bg-noir-success';
        case 'completed': return 'bg-noir-muted';
        case 'locked':    return 'bg-noir-danger';
        default: return 'bg-noir-muted';
    }
};

const getSvgIcon = (type) => {
    switch (type) {
        case 'walkable-area': return 'behavior.svg';
        case 'interrogation': return 'about.svg';
        case 'combat':        return 'combat.svg';
        case 'investigation': return 'clue.svg';
        case 'practice':      return 'target.svg';
        case 'vue-component': return 'vue.svg';
        default: return 'cog';
    }
};

const { t } = useI18n();
</script>

<template>
    <div class="panel panel--hover group">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-grow">
                <div class="flex items-center gap-2 mb-1">
                    <img :src="`/icons/${getSvgIcon(scene.type)}`" class="w-8"/>
                    <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ scene.titel }}</h2>
                </div>
                <span class="text-xs text-noir-muted uppercase tracking-wider">
                {{ scene.type }}</span>
            </div>
            <div :class="['h-2 w-2 rounded-full', getStatusColor(scene.status)]"></div>
        </div>

        <p class="text-noir-text text-sm mb-4 line-clamp-3">{{ scene.beschrijving }}</p>

        <!-- Location Info -->
        <div v-if="scene.locatie" class="mb-4 flex justify-between items-center p-3 bg-noir-dark/30 rounded border border-noir-dark">
            <div>
                <div class="text-[9px] text-noir-muted uppercase mb-1">Location</div>
                <div class="text-sm text-white font-semibold">{{ scene.locatie.naam }}</div>
            </div>
            <div v-if="scene.sector" class="text-right">
                <div class="text-[9px] text-noir-muted uppercase mb-1">Sector</div>
                <div class="text-xs text-noir-accent">{{ scene.sector.naam }}</div>
            </div>
        </div>

        <!-- Attached NPCs & Behaviors -->
        <div v-if="scene.scene_personages && scene.scene_personages.length > 0" class="mb-4">
            <div class="text-[9px] text-noir-muted uppercase mb-2 tracking-widest px-1">GAMEPLAY</div>
            <div class="flex flex-col gap-1.5">
                <div
                    v-for="sp in scene.scene_personages"
                    :key="sp.id"
                    class="flex items-center justify-between p-2 bg-noir-dark/50 rounded border border-noir-dark/50 hover:border-noir-accent/30 transition-colors"
                >
                    <div class="flex items-center gap-2 overflow-hidden">
                        <span class="text-sm shrink-0">
                            <img src="/icons/vehicle.svg"   class="h-[20px]" v-if="sp.personage?.type === 'voertuig'">
                            <img src="/icons/personage.svg" class="h-[20px]" v-else>
                        </span>
                        <span class="text-[10px] text-white font-bold uppercase truncate">{{ sp.personage?.naam }}</span>
                    </div>
                    <div v-if="sp.gedrag" class="flex items-center gap-1 shrink-0">
                        <span class="text-[8px] text-noir-muted">
                            <img src="/icons/behavior.svg" class="h-[20px]">
                        </span>
                        <span class="text-[9px] text-noir-accent font-mono uppercase">{{ sp.gedrag.naam }}</span>
                    </div>
                </div>
            </div>
        </div>

        <thumb-footer
            name     ="scene"
            :itemId  ="scene.id"
            :edit    ="true"
        />

    </div>
</template>
