<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';

const scenes = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/scenes');
        scenes.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const getStatusColor = (status) => {
    switch (status) {
        case 'active': return 'bg-noir-success';
        case 'completed': return 'bg-noir-muted';
        case 'locked': return 'bg-noir-danger';
        default: return 'bg-noir-muted';
    }
};

const getTypeIcon = (type) => {
    switch (type) {
        case 'interrogation': return '🎭';
        case 'combat': return '⚔️';
        case 'investigation': return '🔍';
        default: return '📍';
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white tracking-tight">SCENES</h1>
            <button class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NEW SCENE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LOADING_SCENE_DATA...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="scene in scenes" :key="scene.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-2xl">{{ getTypeIcon(scene.type) }}</span>
                            <h2 class="text-xl font-bold text-white group-hover:text-noir-accent transition-colors">{{ scene.titel }}</h2>
                        </div>
                        <span class="text-xs text-noir-muted uppercase tracking-wider">{{ scene.type }}</span>
                    </div>
                    <div :class="['h-2 w-2 rounded-full', getStatusColor(scene.status)]"></div>
                </div>
                
                <p class="text-noir-text text-sm mb-4 line-clamp-3">{{ scene.beschrijving }}</p>

                <!-- Location Info -->
                <div v-if="scene.locatie" class="mb-4 p-3 bg-noir-dark/30 rounded border border-noir-dark">
                    <div class="text-xs text-noir-muted uppercase mb-1">Location</div>
                    <div class="text-sm text-white font-semibold">{{ scene.locatie.naam }}</div>
                </div>
                
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-noir-muted">ID: {{ String(scene.id).padStart(4, '0') }}</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase" :class="{
                            'bg-noir-success/20 text-noir-success border border-noir-success': scene.status === 'active',
                            'bg-noir-muted/20 text-noir-muted border border-noir-muted': scene.status === 'completed',
                            'bg-noir-danger/20 text-noir-danger border border-noir-danger': scene.status === 'locked'
                        }">{{ scene.status }}</span>
                    </div>
                    <RouterLink :to="`/scenes/${scene.id}`" class="text-noir-accent text-sm hover:text-white hover:underline decoration-noir-accent underline-offset-4 uppercase font-semibold transition-all">
                        ACCESS_SCENE >
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
