<script setup>
import { ref, onMounted, computed } from 'vue';
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

onMounted(async () => {
    await fetchSectors();
    await fetchLocaties();
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

const filteredLocaties = computed(() => {
    if (!selectedSector.value) return locaties.value;
    return locaties.value.filter(l => l.sector_id == selectedSector.value);
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
                <h1 class="text-3xl font-bold text-white tracking-tight">LOCATIES</h1>
                <select v-model="selectedSector" class="bg-noir-darker text-noir-text border border-noir-dark rounded px-3 py-2 text-sm focus:border-noir-warning focus:outline-none">
                    <option value="">ALLE SECTORS</option>
                    <option v-for="sector in sectors" :key="sector.id" :value="sector.id">
                        {{ sector.naam }}
                    </option>
                </select>
            </div>
            <button @click="openModal" class="bg-noir-warning text-black px-4 py-2 rounded hover:bg-yellow-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + NIEUWE LOCATIE
            </button>
        </div>

        <div v-if="loading" class="text-center text-noir-muted animate-pulse">
            SCANNING_SECTORS...
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="locatie in filteredLocaties" :key="locatie.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-colors group flex flex-col">
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
                        BEWERK_LOCATIE >
                    </RouterLink>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal :isOpen="showModal" title="NEW LOCATION ENTRY" @close="showModal = false">
            <form @submit.prevent="createLocatie" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Naam</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Notities</label>
                    <textarea v-model="form.notities" rows="2" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-warning focus:outline-none"></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-warning text-black font-bold rounded hover:bg-yellow-400 transition-colors">CREATE RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
