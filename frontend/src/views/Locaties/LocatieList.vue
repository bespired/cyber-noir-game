<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const locaties = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/locaties');
        locaties.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">LOCATIES</h1>
            <button class="bg-noir-warning text-black px-4 py-2 rounded hover:bg-yellow-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW LOCATION
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            SCANNING_SECTORS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="locatie in locaties" :key="locatie.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-colors group flex flex-col">
                <!-- Thumbnail -->
                <div v-if="locatie.artwork && locatie.artwork.length > 0" class="mb-4">
                    <img 
                        :src="getImageUrl(locatie.artwork[0].bestandspad)" 
                        :alt="locatie.naam" 
                        class="w-full aspect-video object-cover rounded border border-noir-dark"
                    >
                </div>
                
                <h2 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors">{{ locatie.naam }}</h2>
                
                <p class="text-noir-text text-sm mb-4 line-clamp-3 flex-grow">{{ locatie.beschrijving }}</p>
                
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <span class="text-xs text-noir-muted">SECTOR_ID: {{ String(locatie.id).padStart(4, '0') }}</span>
                    <RouterLink :to="`/locaties/${locatie.id}`" class="text-noir-warning text-sm hover:text-white hover:underline decoration-noir-warning underline-offset-4 uppercase font-semibold transition-all">
                        VIEW_SECTOR >
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
