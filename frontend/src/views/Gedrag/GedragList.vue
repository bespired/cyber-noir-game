<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '../../axios';
import { useToast } from '../../composables/useToast';
import ClickButton from '../../components/inputs/ClickButton.vue';
import GedragThumb from '../../components/thumbs/GedragThumb.vue';
import { useI18n } from 'vue-i18n';
import HeaderBar from '../../components/bars/HeaderBar.vue';

const { t } = useI18n();
const toast = useToast();
const gedragingen = ref([]);
const loading = ref(true);

const fetchGedragingen = async () => {
    try {
        const response = await axios.get('/api/gedrag');
        gedragingen.value = response.data;
    } catch (e) {
        toast.error(t('behavior.fetch_error'));
    } finally {
        loading.value = false;
    }
};

const createGedrag = async () => {
    const naam = prompt(t('behavior.name_prompt'));
    if (!naam) return;

    try {
        const response = await axios.post('/api/gedrag', {
            naam,
            acties: []
        });
        gedragingen.value.push(response.data);
        toast.success(t('behavior.created'));
    } catch (e) {
        toast.error(t('behavior.create_error'));
    }
};

onMounted(fetchGedragingen);
</script>

<template>
    <div class="container mx-auto p-6">
        <header-bar :label="t('behavior.title')">
            <template #actions>
                <click-button :label="t('behavior.new')" icon="+" buttonType="add" @click="createGedrag" />
            </template>
        </header-bar>

        <div v-if="loading" class="text-center py-20 text-noir-muted animate-pulse">
            {{ t('behavior.scanning') }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <GedragThumb v-for="gedrag in gedragingen" :key="gedrag.id" :gedrag="gedrag" />
        </div>
    </div>
</template>
