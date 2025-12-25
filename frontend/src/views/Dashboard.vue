<script setup>
import { ref, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import { RouterLink } from 'vue-router';
import axios from '../axios';

import { watch } from 'vue';

const store = useStore();
const isAuthenticated = computed(() => store.getters['auth/isAuthenticated']);

const stats = ref({
    personages: 0,
    locaties: 0,
    aanwijzingen: 0,
    scenes: 0,
    sectoren: 0,
    dialogen: 0,
    notities: 0
});

const loading = ref(true);

const fetchStats = async () => {
    if (!isAuthenticated.value) return;

    try {
        const [pRes, lRes, aRes, sRes, secRes, dRes, nRes] = await Promise.all([
            axios.get('/api/personages'),
            axios.get('/api/locaties'),
            axios.get('/api/aanwijzingen'),
            axios.get('/api/scenes'),
            axios.get('/api/sectoren'),
            axios.get('/api/dialogen'),
            axios.get('/api/notities')
        ]);
        stats.value = {
            personages: pRes.data.length,
            locaties: lRes.data.length,
            aanwijzingen: aRes.data.length,
            scenes: sRes.data.length,
            sectoren: secRes.data.length,
            dialogen: dRes.data.length,
            notities: nRes.data.length
        };
    } catch (e) {
        console.error("Failed to load stats", e);
    } finally {
        loading.value = false;
    }
};

watch(isAuthenticated, (newVal) => {
    if (newVal) {
        fetchStats();
    }
}, { immediate: true });
</script>

<template>
    <div class="container mx-auto p-6">
        <div v-if="isAuthenticated">
            <h1 class="text-3xl font-bold text-white mb-8 tracking-tight">
                CASE_STATUS: <span class="text-noir-success">ACTIEF</span>
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

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                <!-- Scenes -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Scenes</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.scenes }}</p>
                </div>

                <!-- Sectors -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Sectors</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.sectoren }}</p>
                </div>

                <!-- Dialogen -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Dialogen</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.dialogen }}</p>
                </div>

                <!-- Notities -->
                <div class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h3 class="text-noir-muted uppercase text-sm font-semibold mb-2">Notities</h3>
                    <p class="text-3xl font-bold text-white">{{ stats.notities }}</p>
                </div>
            </div>

            <div class="bg-noir-panel border border-noir-dark p-6 rounded">
                <h2 class="text-xl font-bold text-white mb-4 border-b border-noir-dark pb-2">SPEL_DOEL</h2>
                <p class="text-noir-text text-lg">
                    > Vind de bron van het gestolen DNA-bestand. Of iets.
                    <span class="animate-pulse inline-block w-2 h-4 bg-noir-accent ml-1 align-middle"></span>
                </p>
            </div>
        </div>

        <div v-else class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <div class="bg-noir-panel border border-noir-dark p-8 rounded-lg shadow-2xl max-w-md w-full">
                <h1 class="text-3xl font-bold text-white mb-4">Cyber Noir Reference</h1>
                <p class="text-noir-muted mb-8">Toegang beperkt. Autorisatie vereist om dossiers te bekijken.</p>
                <RouterLink :to="{ name: 'login' }" class="inline-block px-6 py-3 bg-noir-accent hover:bg-opacity-80 text-white font-bold rounded transition-colors duration-200">
                    Login in Terminal
                </RouterLink>
            </div>
        </div>
    </div>
</template>
