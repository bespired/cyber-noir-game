<script setup>
import { RouterLink } from 'vue-router';
import LinkButton  from '../inputs/LinkButton.vue';
import ThumbFooter from '../bars/ThumbFooter.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    gedrag: {
        type: Object,
        required: true
    }
});

const { t } = useI18n();
</script>

<template>
    <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group ">

        <div class="flex justify-between items-start mb-2">
            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ gedrag.naam }}</h2>
        </div>

        <span class="flex text-noir-muted text-xs font-mono ">
            {{ gedrag.acties?.length || 0 }} {{ t('behavior.actions_loaded') }}
        </span>


        <p class="text-xs text-noir-text mb-4 line-clamp-2 min-h-[32px]">
            {{ gedrag.beschrijving || t('common.no_description') }}
        </p>

        <thumb-footer
            name     ="gedrag"
            :itemId  ="gedrag.id"
            :edit    ="true"
        >

        </thumb-footer>

    </div>
</template>
