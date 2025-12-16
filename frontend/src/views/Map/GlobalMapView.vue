<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';

const sectors = ref([]);
const loading = ref(true);
const mapContainer = ref(null);
const dragging = ref(null); // { id: 1, startX: 0, startY: 0, initialLeft: 0, initialTop: 0 }

// Create Modal State
const showCreateModal = ref(false);
const form = ref({
    naam: '',
    beschrijving: '',
    kaart_coordinaten: '',
    is_ontdekt: false,
    x: 100,
    y: 100,
    width: 200,
    height: 150
});

onMounted(async () => {
    await fetchSectors();
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
});

const fetchSectors = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/sectoren');
        sectors.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

// Dragging Logic
const onMouseDown = (event, sector) => {
    event.preventDefault(); // Prevent text selection
    dragging.value = {
        sector: sector,
        startX: event.clientX,
        startY: event.clientY,
        initialX: sector.x,
        initialY: sector.y
    };
};

const onMouseMove = (event) => {
    if (!dragging.value) return;

    const dx = event.clientX - dragging.value.startX;
    const dy = event.clientY - dragging.value.startY;

    // Update local state immediately for responsiveness
    dragging.value.sector.x = Math.max(0, dragging.value.initialX + dx);
    dragging.value.sector.y = Math.max(0, dragging.value.initialY + dy);
};

const onMouseUp = async () => {
    if (!dragging.value) return;

    const sector = dragging.value.sector;
    dragging.value = null; // Stop dragging

    // Save final position to backend
    try {
        await axios.put(`/api/sectoren/${sector.id}`, {
            x: sector.x,
            y: sector.y,
            // Send other required fields to satisfy validation if necessary,
            // though typically PATCH/PUT might validate 'sometimes'.
            // Our controller validates 'sometimes' for update, so just x/y is fine?
            // Wait, standard update usually requires full body if we use the same validation rules.
            // Let's check SectorController validation... it's `unique:sectoren,naam,id`, so likely fine if we just send x/y?
            // Actually the controller validates everything if present. But standard resource controller patterns often assume full object or patch.
            // To be safe and compliant with the update method we made:
            naam: sector.naam,
            width: sector.width,
            height: sector.height
        });
    } catch (e) {
        console.error("Failed to save position", e);
    }
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

// Create Logic
const openCreateModal = () => {
    showCreateModal.value = true;
};

const createSector = async () => {
    try {
        await axios.post('/api/sectoren', form.value);
        showCreateModal.value = false;
        await fetchSectors();
    } catch (e) {
        console.error("Failed to create sector", e);
    }
};
</script>

<template>
    <div class="h-[calc(100vh-4rem)] flex flex-col">
        <!-- Toolbar -->
        <div class="h-16 bg-noir-dark border-b border-noir-panel px-6 flex justify-between items-center z-20">
            <h1 class="text-2xl font-bold text-white tracking-tight">DE SECTOR MAP</h1>
            <div class="flex gap-4">
                <span class="text-xs text-noir-muted self-center">SLEEP SECTOREN | KLIK NAAM OM TE BEWERKEN</span>
                <button @click="openCreateModal" class="bg-noir-accent text-white px-4 py-2 rounded hover:bg-blue-500 uppercase font-bold text-xs tracking-wider">
                    + SECTOR TOEVOEGEN
                </button>
            </div>
        </div>

        <!-- Map Canvas -->
        <div class="flex-grow relative overflow-auto bg-[#0a0a0a] cursor-crosshair" ref="mapContainer">
            <!-- Grid Background -->
            <div class="absolute inset-0 pointer-events-none opacity-20"
                 style="background-image: radial-gradient(#333 1px, transparent 1px); background-size: 20px 20px;">
            </div>

            <!-- Sectors -->
            <div v-for="sector in sectors" :key="sector.id"
                 @mousedown="onMouseDown($event, sector)"
                 :style="{
                     left: `${sector.x}px`,
                     top: `${sector.y}px`,
                     width: `${sector.width}px`,
                     height: `${sector.height}px`
                 }"
                 class="absolute bg-noir-panel border border-noir-dark group hover:border-noir-accent hover:z-50 shadow-lg flex flex-col select-none transition-shadow"
                 :class="{ 'border-noir-accent z-50 ring-1 ring-noir-accent': dragging?.sector?.id === sector.id }">

                <!-- Sector Image (Background) -->
                <div class="absolute inset-0 z-0">
                     <img v-if="sector.artwork && sector.artwork.length > 0"
                          :src="getImageUrl(sector.artwork[0].bestandspad)"
                          class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity pointer-events-none">
                     <div v-else class="w-full h-full bg-noir-dark/50 flex items-center justify-center pointer-events-none">
                         <span class="text-4xl opacity-10">?</span>
                     </div>
                </div>

                <!-- Overlay Info -->
                <div class="relative z-10 p-2 flex flex-col h-full justify-between bg-gradient-to-t from-black/80 to-transparent">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] bg-black/50 px-1 rounded text-noir-muted font-mono">SEC-{{ sector.id }}</span>
                        <div :class="['w-2 h-2 rounded-full', sector.is_ontdekt ? 'bg-noir-success' : 'bg-noir-danger']"></div>
                    </div>

                    <div>
                         <RouterLink :to="`/map/${sector.id}`" class="text-white font-bold hover:underline decoration-noir-accent underline-offset-2 uppercase text-sm drop-shadow-md block truncate">
                            {{ sector.naam }}
                        </RouterLink>
                    </div>
                </div>

                <!-- Resize Handle (Visual Only for now) -->
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-noir-muted/50 cursor-se-resize"></div>
            </div>
        </div>

        <!-- Create Modal -->
         <Modal :isOpen="showCreateModal" title="NIEUWE SECTOR" @close="showCreateModal = false">
            <form @submit.prevent="createSector" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Naam</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">Beschrijving</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Breedte (px)</label>
                        <input v-model="form.width" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">Hoogte (px)</label>
                        <input v-model="form.height" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-noir-muted hover:text-white transition-colors">ANNULEREN</button>
                    <button type="submit" class="px-4 py-2 bg-noir-accent text-white rounded hover:bg-blue-600 transition-colors">AANMAKEN</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
