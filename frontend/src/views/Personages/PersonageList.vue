<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const personages = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/personages');
        personages.value = response.data;
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
            <h1 class="text-3xl font-bold text-white tracking-tight">PERSONAGES</h1>
            <button class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW ENTRY
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_DATABASE...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="personage in personages" :key="personage.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group flex gap-4">
                <!-- Thumbnail -->
                <div v-if="personage.artwork && personage.artwork.length > 0" class="flex-shrink-0 w-20">
                    <img 
                        :src="getImageUrl(personage.artwork[0].bestandspad)" 
                        :alt="personage.naam" 
                        class="w-full aspect-[2/3] object-cover rounded border border-noir-dark"
                    >
                </div>

                <div class="flex-grow">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ personage.naam }}</h2>
                            <span class="text-xs text-noir-muted uppercase tracking-wider">{{ personage.rol }}</span>
                        </div>
                        <div class="h-2 w-2 rounded-full" :class="personage.is_replicant ? 'bg-noir-danger' : 'bg-noir-success'"></div>
                    </div>
                    
                    <p class="text-noir-text text-sm mb-4 line-clamp-3">{{ personage.beschrijving }}</p>
                    
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                        <span class="text-xs text-noir-muted">ID: {{ String(personage.id).padStart(4, '0') }}</span>
                        <RouterLink :to="`/personages/${personage.id}`" class="text-noir-accent text-sm hover:text-white hover:underline decoration-noir-accent underline-offset-4 uppercase font-semibold transition-all">
                            ACCESS_FILE >
                        </RouterLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
