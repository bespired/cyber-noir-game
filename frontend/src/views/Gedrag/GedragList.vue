<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '../../axios';
import { useToast } from '../../composables/useToast';
import ClickButton from '../../components/inputs/ClickButton.vue';
import GedragThumb from '../../components/thumbs/GedragThumb.vue';

const toast = useToast();
const gedragingen = ref([]);
const loading = ref(true);

const fetchGedragingen = async () => {
    try {
        const response = await axios.get('/api/gedrag');
        gedragingen.value = response.data;
    } catch (e) {
        toast.error('FAILED_TO_FETCH_BEHAVIORS');
    } finally {
        loading.value = false;
    }
};

const createGedrag = async () => {
    const naam = prompt('BEHAVIOR_NAME:');
    if (!naam) return;

    try {
        const response = await axios.post('/api/gedrag', {
            naam,
            acties: []
        });
        gedragingen.value.push(response.data);
        toast.success('BEHAVIOR_CREATED');
    } catch (e) {
        toast.error('FAILED_TO_CREATE_BEHAVIOR');
    }
};

onMounted(fetchGedragingen);
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="page-header">GEDRAG</h1>
            <click-button label="NIEUW GEDRAG" icon="+" buttonType="add" @click="createGedrag" />
        </div>

        <div v-if="loading" class="text-center py-20 text-noir-muted animate-pulse">
            SCANNING_NEURAL_PATTERNS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <GedragThumb v-for="gedrag in gedragingen" :key="gedrag.id" :gedrag="gedrag" />
        </div>
    </div>
</template>
