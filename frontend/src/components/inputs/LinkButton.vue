<script setup>
import { RouterLink } from 'vue-router';
import ClickButton from './ClickButton.vue';

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    label: {
        type: String,
        required: true
    },
    buttonType: {
        type: String,
        default: 'green'
    },
    icon: {
        type: String,
        default: null
    },
    params: {
        type: Object,
        default: () => ({})
    },
    query: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <RouterLink :to="{ name, params, query }" class="link-button">
        <ClickButton :label="label" :buttonType="buttonType" :icon="icon" />
    </RouterLink>
</template>

<style>
    .click-button ~ .link-button,
    .link-button ~ .click-button,
    .link-button ~ .link-button {
        margin-left: 10px;
    }

</style>