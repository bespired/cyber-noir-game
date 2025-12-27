<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import axios from '../../axios';
import { useToast } from '../../composables/useToast';

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
            <button @click="createGedrag" class="btn btn--success">
                + NIEUW_GEDRAG
            </button>
        </div>

        <div v-if="loading" class="text-center py-20 text-noir-muted animate-pulse">
            SCANNING_NEURAL_PATTERNS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="gedrag in gedragingen" :key="gedrag.id" class="panel group hover:border-noir-accent transition-all duration-300">
                <div class="p-4 border-b border-noir-dark flex justify-between items-center bg-noir-dark/30">
                    <h2 class="text-white font-bold tracking-widest uppercase truncate">{{ gedrag.naam }}</h2>
                    <span class="text-[10px] text-noir-muted font-mono">ID: {{ String(gedrag.id).padStart(4, '0') }}</span>
                </div>
                <div class="p-4">
                    <p class="text-xs text-noir-text mb-4 line-clamp-2 min-h-[32px]">
                        {{ gedrag.beschrijving || 'GEEN_BESCHRIJVING_BESCHIKBAAR' }}
                    </p>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                        <span class="text-[10px] text-noir-accent uppercase tracking-tighter">
                            {{ gedrag.acties?.length || 0 }} ACTIONS LOADED
                        </span>
                        <RouterLink :to="`/gedrag/${gedrag.id}`" class="btn btn--small btn--primary">
                            BEWERKEN
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
