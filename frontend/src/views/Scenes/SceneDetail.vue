<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ArtworkManager from '../../components/ArtworkManager.vue';
import axios from '../../axios';
import Modal from '../../components/Modal.vue';
import { useToast } from '../../composables/useToast';

const toast = useToast();
const route = useRoute();
const router = useRouter();
const scene = ref(null);
const locaties = ref([]);
const allScenes = ref([]); // For gateway target selection
const sectors = ref([]);
const loading = ref(true);

const availableTargetScenes = computed(() => {
    if (!scene.value || !scene.value.sector_id) return [];
    const currentSectorId = scene.value.sector_id;
    return allScenes.value.filter(s => s.sector_id === currentSectorId && s.id !== scene.value.id);
});

const targetSceneSpawnPoints = computed(() => {
    if (!gatewayForm.value.target_scene_id) return [];
    const targetScene = allScenes.value.find(s => s.id === gatewayForm.value.target_scene_id);
    if (!targetScene || !targetScene.locatie || !targetScene.locatie.spawn_points) return [];
    
    const sectorId = scene.value.sector_id;
    const spawnPoints = targetScene.locatie.spawn_points[sectorId] || targetScene.locatie.spawn_points[Number(sectorId)] || [];
    return spawnPoints;
});

const activeArtwork = computed(() => {
    if (scene.value?.artwork && scene.value.artwork.length > 0) {
        return scene.value.artwork;
    }
    if (scene.value?.locatie?.artwork && scene.value.locatie.artwork.length > 0) {
        return scene.value.locatie.artwork;
    }
    return [];
});

// Gateway Editor State
const imageContainer = ref(null);
const isDrawing = ref(false);
const startPos = ref({ x: 0, y: 0 });
const previewBox = ref({ x: 0, y: 0, width: 0, height: 0 });
const showGatewayModal = ref(false);
const editingGatewayIndex = ref(null);
const gatewayForm = ref({
    target_scene_id: null,
    target_spawn_point: null,
    label: ''
});

// Dragging State
const draggingGatewayIndex = ref(null);
const dragStart = ref({ x: 0, y: 0 });
const gatewayStart = ref({ x: 0, y: 0 });
const hasMoved = ref(false);
const justDragged = ref(false);

onMounted(async () => {
    try {
        const [sceneResponse, locatiesResponse, scenesResponse, sectorsResponse] = await Promise.all([
            axios.get(`/api/scenes/${route.params.id}`),
            axios.get('/api/locaties'),
            axios.get('/api/scenes'),
            axios.get('/api/sectoren')
        ]);
        scene.value = sceneResponse.data;
        if (!scene.value.gateways) scene.value.gateways = []; // Ensure array

        locaties.value = locatiesResponse.data;
        allScenes.value = scenesResponse.data;
        sectors.value = sectorsResponse.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const saveChanges = async (silent = false) => {
    try {
        await axios.put(`/api/scenes/${scene.value.id}`, scene.value);
        if (!silent) toast.success('CHANGES_SAVED');
    } catch (e) {
        toast.error('ERROR_SAVING');
    }
};

const deleteScene = async () => {
    if (confirm('DELETE_SCENE? This action cannot be undone.')) {
        try {
            await axios.delete(`/api/scenes/${scene.value.id}`);
            router.push('/scenes');
        } catch (e) {
            toast.error('ERROR_DELETING');
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

// Artwork Handlers
const handleUploadSuccess = (newImage) => {
    if (!scene.value.artwork) scene.value.artwork = [];
    scene.value.artwork.push(newImage);
};

const handleDeleteSuccess = (deletedId) => {
    if (scene.value.artwork) {
        scene.value.artwork = scene.value.artwork.filter(img => img.id !== deletedId);
    }
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

// Gateway Logic
const startDrawing = (e) => {
    if (activeArtwork.value.length === 0) return;
    isDrawing.value = true;
    const rect = imageContainer.value.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    startPos.value = { x, y };
    previewBox.value = { x, y, width: 0, height: 0 };
};

const startDrag = (index, e) => {
    e.stopPropagation(); // Prevent drawing start
    draggingGatewayIndex.value = index;
    hasMoved.value = false;
    dragStart.value = { x: e.clientX, y: e.clientY };
    gatewayStart.value = {
        x: scene.value.gateways[index].x,
        y: scene.value.gateways[index].y
    };
};

const draw = (e) => {
    const rect = imageContainer.value.getBoundingClientRect();

    // Handle Dragging
    if (draggingGatewayIndex.value !== null) {
        hasMoved.value = true;
        const deltaX = ((e.clientX - dragStart.value.x) / rect.width) * 100;
        const deltaY = ((e.clientY - dragStart.value.y) / rect.height) * 100;

        scene.value.gateways[draggingGatewayIndex.value].x = gatewayStart.value.x + deltaX;
        scene.value.gateways[draggingGatewayIndex.value].y = gatewayStart.value.y + deltaY;
        return;
    }

    // Handle Drawing
    if (!isDrawing.value) return;
    const currentX = ((e.clientX - rect.left) / rect.width) * 100;
    const currentY = ((e.clientY - rect.top) / rect.height) * 100;

    previewBox.value = {
        x: Math.min(startPos.value.x, currentX),
        y: Math.min(startPos.value.y, currentY),
        width: Math.abs(currentX - startPos.value.x),
        height: Math.abs(currentY - startPos.value.y)
    };
};

const stopDrawing = () => {
    // Handle Stop Dragging
    if (draggingGatewayIndex.value !== null) {
        if (hasMoved.value) {
            saveChanges(true); // Silent save
            justDragged.value = true;
            setTimeout(() => justDragged.value = false, 100);
        }
        draggingGatewayIndex.value = null;
        return;
    }

    if (!isDrawing.value) return;
    isDrawing.value = false;

    // Only add if size is significant (> 1%)
    if (previewBox.value.width > 1 && previewBox.value.height > 1) {
        editingGatewayIndex.value = null; // New gateway
        gatewayForm.value = { target_scene_id: null, target_spawn_point: null, label: '' };
        showGatewayModal.value = true;
    }
};

const saveGateway = () => {
    if (editingGatewayIndex.value !== null) {
        // Edit existing
        scene.value.gateways[editingGatewayIndex.value] = {
            ...scene.value.gateways[editingGatewayIndex.value],
            ...gatewayForm.value
        };
    } else {
        // Add new
        scene.value.gateways.push({
            ...previewBox.value,
            ...gatewayForm.value
        });
    }
    saveChanges(true); // Auto-save after modal
    showGatewayModal.value = false;
};

const editGateway = (index) => {
    if (justDragged.value) return;
    editingGatewayIndex.value = index;
    const g = scene.value.gateways[index];
    gatewayForm.value = {
        target_scene_id: g.target_scene_id,
        target_spawn_point: g.target_spawn_point || null,
        label: g.label || ''
    };
    showGatewayModal.value = true;
};

const removeGateway = (index) => {
    if (confirm('DELETE_GATEWAY?')) {
        scene.value.gateways.splice(index, 1);
    }
};

const getSceneName = (id) => {
    const s = allScenes.value.find(s => s.id === id);
    return s ? s.titel : 'Unknown Scene';
};

const getPersonageName = (id) => {
    const p = allScenes.value.find(s => s.id === scene.value?.id)?.locatie?.personages?.find(pers => pers.id === id) || 
              allScenes.value.flatMap(s => s.locatie?.personages || []).find(pers => pers.id === id);
    // Note: Actually, it's better to fetch from a dedicated personages ref if we had one, 
    // but we can try to find it in the loaded allScenes.
    return p ? p.naam : `ID: ${id}`;
};

watch(() => scene.value?.locatie_id, (newId) => {
    if (!newId) {
        if (scene.value) scene.value.locatie = null;
        return;
    }
    const loc = locaties.value.find(l => l.id === newId);
    if (loc && scene.value) {
        // We set the nested location object so the computed activeArtwork updates
        scene.value.locatie = loc;
    }
});

</script>

<template>
    <div v-if="loading" class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        LADEN_SCENE_DATA...
    </div>

    <div v-else-if="scene" class="container mx-auto p-6">
        <div class="flex items-center mb-6 text-sm text-noir-muted">
            <RouterLink to="/scenes" class="hover:text-white">&lt; NAAR_OVERZICHT</RouterLink>
            <span class="mx-2">/</span>
            <span class="text-white">{{ scene.titel }}</span>
        </div>

        <div class="panel overflow-hidden">
            <!-- Header -->
            <div class="p-6 border-b border-noir-dark flex justify-between items-start bg-noir-dark/50">
                <div>
                    <h1 class="page-header mb-1">{{ scene.titel }}</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-noir-accent font-mono text-sm uppercase tracking-wider">{{ scene.type }}</span>
                        <span class="text-xs text-noir-muted">SCENE_ID: {{ String(scene.id).padStart(8, '0') }}</span>
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase border" :class="getStatusColor(scene.status)">
                            {{ scene.status }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-end">
                    <div class="flex gap-2">
                        <button @click="saveChanges" class="btn btn--success">
                            BEWAREN
                        </button>
                        <button @click="deleteScene" class="btn btn--danger">
                            VERWIJDEREN
                        </button>
                    </div>
                    <div class="flex gap-2">
                    <RouterLink v-if="scene.sector_id" :to="`/sector-map/${scene.sector_id}`" class="btn btn--accent-outline flex items-center justify-center gap-2 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                        </svg>
                        GATEWAY_OVERZICHT
                    </RouterLink>
                    <RouterLink v-if="scene.locatie_id && scene.sector_id" :to="`/locaties/${scene.locatie_id}/sector/${scene.sector_id}/3d`" class="btn btn--primary flex items-center justify-center gap-2 w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        3D_BEWERKEN
                    </RouterLink>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Visual Editor -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Artwork Manager (Hidden if we have a fallback visual but no scene-specific artwork) -->
                        <ArtworkManager
                            v-if="scene.artwork.length > 0 || activeArtwork.length === 0"
                            model-type="scene"
                            :model-id="scene.id"
                            :artwork="scene.artwork"
                            @upload-success="handleUploadSuccess"
                            @image-deleted="handleDeleteSuccess"
                        />

                        <!-- Visual Gateway Editor -->
                        <div v-if="scene.type === 'walkable-area'">
                            <div v-if="activeArtwork.length > 0" class="bg-noir-dark border border-noir-panel rounded overflow-hidden relative group select-none">
                                <div class="absolute top-2 left-2 z-10 bg-black/70 text-white text-xs px-2 py-1 rounded pointer-events-none flex items-center gap-2">
                                    <span class="text-noir-warning">⚡</span> KLIK & SLEEP OM GATEWAY TE MAKEN
                                    <span v-if="scene.artwork.length === 0" class="ml-2 text-[10px] text-noir-accent bg-noir-accent/10 px-1 rounded border border-noir-accent/30 font-mono">LOCATIE_VISUAL_IN_GEBRUIK</span>
                                </div>

                                <div
                                    ref="imageContainer"
                                    class="relative cursor-crosshair"
                                    @mousedown="startDrawing"
                                    @mousemove="draw"
                                    @mouseup="stopDrawing"
                                >
                                    <img
                                        :src="getImageUrl(activeArtwork[0].bestandspad)"
                                        class="w-full h-auto block pointer-events-none opacity-90 group-hover:opacity-100 transition-opacity"
                                    >

                                    <!-- Existing Gateways -->
                                    <div
                                        v-for="(gateway, index) in scene.gateways"
                                        :key="index"
                                        class="absolute border-2 border-noir-accent bg-noir-accent/20 hover:bg-noir-accent/40 transition-colors cursor-pointer flex items-center justify-center"
                                        :style="{
                                            left: `${gateway.x}%`,
                                            top: `${gateway.y}%`,
                                            width: `${gateway.width}%`,
                                            height: `${gateway.height}%`
                                        }"
                                        @click.stop="editGateway(index)"
                                        @mousedown="startDrag(index, $event)"
                                    >
                                        <span class="text-xs font-bold text-white bg-black/50 px-1 rounded truncate max-w-full">
                                            {{ getSceneName(gateway.target_scene_id) }}
                                        </span>
                                        <button
                                            @click.stop="removeGateway(index)"
                                            class="absolute -top-2 -right-2 bg-noir-danger text-white rounded-full w-4 h-4 flex items-center justify-center text-xs hover:scale-110 transition-transform"
                                        >
                                            &times;
                                        </button>
                                    </div>

                                    <!-- Drawing Preview -->
                                    <div
                                        v-if="isDrawing"
                                        class="absolute border-2 border-white bg-white/10 pointer-events-none"
                                        :style="{
                                            left: `${previewBox.x}%`,
                                            top: `${previewBox.y}%`,
                                            width: `${previewBox.width}%`,
                                            height: `${previewBox.height}%`
                                        }"
                                    ></div>
                                </div>
                            </div>
                            <div v-else class="p-12 text-center border-2 border-dashed border-noir-dark rounded text-noir-muted bg-noir-darker/30 flex flex-col items-center justify-center gap-3">
                                <span class="text-3xl">⚠️</span>
                                <div class="uppercase tracking-widest font-mono text-sm">NO_VISUAL_DATA_AVAILABLE</div>
                                <div class="text-[10px] max-w-xs">Upload een achtergrond voor de scene of koppel een locatie met artwork om gateways te kunnen plaatsen.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Metadata & Form -->
                    <div class="space-y-6">
                        <div>
                            <div v-if="scene.sector" class="mt-1 text-xs text-noir-muted">
                                SECTOR: <span class="text-white">{{ scene.sector.naam }}</span>
                            </div>
                            <label class="form-label">Locatie</label>
                            <select v-model="scene.locatie_id" class="form-input">
                                <option :value="null">-- GEEN LOCATIE --</option>
                                <option v-for="loc in locaties" :key="loc.id" :value="loc.id">{{ loc.naam }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Sector</label>
                            <select v-model="scene.sector_id" class="form-input">
                                <option :value="null">-- GEEN SECTOR --</option>
                                <option v-for="sec in sectors" :key="sec.id" :value="sec.id">{{ sec.naam }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Beschrijving</label>
                            <textarea v-model="scene.beschrijving" rows="12" class="form-input" placeholder="Describe what happens in this scene..."></textarea>
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
        <!-- Gateway Modal -->
        <Modal :isOpen="showGatewayModal" title="PLAATS GATEWAY" @close="showGatewayModal = false">
            <form @submit.prevent="saveGateway" class="space-y-4">
                <div v-if="scene.sector" class="mt-1 text-xs text-noir-muted">
                    SECTOR: <span class="text-white">{{ scene.sector.naam }}</span>
                </div>

                <div>
                    <label class="form-label">Doel Scene</label>
                    <select v-model="gatewayForm.target_scene_id" required class="form-input">
                        <option :value="null">-- KIES SCENE --</option>
                        <option v-for="s in availableTargetScenes" :key="s.id" :value="s.id">
                            {{ s.titel }} (ID: {{ s.id }})
                        </option>
                    </select>
                </div>
                
                <div v-if="gatewayForm.target_scene_id">
                    <label class="form-label">Spawn Point in Doel Scene</label>
                    <select v-model="gatewayForm.target_spawn_point" class="form-input">
                        <option :value="null">-- STANDAARD (PERSONAGE) --</option>
                        <optgroup label="Waypoints">
                           <option v-for="sp in targetSceneSpawnPoints.filter(p => p.type === 'waypoint')" :key="sp.id" :value="sp.name">
                               {{ sp.name }} ({{ sp.x.toFixed(1) }}, {{ sp.y.toFixed(1) }})
                           </option>
                        </optgroup>
                        <optgroup label="Personages">
                            <option v-for="sp in targetSceneSpawnPoints.filter(p => p.type === 'personage')" :key="sp.id" :value="sp.personage_id">
                               {{ getPersonageName(sp.personage_id) }} ({{ sp.x.toFixed(1) }}, {{ sp.y.toFixed(1) }})
                            </option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label class="form-label">Label (Optioneel)</label>
                    <input v-model="gatewayForm.label" type="text" placeholder="b.v. Naar Hoofd Street" class="form-input">
                </div>
                <div class="pt-4 flex justify-end gap-2 text-sm">
                    <button type="button" @click="showGatewayModal = false" class="btn btn--secondary">HMMM...</button>
                    <button type="submit" class="btn btn--primary">BEWAAR_GATEWAY</button>
                </div>
            </form>
        </Modal>
    </div>
</template>
