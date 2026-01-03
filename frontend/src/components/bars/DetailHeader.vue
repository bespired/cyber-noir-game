<script setup>
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n }  from 'vue-i18n';
import LinkButton   from '../inputs/LinkButton.vue';
import ClickButton  from '../inputs/ClickButton.vue';

const store = useStore();
const { t } = useI18n();

const props = defineProps({

    backLink: {
        type: String,
        default: null
    },

    backlabel: {
        type: String,
        default: null
    },

    name: {
        type: String,
        default: null
    },

    label: {
        type: String,
        default: null
    },

    save: {
        type: Boolean,
        default: false
    },

    remove: {
        type: Boolean,
        default: false
    },

    three: {
        type: [Object],
        default: null
    },

    emulate: {
        type: [Number,String],
        default: null
    },

    gateway: {
        type: [Number,String],
        default: null
    },
});

const emit = defineEmits(['save','remove']);

</script>

<template>

    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center text-sm text-noir-muted uppercase tracking-widest">
            <LinkButton
                :name ="backLink"
                :label="backlabel"
                icon="←"
                buttonType="back"
            />
            <span class="mx-2">/</span>
            <span class="text-white font-bold">{{ label }}</span>
        </div>
        <div>

        <LinkButton v-if="emulate"
            :name="`${name}-emulate`"
            :params="{id: emulate}"
            :label="t('scenes.test_component')"
            buttonType="blue"
            icon="ƈ"
        />
        <LinkButton v-if="gateway"
            name="sector-map"
            :params="{ id: gateway }"
            label="Gateway"
            buttonType="blue"
            icon="Ɗ"
        />
        <LinkButton v-if="three"
            :name="`locatie-3d`"
            :params="{ id: three.location, sectorId: three.sector }"
            label="3D"
            icon="Ɖ"
            buttonType="blue"
        />
        <ClickButton v-if="save"
            :label="t('common.save')"
            buttonType="green"
            @click="emit('save')"
        />
        <ClickButton v-if="remove"
            :label="t('common.delete')"
            buttonType="red"
            @click="emit('remove')"
        />
        </div>
    </div>


</template>

<style scoped>

</style>
