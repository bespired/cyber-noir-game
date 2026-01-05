<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from '../inputs/LinkButton.vue';
import ThumbFooter from '../bars/ThumbFooter.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    sector: {
        type: Object,
        required: true
    },
    scene: {
        type: Object,
        required: true
    },
});

const { t } = useI18n();
</script>
<template>
    <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-colors group">

         {{ scene.type }}
        <h3 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors">{{ scene.titel }}</h3>
        <p class="text-noir-text text-sm mb-4 line-clamp-2">{{ scene.beschrijving }}</p>

        <thumb-footer
            name     = "scene"
            :itemId  = "scene.id"
            :edit    = "true"
            :three   = "sector?.id"
        />


    </div>
</template>