<script setup>
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const store = useStore();
const { t } = useI18n();

const props = defineProps({
    label: {
        type: String,
        default: ''
    }
});

</script>

<template>
   <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div class="flex items-center gap-4">
            <h1 class="page-header">{{ label }}</h1>

            <div class="flex items-center gap-2">
                <slot name="filters"></slot>
            </div>
        </div>

        <div class="flex gap-4 items-center">
            <slot name="actions"></slot>
        </div>
    </div>
</template>

<style scoped>
.page-header {
    white-space: nowrap;
}
</style>
