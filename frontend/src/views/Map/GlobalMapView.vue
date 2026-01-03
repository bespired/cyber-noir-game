<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from '../../axios';
import { RouterLink } from 'vue-router';
import Modal from '../../components/Modal.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const sectors = ref([]);
const loading = ref(true);
const mapContainer = ref(null);
const dragging = ref(null);
const mapBackground = ref('');

// Constants for the native map size
const NATIVE_WIDTH = 1536;
const NATIVE_HEIGHT = 1024;

// Reactive state for the actual display dimensions and offsets of the "contained" image
const mapGeometry = ref({
    width: 0,
    height: 0,
    scale: 1,
    offsetX: 0,
    offsetY: 0
});

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
    await Promise.all([
        fetchSectors(),
        fetchSettings()
    ]);
    updateMapGeometry();
    window.addEventListener('resize', updateMapGeometry);
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
});

onUnmounted(() => {
    window.removeEventListener('resize', updateMapGeometry);
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseup', onMouseUp);
});

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/instellingen/map_background');
        mapBackground.value = getImageUrl(response.data.waarde);
    } catch (e) {
        console.error("Failed to fetch map background setting", e);
        // Fallback for dev if needed, though we moved it
        mapBackground.value = '/map-noir.png';
    }
};

const updateMapGeometry = () => {
    if (!mapContainer.value) return;

    const containerWidth = mapContainer.value.clientWidth;
    const containerHeight = mapContainer.value.clientHeight;

    const containerRatio = containerWidth / containerHeight;
    const imageRatio = NATIVE_WIDTH / NATIVE_HEIGHT;

    let displayWidth, displayHeight;

    if (containerRatio > imageRatio) {
        // Container is wider than the image (letterboxed on sides)
        displayHeight = containerHeight;
        displayWidth = containerHeight * imageRatio;
    } else {
        // Container is taller than the image (letterboxed on top/bottom)
        displayWidth = containerWidth;
        displayHeight = containerWidth / imageRatio;
    }

    const scale = displayWidth / NATIVE_WIDTH;
    const offsetX = (containerWidth - displayWidth) / 2;
    const offsetY = (containerHeight - displayHeight) / 2;

    mapGeometry.value = {
        width: displayWidth,
        height: displayHeight,
        scale: scale,
        offsetX: offsetX,
        offsetY: offsetY
    };
};

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

    // Movement in screen pixels must be converted to internal coordinates
    const dx = (event.clientX - dragging.value.startX) / mapGeometry.value.scale;
    const dy = (event.clientY - dragging.value.startY) / mapGeometry.value.scale;

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
            x: Math.round(sector.x),
            y: Math.round(sector.y),
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
            <h1 class="page-header">{{ t('map.the_sector_map') }}</h1>
            <div class="flex gap-4">
                <span class="text-xs text-noir-muted self-center">
                    {{ t('map.drag_hint') }}
                </span>
                <click-button icon="+" :label="t('map.add_sector')" buttonType="add" @click="openCreateModal" />

            </div>
        </div>

        <!-- Map Canvas -->
        <div class="flex-grow relative overflow-hidden bg-[#0a0a0a] cursor-crosshair" ref="mapContainer">

            <!-- Map Background -->
            <div class="absolute inset-0 pointer-events-none opacity-80"
                 :style="{
                    backgroundImage: `url(${mapBackground})`,
                    backgroundSize: 'contain',
                    backgroundPosition: 'center',
                    backgroundRepeat: 'no-repeat'
                 }"
            >
            </div>

            <!-- Sectors -->
            <div v-for="sector in sectors" :key="sector.id"
                 @mousedown="onMouseDown($event, sector)"
                 :style="{
                     left: `${mapGeometry.offsetX + (sector.x * mapGeometry.scale)}px`,
                     top: `${mapGeometry.offsetY + (sector.y * mapGeometry.scale)}px`,
                     width: `${sector.width * mapGeometry.scale}px`,
                     height: `${sector.height * mapGeometry.scale}px`
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
                         <RouterLink :to="`/map/${sector.id}`" @mousedown.stop class="text-white font-bold hover:underline decoration-noir-accent underline-offset-2 uppercase text-sm drop-shadow-md block truncate">
                            {{ sector.naam }}
                        </RouterLink>
                    </div>
                </div>

                <!-- Resize Handle (Visual Only for now) -->
                <div class="absolute bottom-0 right-0 w-3 h-3 bg-noir-muted/50 cursor-se-resize"></div>
            </div>
        </div>

        <!-- Create Modal -->
         <Modal :isOpen="showCreateModal" :title="t('map.new_sector_title')" :okLabel="t('map.create')" @close="showCreateModal = false" @ok="createSector">
            <form @submit.prevent="createSector" class="space-y-4">
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.name') }}</label>
                    <input v-model="form.naam" type="text" required class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                </div>
                <div>
                    <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.description') }}</label>
                    <textarea v-model="form.beschrijving" required rows="3" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none"></textarea>
                </div>
                 <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.width') }}</label>
                        <input v-model="form.width" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-noir-muted text-xs uppercase mb-1">{{ t('map.height') }}</label>
                        <input v-model="form.height" type="number" class="w-full bg-noir-darker border border-noir-dark text-white p-2 rounded focus:border-noir-accent focus:outline-none">
                    </div>
                </div>
            </form>
        </Modal>
    </div>
</template>
