<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const sectors = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">SECTOR MAP</h1>
            <button class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW SECTOR
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_MAP_DATA...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="sector in sectors" :key="sector.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group relative overflow-hidden">
                <!-- "Map" Background Effect -->
                <div class="absolute inset-0 opacity-10 pointer-events-none">
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/diagmonds-light.png')]"></div>
                </div>

                <div class="relative z-10">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-2xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ sector.naam }}</h2>
                        <span v-if="sector.is_ontdekt" class="px-2 py-1 bg-noir-success/20 text-noir-success border border-noir-success rounded text-xs font-bold uppercase">DISCOVERED</span>
                        <span v-else class="px-2 py-1 bg-noir-muted/20 text-noir-muted border border-noir-muted rounded text-xs font-bold uppercase">UNKNOWN</span>
                    </div>
                    
                    <p class="text-noir-text text-sm mb-6">{{ sector.beschrijving }}</p>

                    <!-- Locations Preview -->
                    <div class="mb-6">
                        <h3 class="text-xs font-bold text-noir-muted uppercase mb-2">Key Locations</h3>
                        <ul v-if="sector.locaties && sector.locaties.length > 0" class="space-y-1">
                            <li v-for="loc in sector.locaties.slice(0, 3)" :key="loc.id" class="text-sm text-white flex items-center">
                                <span class="w-1.5 h-1.5 bg-noir-warning rounded-full mr-2"></span>
                                {{ loc.naam }}
                            </li>
                            <li v-if="sector.locaties.length > 3" class="text-xs text-noir-muted italic pl-3.5">
                                + {{ sector.locaties.length - 3 }} more...
                            </li>
                        </ul>
                        <div v-else class="text-xs text-noir-muted italic">No locations mapped.</div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                        <span class="text-xs text-noir-muted font-mono">COORDS: {{ sector.kaart_coordinaten || 'UNKNOWN' }}</span>
                        <RouterLink :to="`/map/${sector.id}`" class="text-noir-accent text-sm hover:text-white hover:underline decoration-noir-accent underline-offset-4 uppercase font-semibold transition-all">
                            ENTER SECTOR >
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
