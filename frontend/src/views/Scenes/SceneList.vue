<script setup>
import { ref, onMounted, computed, watch } from 'vue';
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

const sectors = ref([]);
const selectedSector = ref('');

onMounted(async () => {
    // Restore filter from sessionStorage
    const savedFilter = sessionStorage.getItem('scene-sector-filter');
    if (savedFilter) {
        selectedSector.value = savedFilter;
    }

    await Promise.all([
        fetchScenes(),
        fetchLocaties(),
        fetchSectors()
    ]);
});

watch(selectedSector, (newVal) => {
    sessionStorage.setItem('scene-sector-filter', newVal);
});

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data.sort((a, b) => a.naam.localeCompare(b.naam));
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
                <h1 class="page-header">SCENES</h1>
                <div class="flex items-center gap-2">
                    <select v-model="selectedSector" class="form-input text-sm w-auto">
                        <option value="">ALLE SECTOREN</option>
                        <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                            {{ sector.naam }}
                        </option>
                    </select>
                    <button v-if="selectedSector" @click="selectedSector = ''" class="btn btn--secondary p-2 text-xs" title="Clear Filter">
                        ✕
                    </button>
                </div>
            </div>
            <button @click="openModal" class="btn btn--primary">
                + NIEUWE SCENE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            LADEN_SCENE_DATA...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="scene in filteredScenes" :key="scene.id" class="panel panel--hover group">
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
                <div v-if="scene.locatie" class="mb-4 flex justify-between items-center p-3 bg-noir-dark/30 rounded border border-noir-dark">
                    <div>
                        <div class="text-[9px] text-noir-muted uppercase mb-1">Location</div>
                        <div class="text-sm text-white font-semibold">{{ scene.locatie.naam }}</div>
                    </div>
                    <div v-if="scene.sector" class="text-right">
                        <div class="text-[9px] text-noir-muted uppercase mb-1">Sector</div>
                        <div class="text-xs text-noir-accent">{{ scene.sector.naam }}</div>
                    </div>
                </div>

                <!-- Attached NPCs & Behaviors -->
                <div v-if="scene.scene_personages && scene.scene_personages.length > 0" class="mb-4">
                    <div class="text-[9px] text-noir-muted uppercase mb-2 tracking-widest px-1">GAMEPLAY</div>
                    <div class="flex flex-col gap-1.5">
                        <div
                            v-for="sp in scene.scene_personages"
                            :key="sp.id"
                            class="flex items-center justify-between p-2 bg-noir-dark/50 rounded border border-noir-dark/50 hover:border-noir-accent/30 transition-colors"
                        >
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span class="text-sm shrink-0">
                                    <img src="/icons/vehicle.svg"   class="h-[20px]" v-if="sp.personage?.type === 'voertuig'">
                                    <img src="/icons/personage.svg" class="h-[20px]" v-else>
                                </span>
                                <span class="text-[10px] text-white font-bold uppercase truncate">{{ sp.personage?.naam }}</span>
                            </div>
                            <div v-if="sp.gedrag" class="flex items-center gap-1 shrink-0">
                                <span class="text-[8px] text-noir-muted">
                                    <img src="/icons/behavior.svg" class="h-[20px]">
                                </span>
                                <span class="text-[9px] text-noir-accent font-mono uppercase">{{ sp.gedrag.naam }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-noir-muted">ID: {{ String(scene.id).padStart(4, '0') }}</span>
                        <span class="badge" :class="{
                            'badge--active': scene.status === 'active',
                            'badge--completed': scene.status === 'completed',
                            'badge--locked': scene.status === 'locked'
                        }">{{ scene.status }}</span>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <RouterLink :to="`/scenes/${scene.id}`" class="btn--link">
                            WIJZIG_SCENE >
                        </RouterLink>

                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW SCENE ENTRY" @close="showModal = false">
            <form @submit.prevent="createScene" class="space-y-4">
                <div>
                    <label class="form-label">Titel</label>
                    <input v-model="form.titel" type="text" required class="form-input">
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Sector</label>
                        <select v-model="form.sector_id" required class="form-input">
                            <option value="" disabled>Select a sector</option>
                            <option v-for="sector in sectors" :key="sector.id" :value="sector.id">{{ sector.naam }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Location</label>
                        <select v-model="form.locatie_id" required class="form-input">
                            <option value="" disabled>Select a location</option>
                            <option v-for="loc in locaties" :key="loc.id" :value="loc.id">{{ loc.naam }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="form-label">Type</label>
                        <select v-model="form.type" required class="form-input">
                            <option value="walkable-area">Walkable Area (Outside)</option>
                            <option value="investigation">Investigation (Inside)</option>
                            <option value="interrogation">Interrogation</option>
                            <option value="combat">Combat</option>
                            <option value="practice">Practice</option>
                        </select>
                    </div>
                     <div>
                        <label class="form-label">Status</label>
                        <select v-model="form.status" required class="form-input">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="locked">Locked</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="form-input"></textarea>
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Entry Point</label>
                        <textarea v-model="form.entry_point" rows="2" class="form-input"></textarea>
                    </div>
                    <div>
                        <label class="form-label">Exit Point</label>
                        <textarea v-model="form.exit_point" rows="2" class="form-input"></textarea>
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="btn btn--secondary">CANCEL</button>
                    <button type="submit" class="btn btn--primary">MAAK_RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
