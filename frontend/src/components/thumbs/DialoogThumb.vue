<script setup>
import { RouterLink } from 'vue-router';
import LinkButton  from '../inputs/LinkButton.vue';
import ThumbFooter from '../bars/ThumbFooter.vue';
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
    <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group ">

        <div class="flex justify-between items-start mb-2">

            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ conv.titel }}</h2>
            <span v-if="conv.personage" class="text-xs text-noir-muted bg-noir-dark px-2 py-1 rounded border border-noir-dark">
                {{ conv.personage.naam }}
            </span>
        </div>

        <thumb-footer
            name     ="dialoog"
            :itemId  ="conv.id"
            :emulate ="true"
            :edit    ="true"
        />

        <!--

                <button
                    @click="$emit('delete', conv.id)"
                    class="btn btn--danger btn--small py-1 px-3 text-[10px]"
                >
                    {{ t('common.delete') }}
                </button>


                <RouterLink :to="`/dialogen/${conv.id}/emulate`" class="btn--link text-noir-accent">
                    EMULATE DIALOOG >
                </RouterLink>
            </div>
        </div>-->
    </div>
</template>
