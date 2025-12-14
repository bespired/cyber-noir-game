<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';

const route = useRoute();
const router = useRouter();
const scene = ref(null);
const locaties = ref([]);
const loading = ref(true);

onMounted(async () => {
    try {
        const [sceneResponse, locatiesResponse] = await Promise.all([
            axios.get(`/api/scenes/${route.params.id}`),
            axios.get('/api/locaties')
        ]);
        scene.value = sceneResponse.data;
        locaties.value = locatiesResponse.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const saveChanges = async () => {
    try {
        await axios.put(`/api/scenes/${scene.value.id}`, scene.value);
        alert('CHANGES_SAVED');
    } catch (e) {
        alert('ERROR_SAVING');
    }
};

const deleteScene = async () => {
    if (confirm('DELETE_SCENE? This action cannot be undone.')) {
        try {
            await axios.delete(`/api/scenes/${scene.value.id}`);
            router.push('/scenes');
        } catch (e) {
            alert('ERROR_DELETING');
        }
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'active': return 'text-noir-success border-noir-success';
        case 'completed': return 'text-noir-muted border-noir-muted';
        case 'locked': return 'text-noir-danger border-noir-danger';
        default: return 'text-noir-muted border-noir-muted';
    }
};
</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        LOADING_SCENE_DATA...
    </div>

    <div v-else-if="scene" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/scenes" class="hover:text-white">&lt; BACK_TO_SCENES</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ scene.titel }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-noir-dark flex justify-between items-start bg-noir-dark/50">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">{{ scene.titel }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-noir-accent font-mono text-sm uppercase tracking-wider">{{ scene.type }}</span>
                        <span class="text-xs text-noir-muted">SCENE_ID: {{ String(scene.id).padStart(8, '0') }}</span>
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase border" :class="getStatusColor(scene.status)">
                            {{ scene.status }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="saveChanges" class="bg-noir-success/20 text-noir-success border border-noir-success px-4 py-2 rounded hover:bg-noir-success hover:text-black hover:shadow-[0_0_15px_rgba(16,185,129,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                        SAVE_CHANGES
                    </button>
                    <button @click="deleteScene" class="bg-noir-danger/20 text-noir-danger border border-noir-danger px-4 py-2 rounded hover:bg-noir-danger hover:text-white hover:shadow-[0_0_15px_rgba(239,68,68,0.5)] transition-all duration-300 uppercase font-bold text-xs tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                        DELETE
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Titel</label>
                            <input v-model="scene.titel" type="text" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Type</label>
                            <select v-model="scene.type" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                                <option value="standard">Standard</option>
                                <option value="interrogation">Interrogation</option>
                                <option value="combat">Combat</option>
                                <option value="investigation">Investigation</option>
                                <option value="dialogue">Dialogue</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Status</label>
                            <select v-model="scene.status" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Locatie</label>
                            <select v-model="scene.locatie_id" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors">
                                <option :value="null">-- Select Location --</option>
                                <option v-for="loc in locaties" :key="loc.id" :value="loc.id">
                                    {{ loc.naam }}
                                </option>
                            </select>
                        </div>

                        <!-- Current Location Info -->
                        <div v-if="scene.locatie" class="p-4 bg-noir-dark/30 rounded border border-noir-dark">
                            <h3 class="text-xs font-bold text-noir-muted uppercase mb-2">Current Location</h3>
                            <div class="text-white font-semibold mb-1">{{ scene.locatie.naam }}</div>
                            <div class="text-sm text-noir-text">{{ scene.locatie.beschrijving }}</div>
                            <div v-if="scene.locatie.adres" class="text-xs text-noir-muted mt-2">
                                📍 {{ scene.locatie.adres }}
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-noir-muted uppercase mb-2">Beschrijving</label>
                            <textarea v-model="scene.beschrijving" rows="12" class="w-full bg-noir-dark border border-noir-panel rounded p-2 text-white focus:border-noir-accent focus:outline-none transition-colors" placeholder="Describe what happens in this scene..."></textarea>
                        </div>

                        <!-- Scene Metadata -->
                        <div class="p-4 bg-noir-dark/30 rounded border border-noir-dark">
                            <h3 class="text-xs font-bold text-noir-muted uppercase mb-3">Scene Metadata</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-noir-muted">Created:</span>
                                    <span class="text-white">{{ new Date(scene.created_at).toLocaleString() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-noir-muted">Last Updated:</span>
                                    <span class="text-white">{{ new Date(scene.updated_at).toLocaleString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
