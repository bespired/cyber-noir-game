<script setup>
import { RouterLink } from 'vue-router';
import LinkButton from '../inputs/LinkButton.vue';
import { useI18n } from 'vue-i18n';

defineProps({
    conv: {
        type: Object,
        required: true
    }
});

defineEmits(['delete']);

const { t } = useI18n();
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
                <LinkButton
                    name="dialoog-edit"
                    :params="{ id: conv.id }"
                    :label="t('common.change')"
                    buttonType="link"
                />

                <LinkButton
                    name="dialoog-emulate"
                    :params="{ id: conv.id }"
                    :label="t('common.emulate')"
                    buttonType="blue"
                />

                <button 
                    @click="$emit('delete', conv.id)" 
                    class="btn btn--danger btn--small py-1 px-3 text-[10px]"
                >
                    {{ t('common.delete') }}
                </button>


                <!-- <RouterLink :to="`/dialogen/${conv.id}/emulate`" class="btn--link text-noir-accent">
                    EMULATE DIALOOG >
                </RouterLink> -->
            </div>
        </div>
    </div>
</template>
