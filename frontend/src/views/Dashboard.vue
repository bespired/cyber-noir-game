<script setup>
import { ref, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import { RouterLink } from 'vue-router';
import axios from '../axios';

const store = useStore();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const stats = ref({
    personages: 0,
    locaties: 0,
    aanwijzingen: 0
});

const loading = ref(true);

onMounted(async () => {
    if (!isAuthenticated.value) return; // Don't fetch stats if not logged in

    try {
        // In a real app, we'd have a stats endpoint. For now, we'll fetch counts.
        // Or just placeholders if endpoints aren't ready.
        // Let's assume we can fetch lists and count them for now.
        const [pRes, lRes, aRes] = await Promise.all([
            axios.get('/api/personages'),
            axios.get('/api/locaties'),
            axios.get('/api/aanwijzingen')
        ]);
        stats.value = {
            personages: pRes.data.length,
            locaties: lRes.data.length,
            aanwijzingen: aRes.data.length
        };
    } catch (e) {
        console.error("Failed to load stats", e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="container mx-auto p-6">
        <div v-if="isAuthenticated">
            <h1 class="text-3xl font-bold text-white mb-8 tracking-tight">
                CASE_STATUS: <span class="text-noir-success">ACTIVE</span>
            </h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <!-- Stat Card 1 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Personages</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.personages }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-accent w-2/3"></div>
                    </div>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Locaties</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.locaties }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-warning w-1/2"></div>
                    </div>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-noir-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Aanwijzingen</h3>
                    <p class="text-4xl font-bold text-white">{{ stats.aanwijzingen }}</p>
                    <div class="mt-4 h-1 w-full bg-noir-dark rounded-full overflow-hidden">
                        <div class="h-full bg-noir-danger w-3/4"></div>
                    </div>
                </div>
            </div>

            <div class="bg-noir-panel border border-noir-dark p-6 rounded">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-noir-dark pb-2">CURRENT_OBJECTIVE</h2>
                <p class="text-noir-text text-lg">
                    > Vind de bron van het gestolen data-bestand.
                    <span class="animate-pulse inline-block w-2 h-4 bg-noir-accent ml-1 align-middle"></span>
                </p>
            </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <div class="bg-noir-panel border border-noir-dark p-8 rounded-lg shadow-2xl max-w-md w-full">
                <h1 class="text-3xl font-bold text-white mb-4">Cyber Noir Reference</h1>
                <p class="text-noir-muted mb-8">Access restricted. Authorization required to view case files.</p>
                <RouterLink :to="{ name: 'login' }" class="inline-block px-6 py-3 bg-noir-accent hover:bg-opacity-80 text-white font-bold rounded transition-colors duration-200">
                    Login to Terminal
                </RouterLink>
            </div>
        </div>
    </div>
</template>
