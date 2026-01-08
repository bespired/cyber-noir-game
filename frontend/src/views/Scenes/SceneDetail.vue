<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import ArtworkManager from '../../components/ArtworkManager.vue';
import axios from '../../axios';
import Modal from '../../components/Modal.vue';
import LinkButton from '../../components/inputs/LinkButton.vue';
import ClickButton from '../../components/inputs/ClickButton.vue';
import DetailHeader from '../../components/bars/DetailHeader.vue';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();
const route = useRoute();
const router = useRouter();
const scene = ref(null);
const locaties = ref([]);
const allScenes = ref([]); // For gateway target selection
const sectors = ref([]);
const loading = ref(true);

const allPersonages = ref([]);
const gedragingen = ref([]);
const dialogen = ref([]);
const scenePersonages = ref([]); // assignments for this scene
const openingSceneId = ref(null);

const isStartingScene = computed(() => {
    return openingSceneId.value == route.params.id;
});

const setAsStartingScene = async () => {
    try {
        await axios.put(`/api/instellingen/opening_scene`, {
            waarde: route.params.id
        });
        openingSceneId.value = route.params.id;
        toast.success(t('scenes.starting_updated'));
    } catch (e) {
        toast.error(t('scenes.starting_failed'));
    }
};

const sceneTypes = [
    { value: 'walkable-area', label: 'Walkable Area' },
    { value: 'investigation', label: 'Investigation' },
    { value: 'interrogation', label: 'Interrogation' },
    { value: 'combat', label: 'Combat' },
    { value: 'practice', label: 'Practice' },
    { value: 'vue-component', label: 'Custom Vue Component' }
];

const showNPCModal = ref(false);
const editingNPC = ref(null);
const npcForm = ref({
    personage_id: null,
    spawn_point_name: '',
    gedrag_id: null,
    dialoog_id: null,
    spawn_condition: { type: 'on_enter', flag: '' }
});

const componentParams = computed({
    get: () => {
        if (!scene.value?.data?.props) return '{}';
        return JSON.stringify(scene.value.data.props, null, 2);
    },
    set: (val) => {
        try {
            if (!scene.value.data) scene.value.data = {};
            const parsed = JSON.parse(val);
            scene.value.data = { ...scene.value.data, props: parsed };
        } catch (e) {
            // ignore
        }
    }
});

const three = computed(() => {
    // console.log(scene.value)
    if (!scene.value?.sector_id) return null
    return { location: scene.value.locatie_id, sector: scene.value.sector_id }
})

const emulate = computed(() => {

    if (scene.value.type !== 'vue-component') return null
    return scene.value.id
})

const availableTargetScenes = computed(() => {
    if (!scene.value || !scene.value.sector_id) return [];
    const currentSectorId = scene.value.sector_id;
    return allScenes.value.filter(s => (s.sector_id === currentSectorId || s.type === 'vue-component') && s.id !== scene.value.id);
});

const targetSceneSpawnPoints = computed(() => {
    if (!gatewayForm.value.target_scene_id) return [];
    const targetScene = allScenes.value.find(s => s.id === gatewayForm.value.target_scene_id);
    if (!targetScene || !targetScene.locatie || !targetScene.locatie.spawn_points) return [];

    const sectorId = scene.value.sector_id;
    const points = targetScene.locatie.spawn_points;
    return points[sectorId] || points[String(sectorId)] || points[Number(sectorId)] || [];
});

const locationSpawnPoints = computed(() => {
    if (!scene.value?.locatie?.spawn_points) return [];
    const points = scene.value.locatie.spawn_points;
    const sId = scene.value.sector_id;
    if (!sId) return [];
    return points[sId] || points[String(sId)] || points[Number(sId)] || [];
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
    type: 'gateway', // 'gateway' or 'trigger'
    target_scene_id: null,
    target_spawn_point: null,
    gedrag_id: null,
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
        const [sceneResponse, locatiesResponse, scenesResponse, sectorsResponse, persResponse, gedragResponse, dialoogResponse, assignmentsResponse, openingSceneResponse] = await Promise.all([
            axios.get(`/api/scenes/${route.params.id}`),
            axios.get('/api/locaties'),
            axios.get('/api/scenes'),
            axios.get('/api/sectoren'),
            axios.get('/api/personages'),
            axios.get('/api/gedrag'),
            axios.get('/api/dialogen'),
            axios.get(`/api/scene-personages?scene_id=${route.params.id}`),
            axios.get('/api/instellingen/opening_scene')
        ]);
        scene.value = sceneResponse.data;
        if (!scene.value.gateways) scene.value.gateways = [];
        if (!scene.value.data) scene.value.data = {};

        locaties.value = locatiesResponse.data;
        allScenes.value = scenesResponse.data;
        sectors.value = sectorsResponse.data;
        allPersonages.value = persResponse.data;
        gedragingen.value = gedragResponse.data;
        dialogen.value = dialoogResponse.data;
        scenePersonages.value = assignmentsResponse.data;
        openingSceneId.value = openingSceneResponse.data.waarde;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

const saveChanges = async (silent = false) => {
    try {
        const payload = {
            titel: scene.value.titel,
            locatie_id: scene.value.locatie_id,
            sector_id: scene.value.sector_id,
            type: scene.value.type,
            beschrijving: scene.value.beschrijving,
            status: scene.value.status,
            gateways: scene.value.gateways,
            data: scene.value.data
        };
        await axios.put(`/api/scenes/${scene.value.id}`, payload);
        if (!silent) toast.success(t('scenes.changes_saved'));
    } catch (e) {
        toast.error(t('scenes.error_saving'));
    }
};

const deleteScene = async () => {
    if (confirm(t('scenes.delete_confirm'))) {
        try {
            await axios.delete(`/api/scenes/${scene.value.id}`);
            router.push('/scenes');
        } catch (e) {
            toast.error(t('scenes.error_deleting'));
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
        gatewayForm.value = {
            type: 'gateway',
            target_scene_id: null,
            target_spawn_point: null,
            gedrag_id: null,
            label: ''
        };
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
        type: g.type || (g.target_scene_id ? 'gateway' : 'trigger'),
        target_scene_id: g.target_scene_id,
        target_spawn_point: g.target_spawn_point || null,
        gedrag_id: g.gedrag_id || null,
        label: g.label || ''
    };
    showGatewayModal.value = true;
};

const removeGateway = (index) => {

    if (confirm(t('scenes.delete_gateway_confirm'))) {
        scene.value.gateways.splice(index, 1);
    }
};

const getSceneName = (id) => {
    const s = allScenes.value.find(s => s.id === id);
    return s ? s.titel : 'Unknown Scene';
};

const getPersonageName = (id) => {
    if (!id) return t('scenes.unknown') || 'UNKNOWN';
    const p = allPersonages.value.find(pers => String(pers.id) === String(id));
    return p ? p.naam : `ID: ${id}`;
};

const getGedragName = (id) => {
    if (!id) return 'GEEN_STRICT_GEDRAG';
    const g = gedragingen.value.find(b => String(b.id) === String(id));
    return g ? g.naam : 'UNKNOWN';
};

const getSpawnPointLabelById = (targetNameOrId) => {
    if (!targetNameOrId) return 'DEFAULT';
    const sp = locationSpawnPoints.value.find(p => p.name === targetNameOrId || String(p.id) === String(targetNameOrId));
    if (!sp) return targetNameOrId;
    if (sp.name) return sp.name;
    if (sp.personage_id) return `${getPersonageName(sp.personage_id)} (Slot)`;
    return `${sp.type}_${sp.id}`;
};

const getDialoogName = (id) => {
    if (!id) return 'GEEN_START_DIALOGOOG';
    const d = dialogen.value.find(dial => dial.id === id);
    return d ? d.titel : 'UNKNOWN';
};

const openNPCModal = (npc = null) => {
    if (npc) {
        editingNPC.value = npc;
        npcForm.value = { ...npc };
    } else {
        editingNPC.value = null;
        npcForm.value = {
            scene_id: scene.value.id,
            personage_id: allPersonages.value[0]?.id || null,
            spawn_point_name: '',
            gedrag_id: null,
            dialoog_id: null,
            spawn_condition: { type: 'on_enter', flag: '' }
        };
    }
    showNPCModal.value = true;
};

const saveNPC = async () => {
    try {
        const payload = {
            ...npcForm.value,
            spawn_point_name: npcForm.value.spawn_point_name ? String(npcForm.value.spawn_point_name) : ''
        };

        if (editingNPC.value) {
            const res = await axios.put(`/api/scene-personages/${editingNPC.value.id}`, payload);
            const index = scenePersonages.value.findIndex(p => p.id === editingNPC.value.id);
            scenePersonages.value[index] = { ...scenePersonages.value[index], ...res.data };
            toast.success(t('scenes.npc_updated'));
        } else {
            const res = await axios.post('/api/scene-personages', payload);
            scenePersonages.value.push(res.data);
            toast.success(t('scenes.npc_added'));
        }
        showNPCModal.value = false;
    } catch (e) {
        toast.error('FAILED_TO_SAVE_NPC');
    }
};

const removeNPC = async (id) => {
    if (confirm(t('scenes.remove_npc_confirm'))) {
        try {
            await axios.delete(`/api/scene-personages/${id}`);
            scenePersonages.value = scenePersonages.value.filter(p => p.id !== id);
            toast.success(t('scenes.npc_removed'));
        } catch (e) {
            toast.error('FAILED_TO_REMOVE');
        }
    }
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
    <div v-if="loading"
        class="container mx-auto p-6 text-center text-noir-muted animate-pulse">
        {{ t('scenes.loading_data') }}
    </div>

    <div v-else-if="scene" class="container mx-auto p-6">

        <detail-header
            name='scene'
            backLink="scenes"
            :backlabel="t('scenes.back_to_overview')"
            :label="scene.titel"

            :three  ="three"
            :emulate="emulate"
            :gateway="scene.sector_id ? scene.sector_id : null"

            :save="true"   @save="saveChanges"
            :remove="true" @remove="deleteScene"
        />

        <div class="panel overflow-hidden">
            <!-- Header -->
            <div class="detail-panel-header">
                <div>
                    <h1 class="page-header">{{ scene.titel }}</h1>
                </div>
                <div>
                    <div class="flex items-center space-x-4">
                        <select v-model="scene.type" class="form-input text-xs w-auto py-1 px-2 h-8">
                            <option v-for="t in sceneTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <span class="text-xs text-noir-muted">{{ t('scenes.id') }}: {{ String(scene.id).padStart(8, '0') }}</span>
                        <span class="px-2 py-1 rounded text-xs font-bold uppercase border" :class="getStatusColor(scene.status)">
                            {{ scene.status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="pt-6">
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
                        <div v-if="['outside', 'inside', 'walkable-area'].includes(scene.type)">
                            <div v-if="activeArtwork.length > 0" class="bg-noir-dark border border-noir-panel rounded overflow-hidden relative group select-none">
                                <div class="absolute top-2 left-2 z-10 bg-black/70 text-white text-xs px-2 py-1 rounded pointer-events-none flex items-center gap-2">
                                    <span class="text-noir-warning">⚡</span> {{ t('scenes.click_drag_gateway') }}
                                    <span v-if="scene.artwork.length === 0" class="ml-2 text-[10px] text-noir-accent bg-noir-accent/10 px-1 rounded border border-noir-accent/30 font-mono">{{ t('scenes.location_visual_used') }}</span>
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
                                        class="absolute border-2 transition-colors cursor-pointer flex items-center justify-center shadow-lg"
                                        :class="{
                                            'border-noir-accent bg-noir-accent/20 hover:bg-noir-accent/40': (gateway.type || (gateway.target_scene_id ? 'gateway' : 'trigger')) === 'gateway',
                                            'border-noir-warning bg-noir-warning/20 hover:bg-noir-warning/40': (gateway.type || (gateway.target_scene_id ? 'gateway' : 'trigger')) === 'trigger'
                                        }"
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
                                            {{ gateway.label || (gateway.target_scene_id ? getSceneName(gateway.target_scene_id) : (gateway.gedrag_id ? getGedragName(gateway.gedrag_id) : 'ACTION')) }}
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
                                <div class="uppercase tracking-widest font-mono text-sm">{{ t('scenes.no_visual_data') }}</div>
                                <div class="text-[10px] max-w-xs">{{ t('scenes.upload_hint') }}</div>
                            </div>
                        </div>
                        <!-- Vue Component Configuration -->
                        <div v-if="scene.type === 'vue-component'" class="p-4 bg-noir-dark/30 rounded border border-noir-dark space-y-4">
                            <h3 class="text-xs font-bold text-noir-muted uppercase mb-2">{{ t('scenes.vue_config') }}</h3>

                            <div>
                                <label class="form-label">{{ t('scenes.comp_name') }}</label>
                                <input v-model="scene.data.component" type="text" class="form-input font-mono" placeholder="e.g. 'DemoTitleScene'">
                                <p class="text-[10px] text-noir-muted mt-1">{{ t('scenes.ensure_registered') }}</p>
                            </div>

                            <div>
                                <label class="form-label">{{ t('scenes.props_json') }}</label>
                                <textarea v-model="componentParams" rows="6" class="form-input font-mono text-xs"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Metadata & Form -->
                    <div class="space-y-6">
                        <!-- Location & Sector (Only for Walkable Areas) -->
                        <div v-if="['outside', 'inside', 'walkable-area'].includes(scene.type)">
                            <div>
                                <div v-if="scene.sector" class="mt-1 text-xs text-noir-muted">
                                    {{ t('scenes.form_sector') }}: <span class="text-white">{{ scene.sector.naam }}</span>
                                </div>
                                <label class="form-label">{{ t('scenes.form_location') }}</label>
                                <select v-model="scene.locatie_id" class="form-input">
                                    <option :value="null">-- {{ t('scenes.form_location') }} --</option>
                                    <option v-for="loc in locaties" :key="loc.id" :value="loc.id">{{ loc.naam }}</option>
                                </select>
                            </div>
                            <div class="mt-4">
                                <label class="form-label">{{ t('scenes.form_sector') }}</label>
                                <div class="flex gap-2">
                                    <select v-model="scene.sector_id" class="form-input flex-1">
                                        <option :value="null">-- {{ t('scenes.form_sector') }} --</option>
                                        <option v-for="sec in sectors" :key="sec.id" :value="sec.id">{{ sec.naam }}</option>
                                    </select>
                                    <LinkButton
                                        v-if="scene.sector_id"
                                        name="sector-detail"
                                        :params="{ id: scene.sector_id }"
                                        label="SECTOR"
                                        buttonType="blue"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <textarea v-model="scene.beschrijving" rows="3" class="form-input" placeholder="Describe what happens in this scene..."></textarea>
                        </div>

                        <!-- Area Dimensions (Right Column Context) -->
                        <div v-if="['outside', 'inside', 'walkable-area'].includes(scene.type)" class="p-4 bg-noir-dark/30 rounded border border-noir-dark space-y-4">
                            <h3 class="text-xs font-bold text-noir-muted uppercase mb-2">{{ t('scenes.area_dimensions') }}</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">{{ t('scenes.map_width') }}</label>
                                    <input v-model="scene.data.width" type="number" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label">{{ t('scenes.map_height') }}</label>
                                    <input v-model="scene.data.height" type="number" class="form-input">
                                </div>
                            </div>
                            <p class="text-[10px] text-noir-muted">{{ t('scenes.coords_managed', { x: scene.data?.x, y: scene.data?.y }) }}</p>
                        </div>

                        <!-- Personages & Voertuigen in Scene (Hidden for Component Scenes) -->
                        <div v-if="scene.type !== 'vue-component'" class="border-noir-accent/30">
                            <div class="p-4 border-b border-noir-dark flex justify-between items-center bg-noir-dark/50">
                                <h3 class="text-xs font-bold text-noir-accent uppercase">{{ t('scenes.characters_vehicles') }}</h3>
                                <ClickButton
                                    :label="t('scenes.add')"
                                    icon="+"
                                    buttonType="add"
                                    class="!text-[10px] !px-2 !py-0.5"
                                    @click="openNPCModal()"
                                />
                            </div>
                            <div class="pt-1 space-y-3">
                                <div v-if="scenePersonages.length === 0" class="text-center py-4 text-noir-muted italic text-[10px] uppercase">
                                    {{ t('scenes.no_characters') }}
                                </div>
                                <div v-for="npc in scenePersonages" :key="npc.id" class="bg-noir-darker/50 p-3 rounded border border-noir-dark hover:border-noir-accent/50 transition-colors group">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ npc.personage?.type === 'voertuig' ? '🚗' : '👤' }}</span>
                                            <span class="text-white font-bold text-[11px] uppercase truncate max-w-[120px]">{{ npc.personage?.naam }}</span>
                                        </div>
                                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <ClickButton @click="openNPCModal(npc)" buttonType="black" class="!p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </ClickButton>
                                            <ClickButton @click="removeNPC(npc.id)" buttonType="red" class="!p-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </ClickButton>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-1 text-[9px] font-mono">
                                        <div class="flex gap-2">
                                            <span class="text-noir-muted">SPAWN:</span>
                                            <span class="text-noir-accent truncate">{{ getSpawnPointLabelById(npc.spawn_point_name) }}</span>
                                        </div>
                                        <!--
                                            <div class="flex gap-2">
                                            <span class="text-noir-muted">GEDRAG:</span>
                                            <span class="text-noir-warning truncate">{{ getGedragName(npc.gedrag_id) }}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <span class="text-noir-muted">DIALOG:</span>
                                            <span class="text-noir-success truncate">{{ getDialoogName(npc.dialoog_id) }}</span>
                                        </div>
                                        -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <Modal
            :isOpen="showGatewayModal"
            :title="gatewayForm.type === 'gateway' ? t('scenes.place_gateway') : t('scenes.place_trigger')"
            :okLabel="t('scenes.save_gateway')"
            @close="showGatewayModal = false"
            @ok="saveGateway"
        >
            <form @submit.prevent="saveGateway" class="space-y-4">
                <div class="flex gap-4 p-2 bg-black/30 rounded border border-noir-dark mb-4">
                    <button
                        type="button"
                        class="flex-1 py-2 px-3 rounded text-[10px] font-bold transition-all border"
                        :class="gatewayForm.type === 'gateway' ? 'bg-noir-accent text-black border-noir-accent' : 'bg-transparent text-noir-muted border-noir-dark hover:border-noir-accent/50'"
                        @click="gatewayForm.type = 'gateway'"
                    >
                        {{ t('scenes.traversal_gateway') }}
                    </button>
                    <button
                        type="button"
                        class="flex-1 py-2 px-3 rounded text-[10px] font-bold transition-all border"
                        :class="gatewayForm.type === 'trigger' ? 'bg-noir-warning text-black border-noir-warning' : 'bg-transparent text-noir-muted border-noir-dark hover:border-noir-warning/50'"
                        @click="gatewayForm.type = 'trigger'"
                    >
                        {{ t('scenes.system_trigger') }}
                    </button>
                </div>

                <div v-if="scene.sector" class="mt-1 text-[10px] text-noir-muted uppercase">
                    {{ t('scenes.context_sector') }}: <span class="text-white">{{ scene.sector.naam }}</span>
                </div>

                <div v-if="gatewayForm.type === 'gateway'" class="space-y-4">
                    <div>
                        <label class="form-label">{{ t('scenes.target_scene') }}</label>
                        <select v-model="gatewayForm.target_scene_id" class="form-input" required>
                            <option :value="null">{{ t('scenes.select_target') }}</option>
                            <option v-for="s in availableTargetScenes" :key="s.id" :value="s.id">
                                {{ s.titel }} (ID: {{ s.id }})
                            </option>
                        </select>
                    </div>

                    <div v-if="gatewayForm.target_scene_id">
                        <label class="form-label">{{ t('scenes.spawn_point_target') }}</label>
                        <select v-model="gatewayForm.target_spawn_point" class="form-input">
                            <option :value="null">{{ t('scenes.standard_char') }}</option>
                            <optgroup :label="t('scenes.waypoints')">
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
                </div>

                <div v-if="gatewayForm.type === 'trigger'" class="space-y-4">
                    <div>
                        <label class="form-label text-noir-warning">{{ t('scenes.action_behavior') }}</label>
                        <select v-model="gatewayForm.gedrag_id" class="form-input" required>
                            <option :value="null">{{ t('scenes.select_behavior') }}</option>
                            <option v-for="g in gedragingen" :key="g.id" :value="g.id">{{ g.naam }}</option>
                        </select>
                        <p class="text-[10px] text-noir-muted mt-1 uppercase">{{ t('scenes.launch_hint') }}</p>
                    </div>
                </div>
            </form>
        </Modal>

        <!-- NPC / Vehicle Assignment Modal -->
        <Modal
            :isOpen="showNPCModal"
            :title="editingNPC ? t('scenes.config_entity') : t('scenes.add_entity')"
            :okLabel="editingNPC ? t('common.save') : t('common.add')"
            @close="showNPCModal = false"
            @ok="saveNPC"
        >
            <form @submit.prevent="saveNPC" class="space-y-4">
                <div>
                    <label class="form-label">{{ t('scenes.character_vehicle') }}</label>
                    <select v-model="npcForm.personage_id" :disabled="!!editingNPC" class="form-input">
                        <option v-for="p in allPersonages" :key="p.id" :value="p.id">
                            [{{ p.type.toUpperCase() }}] {{ p.naam }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label text-noir-accent">{{ t('scenes.entry_spawn') }}</label>
                        <select v-model="npcForm.spawn_point_name" class="form-input">
                            <option value="">{{ t('scenes.default_spawn') }}</option>
                            <optgroup :label="t('scenes.waypoints')">
                                <option v-for="sp in locationSpawnPoints.filter(p => p.type === 'waypoint')" :key="sp.id" :value="sp.name || sp.id">
                                    {{ sp.name || 'Waypoint ' + sp.id }}
                                </option>
                            </optgroup>
                            <optgroup :label="t('scenes.personage_slots')">
                                <option v-for="sp in locationSpawnPoints.filter(p => p.type === 'personage')" :key="sp.id" :value="sp.name || sp.id">
                                    {{ getPersonageName(sp.personage_id) }} (Slot)
                                </option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ t('scenes.condition') }}</label>
                        <select v-model="npcForm.spawn_condition.type" class="form-input">
                            <option value="on_enter">{{ t('scenes.on_scene_enter') }}</option>
                            <option value="flag">{{ t('scenes.if_flag_true') }}</option>
                            <option value="time">{{ t('scenes.after_time') }}</option>
                        </select>
                    </div>
                </div>


                <div v-if="npcForm.spawn_condition.type === 'flag'">
                    <label class="form-label">{{ t('scenes.flag_name') }}</label>
                    <input v-model="npcForm.spawn_condition.flag" type="text" placeholder="b.v. bartender_alerted" class="form-input">
                </div>
            </form>
        </Modal>
    </div>
</template>
