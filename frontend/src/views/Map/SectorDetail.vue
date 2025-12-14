<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const route = useRoute();
const sector = ref(null);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get(`/api/sectoren/${route.params.id}`);
        sector.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        LOADING_SECTOR_DATA...
    </div>

    <div v-else-if="sector" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/map" class="hover:text-white">&lt; BACK_TO_MAP</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ sector.naam }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-noir-dark bg-noir-dark/50">
                <h1 class="text-3xl font-bold text-white mb-2">{{ sector.naam }}</h1>
                <p class="text-noir-text">{{ sector.beschrijving }}</p>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-white uppercase tracking-wider">Locations in Sector</h2>
            <button class="bg-noir-warning text-black px-4 py-2 rounded hover:bg-yellow-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + ADD LOCATION
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="locatie in sector.locaties" :key="locatie.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-colors group">
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors">{{ locatie.naam }}</h3>
                <p class="text-noir-text text-sm mb-4 line-clamp-2">{{ locatie.beschrijving }}</p>
                
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <span class="text-xs text-noir-muted">ID: {{ String(locatie.id).padStart(4, '0') }}</span>
                    <RouterLink :to="`/locaties/${locatie.id}`" class="text-noir-warning text-sm hover:text-white hover:underline decoration-noir-warning underline-offset-4 uppercase font-semibold transition-all">
                        INSPECT >
                    </RouterLink>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="!sector.locaties || sector.locaties.length === 0" class="col-span-full text-center py-12 border-2 border-dashed border-noir-dark rounded text-noir-muted">
                NO_LOCATIONS_DETECTED
            </div>
        </div>
    </div>
</template>
