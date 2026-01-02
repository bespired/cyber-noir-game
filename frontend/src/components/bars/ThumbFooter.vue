<script setup>
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n }  from 'vue-i18n';
import LinkButton from '../inputs/LinkButton.vue';

const store = useStore();
const { t } = useI18n();

const props = defineProps({

    name: {
        type: String,
        default: null
    },

    itemId: {
        type: Number,
        default: null
    },

    edit: {
        type: Boolean,
        default: false
    },

    emulate: {
        type: Boolean,
        default: false
    },

    three: {
        type: [Number,String],
        default: null
    },

});

</script>

<template>
    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
        <span class="flex text-nowrap text-noir-muted text-xs font-mono">
            ID: {{ itemId }}
        </span>
        <span class="ml-auto">
            <LinkButton
                v-if="three"
                :name="`${name}-3d`"
                :params="{ id: itemId, sectorId: three }"
                label="3D"
                buttonType="blue"
            />
            <LinkButton
                v-if="emulate"
                :name="`${name}-emulate`"
                :params="{ id: itemId }"
                :label="t('common.emulate')"
                buttonType="green"
            />
            <LinkButton
                v-if="edit"
                :name="`${name}-detail`"
                :params="{ id: itemId }"
                :label="t('common.change')"
                buttonType="link"
            />

        </span>
    </div>
</template>

<style scoped>

</style>
