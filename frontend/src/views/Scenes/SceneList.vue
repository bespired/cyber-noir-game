<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const scenes = ref([]);
const locaties = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    titel: '',
    locatie_id: '',
    sector_id: '',
    type: 'walkable-area',
    beschrijving: '',
    entry_point: '',
    exit_point: '',
    status: 'active'
});

onMounted(async () => {
    await Promise.all([
        fetchScenes(),
        fetchLocaties(),
        fetchSectors()
    ]);
});

const sectors = ref([]);
const selectedSector = ref('');

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data;
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    }
}

const filteredScenes = computed(() => {
    if (!selectedSector.value) return scenes.value;
    return scenes.value.filter(s => s.sector_id == selectedSector.value);
});

const fetchScenes = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/scenes');
        scenes.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const fetchLocaties = async () => {
    try {
        const response = await axios.get('/api/locaties');
        locaties.value = response.data;
    } catch (e) {
        console.error("Failed to fetch locations", e);
    }
};

const openModal = () => {
    form.value = {
        titel: '',
        locatie_id: '',
        sector_id: selectedSector.value || '',
        type: 'walkable-area',
        beschrijving: '',
        entry_point: '',
        exit_point: '',
        status: 'active'
    };
    showModal.value = true;
};

const createScene = async () => {
    try {
        await axios.post('/api/scenes', form.value);
        showModal.value = false;
        await fetchScenes();
    } catch (e) {
        console.error("Failed to create scene", e);
    }
};

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
        case 'walkable-area': return '👣';
        case 'practice': return '🎯';
        default: return '📍';
    }
};
</script>

<template>
    <div class="container mx-auto p-6">
        <div class="flex justify-between items-center mb-8">
             <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-white tracking-tight">SCENES</h1>
                <select v-model="selectedSector" class="bg-noir-darker text-noir-text border border-noir-dark rounded px-3 py-2 text-sm focus:border-noir-accent focus:outline-none">
                    <option value="">ALLE SECTOREN</option>
                    <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                        {{ sector.naam }}
                    </option>
                </select>
            </div>
            <button @click="openModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NIEUWE SCENE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LADEN_SCENE_DATA...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="scene in filteredScenes" :key="scene.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-accent transition-colors group">
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
                        WIJZIG_SCENE >
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW SCENE ENTRY" @close="showModal = false">
            <form @submit.prevent="createScene" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Titel</label>
                    <input v-model="form.titel" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Sector</label>
                        <select v-model="form.sector_id" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                            <option value="" disabled>Select a sector</option>
                            <option v-for="sector in sectors" :key="sector.id" :value="sector.id">{{ sector.naam }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Location</label>
                        <select v-model="form.locatie_id" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                            <option value="" disabled>Select a location</option>
                            <option v-for="loc in locaties" :key="loc.id" :value="loc.id">{{ loc.naam }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Type</label>
                        <select v-model="form.type" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                            <option value="walkable-area">Walkable Area (Outside)</option>
                            <option value="investigation">Investigation (Inside)</option>
                            <option value="interrogation">Interrogation</option>
                            <option value="combat">Combat</option>
                            <option value="practice">Practice</option>
                        </select>
                    </div>
                     <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Status</label>
                        <select v-model="form.status" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Entry Point</label>
                        <textarea v-model="form.entry_point" rows="2" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Exit Point</label>
                        <textarea v-model="form.exit_point" rows="2" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">MAAK_RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
