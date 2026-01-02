<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from '../inputs/LinkButton.vue';
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
    <div class="panel group hover:border-noir-accent transition-all duration-300">
        <div class="p-4 border-b border-noir-dark flex justify-between items-center bg-noir-dark/30">
            <h2 class="text-white font-bold tracking-widest uppercase truncate">{{ gedrag.naam }}</h2>
            <span class="text-[10px] text-noir-muted font-mono">ID: {{ String(gedrag.id).padStart(4, '0') }}</span>
        </div>
        <div class="p-4">
            <p class="text-xs text-noir-text mb-4 line-clamp-2 min-h-[32px]">
                {{ gedrag.beschrijving || 'GEEN_BESCHRIJVING_BESCHIKBAAR' }}
            </p>
            <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                <span class="text-[10px] text-noir-accent uppercase tracking-tighter">
                    {{ gedrag.acties?.length || 0 }} ACTIONS LOADED
                </span>
                <LinkButton
                    name="gedrag-detail"
                    :params="{ id: gedrag.id }"
                    :label="t('common.change')"
                    buttonType="link"
                />
            </div>
        </div>
    </div>
</template>
