<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const route = useRoute();
const sector = ref(null);
const loading = ref(true);
const showEditModal = ref(false);
const fileInput = ref(null);
const uploadLoading = ref(false);

const editForm = ref({
    naam: '',
    beschrijving: '',
    kaart_coordinaten: '',
    is_ontdekt: false,
    x: 0,
    y: 0,
    width: 100,
    height: 100
});

onMounted(async () => {
    await fetchSector();
});

const fetchSector = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/sectoren/${route.params.id}`);
        sector.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openEditModal = () => {
    editForm.value = {
        naam: sector.value.naam,
        beschrijving: sector.value.beschrijving,
        kaart_coordinaten: sector.value.kaart_coordinaten,
        is_ontdekt: sector.value.is_ontdekt,
        x: sector.value.x || 0,
        y: sector.value.y || 0,
        width: sector.value.width || 100,
        height: sector.value.height || 100
    };
    showEditModal.value = true;
};

const updateSector = async () => {
    try {
        const response = await axios.put(`/api/sectoren/${sector.value.id}`, editForm.value);
        sector.value = response.data;
        showEditModal.value = false;
    } catch (e) {
        console.error("Failed to update sector", e);
    }
};

const triggerUpload = () => {
    fileInput.value.click();
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('artwork', file);

    uploadLoading.value = true;
    try {
        // Upload to /upload/sector/{id}
        await axios.post(`/api/upload/sector/${sector.value.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        await fetchSector(); // Refresh to show new image
    } catch (e) {
        console.error("Upload failed", e);
        alert("Upload failed: " + (e.response?.data?.message || e.message));
    } finally {
        uploadLoading.value = false;
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
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        LOADING_SECTOR_DATA...
    </div>

    <div v-else-if="sector" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/map" class="hover:text-white">&lt; BACK_TO_MAP</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ sector.naam }}</span>
        </div>

        <div class="bg-noir-panel border border-noir-dark rounded shadow-xl overflow-hidden mb-8 grid grid-cols-1 lg:grid-cols-3">
            <!-- Image Section -->
            <div class="lg:col-span-1 border-r border-noir-dark bg-black relative group">
                <div v-if="sector.artwork && sector.artwork.length > 0" class="w-full h-full min-h-[200px]">
                    <img :src="getImageUrl(sector.artwork[0].bestandspad)" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                </div>
                <div v-else class="w-full h-full min-h-[200px] flex items-center justify-center text-noir-muted bg-noir-dark/50">
                    NO_VISUAL_DATA
                </div>
                
                <!-- Upload Overlay -->
                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <button @click="triggerUpload" class="px-4 py-2 border border-white text-white hover:bg-white hover:text-black transition-colors uppercase text-xs font-bold tracking-wider">
                        {{ uploadLoading ? 'UPLOADING...' : 'CHANGE_VISUAL' }}
                    </button>
                    <input type="file" ref="fileInput" @change="handleFileUpload" class="hidden" accept="image/*">
                </div>
            </div>

            <!-- Details Section -->
            <div class="lg:col-span-2 p-6 flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <h1 class="text-3xl font-bold text-white">{{ sector.naam }}</h1>
                    <button @click="openEditModal" class="text-noir-accent hover:text-white text-sm font-bold uppercase tracking-wider border border-noir-accent hover:border-white px-3 py-1 rounded transition-colors">
                        EDIT_DATA
                    </button>
                </div>
                <p class="text-noir-text mb-6 flex-grow">{{ sector.beschrijving }}</p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-noir-muted border-t border-noir-dark pt-4 font-mono">
                    <div>
                        <div class="uppercase mb-1">Coordinates</div>
                        <div class="text-white">{{ sector.kaart_coordinaten || 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="uppercase mb-1">Grid Position</div>
                        <div class="text-white">X:{{ sector.x }} Y:{{ sector.y }}</div>
                    </div>
                    <div>
                        <div class="uppercase mb-1">Dimensions</div>
                        <div class="text-white">{{ sector.width }}x{{ sector.height }}</div>
                    </div>
                     <div>
                        <div class="uppercase mb-1">Status</div>
                        <div :class="sector.is_ontdekt ? 'text-noir-success' : 'text-noir-muted'">{{ sector.is_ontdekt ? 'DISCOVERED' : 'UNKNOWN' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-white uppercase tracking-wider">Locations in Sector</h2>
            <button class="bg-noir-warning text-black px-4 py-2 rounded hover:bg-yellow-400 hover:shadow-[0_0_15px_rgba(245,158,11,0.5)] transition-all duration-300 uppercase font-bold text-sm tracking-wider transform hover:-translate-y-0.5 cursor-pointer">
                + ADD LOCATION
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="locatie in sector.locaties" :key="locatie.id" class="bg-noir-panel border border-noir-dark p-6 rounded shadow-lg hover:border-noir-warning transition-colors group">
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-noir-warning transition-colors">{{ locatie.naam }}</h3>
                <p class="text-noir-text text-sm mb-4 line-clamp-2">{{ locatie.beschrijving }}</p>
                
                <div class="flex justify-between items-center mt-4 pt-4 border-t border-noir-dark">
                    <span class="text-xs text-noir-muted">ID: {{ String(locatie.id).padStart(4, '0') }}</span>
                    <RouterLink :to="`/locaties/${locatie.id}`" class="text-noir-warning text-sm hover:text-white hover:underline decoration-noir-warning underline-offset-4 uppercase font-semibold transition-all">
                        INSPECT >
                    </RouterLink>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="!sector.locaties || sector.locaties.length === 0" class="col-span-full text-center py-12 border-2 border-dashed border-noir-dark rounded text-noir-muted">
                NO_LOCATIONS_DETECTED
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :isOpen="showEditModal" title="EDIT SECTOR DATA" @close="showEditModal = false">
            <form @submit.prevent="updateSector" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Naam</label>
                    <input v-model="editForm.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="editForm.beschrijving" required rows="4" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Width (px)</label>
                        <input v-model="editForm.width" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Height (px)</label>
                        <input v-model="editForm.height" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                </div>
                <!-- X/Y are managed via map but editable here if needed -->
                
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">CANCEL</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">UPDATE RECORD</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
