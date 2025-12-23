<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const locaties = ref([]);
const loading = ref(true);
const showModal = ref(false);
const form = ref({
    naam: '',
    beschrijving: '',
    notities: ''
});

const sectors = ref([]);
const selectedSector = ref('');

onMounted(async () => {
    // Restore filter from sessionStorage
    const savedFilter = sessionStorage.getItem('locatie-sector-filter');
    if (savedFilter) {
        selectedSector.value = savedFilter;
    }

    await fetchSectors();
    await fetchLocaties();
});

watch(selectedSector, (newVal) => {
    sessionStorage.setItem('locatie-sector-filter', newVal);
});

const fetchSectors = async () => {
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data.sort((a, b) => a.naam.localeCompare(b.naam));
    } catch (e) {
        console.error("Failed to fetch sectors", e);
    }
}

const filteredLocaties = computed(() => {
    if (!selectedSector.value) return locaties.value;
    // Filter locaties that have at least one scene in the selected sector
    return locaties.value.filter(l => 
        l.scenes && l.scenes.some(s => s.sector_id == selectedSector.value)
    );
});

const fetchLocaties = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/locaties');
        locaties.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

const openModal = () => {
    form.value = {
        naam: '',
        beschrijving: '',
        notities: ''
    };
    showModal.value = true;
};

const createLocatie = async () => {
    try {
        await axios.post('/api/locaties', form.value);
        showModal.value = false;
        await fetchLocaties();
    } catch (e) {
        console.error("Failed to create locatie", e);
    }
};

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
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-white tracking-tight leading-none">LOCATIES</h1>
                <div class="flex items-center gap-2">
                    <select v-model="selectedSector" class="bg-noir-darker text-noir-text border border-noir-dark rounded px-3 py-2 text-sm focus:border-noir-warning focus:outline-none uppercase">
                        <option value="">ALLE SECTORS</option>
                        <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                            {{ sector.naam }}
                        </option>
                    </select>
                    <button v-if="selectedSector" @click="selectedSector = ''" class="bg-noir-dark border border-noir-dark text-noir-muted hover:text-white px-2 py-2 rounded text-xs transition-colors" title="Clear Filter">
                        ✕
                    </button>
                </div>
            </div>
            <div class="flex gap-4">
                <RouterLink to="/reorder/locaties" class="bg-noir-dark border border-noir-accent text-noir-accent px-4 py-2 rounded hover:bg-noir-accent/10 transition-all duration-300 uppercase font-bold text-sm tracking-wider flex items-center gap-2">
                    <span class="text-lg leading-none">⇅</span> REORDER_DATA
                </RouterLink>
                <button @click="openModal" class="bg-noir-warning text-black px-4 py-2 rounded hover:bg-yellow-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                    + NIEUWE LOCATIE
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse font-mono tracking-widest text-lg py-20">
            LOADING_GEOSPATIAL_DATA...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="locatie in filteredLocaties" :key="locatie.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-all group flex flex-col relative overflow-hidden">
                <!-- Thumbnail -->
                <div v-if="locatie.artwork && locatie.artwork.length > 0" class="mb-4">
                    <img 
                        :src="getImageUrl(locatie.artwork[0].bestandspad)" 
                        :alt="locatie.naam"
                        class="w-full aspect-video object-cover rounded border border-noir-dark opacity-80 group-hover:opacity-100 transition-opacity"
                    >
                </div>
                <div v-else class="mb-4 aspect-video bg-noir-darker rounded border border-noir-dark flex items-center justify-center text-noir-muted text-xs uppercase tracking-widest">
                    NO_VISUAL_DATA
                </div>

                <h2 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors leading-tight">{{ locatie.naam }}</h2>

                <p class="text-noir-text text-sm mb-4 line-clamp-3 flex-grow">{{ locatie.beschrijving }}</p>

                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <span class="text-xs text-noir-muted font-mono">LOC_ID: {{ String(locatie.id).padStart(4, '0') }}</span>
                    <RouterLink :to="`/locaties/${locatie.id}`" class="text-noir-warning text-sm hover:text-white hover:underline decoration-noir-warning underline-offset-4 uppercase font-semibold transition-all">
                        INVESTIGATE >
                    </RouterLink>
                </div>
            </div>
        </div>

        <div v-if="!loading && filteredLocaties.length === 0" class="text-center py-20 border-2 border-dashed border-noir-dark rounded text-noir-muted uppercase tracking-widest bg-noir-darker/30">
            NO_LOCATIONS_DETECTED_IN_THIS_SECTOR
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW DATA ENTRY" @close="showModal = false">
            <form @submit.prevent="createLocatie" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1 font-bold">Location Name</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none placeholder-noir-muted/30" placeholder="NAME">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1 font-bold">Coordinates / Description</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none placeholder-noir-muted/30" placeholder="ATMOSPHERE, DETAILS..."></textarea>
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1 font-bold">Intel / Notes</label>
                    <textarea v-model="form.notities" rows="2" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none placeholder-noir-muted/30" placeholder="PRIVATE INVESTIGATOR NOTES..."></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-3 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors uppercase font-bold">DISCARD</button>
                    <button type="submit" class="px-4 py-2 bg-noir-warning text-black font-bold rounded hover:bg-yellow-400 transition-colors uppercase tracking-widest">AUTHORIZE_ENTRY</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
