<script setup>

const props = defineProps({
    label: {
        type: String,
        default: ''
    },
    options: {
        type: Object,
        default: ''
    }
});

const emit = defineEmits(['click']);

const handleClick = (event) => {
    emit('click', event);
};
</script>

<template>
    <select v-model="selectedOption" class="option-list">
        <option value="">{{ t('locations.all_sectors') }}</option>
        <option v-for="option in options" :key="option.id" :value="option.id">
            {{ option.naam }}
        </option>
    </select>
    <click-button icon="✕" buttonType="black"
        v-if="selectedOption" @click="selectedOption = ''"
    />

</template>


<style>
    .option-list {

    }
</style>
