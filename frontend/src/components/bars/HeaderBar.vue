<script setup>
import { useStore } from 'vuex';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const store = useStore();
const { t } = useI18n();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);
const user = computed(() => store.getters['auth/user']);

const handleLogout = () => {
    store.dispatch('auth/logout');
};

const props = defineProps({
    label: {
        type: String,
        default: ''
    },
});

</script>

<template>
   <div class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-4">
            <h1 class="page-header">{{ label }}</h1>
            <!-- <div class="flex items-center gap-2">
                <select v-model="selectedSector" class="form-input text-sm w-auto uppercase">
                    <option value="">{{ t('locations.all_sectors') }}</option>
                    <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                        {{ sector.naam }}
                    </option>
                </select>
                <click-button v-if="selectedSector" icon="✕" buttonType="black" @click="selectedSector = ''" />
            </div> -->
        </div>
        <div class="flex gap-4">
            <link-button :label="t('locations.order')" icon="⇅" name="locaties-reorder" buttonType="blue" />
            <click-button :label="t('locations.new_location')" icon="+" buttonType="add" @click="openModal"  />
        </div>
    </div>
</template>
