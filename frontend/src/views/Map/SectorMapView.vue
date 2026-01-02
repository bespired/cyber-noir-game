<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import axios from '../../axios';
import { useToast } from '../../composables/useToast';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const route = useRoute();
const sector = ref(null);
const scenes = ref([]);
const loading = ref(true);
const mapContainer = ref(null);
const dragging = ref(null);
const mapBackground = ref('');

// Constants for the native map size (matching GlobalMapView for consistency)
const NATIVE_WIDTH = 1536;
const NATIVE_HEIGHT = 1024;

// Reactive state for the actual display dimensions and offsets
const mapGeometry = ref({
    width: 0,
    height: 0,
    scale: 1,
    offsetX: 0,
    offsetY: 0
});

onMounted(async () => {
    await fetchSectorData();
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

const fetchSectorData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/sectoren/${route.params.id}`);
        sector.value = response.data;
        scenes.value = response.data.scenes || [];

        if (sector.value.artwork && sector.value.artwork.length > 0) {
            mapBackground.value = getImageUrl(sector.value.artwork[0].bestandspad);
        } else {
            mapBackground.value = '/map-noir.png';
        }
    } catch (e) {
        console.error("Failed to fetch sector data", e);
    } finally {
        loading.value = false;
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
        displayHeight = containerHeight;
        displayWidth = containerHeight * imageRatio;
    } else {
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

const onMouseDown = (event, scene) => {
    event.preventDefault();
    dragging.value = {
        scene: scene,
        startX: event.clientX,
        startY: event.clientY,
        initialX: scene.data?.x ?? 100,
        initialY: scene.data?.y ?? 100
    };
};

const onMouseMove = (event) => {
    if (!dragging.value) return;

    const dx = (event.clientX - dragging.value.startX) / mapGeometry.value.scale;
    const dy = (event.clientY - dragging.value.startY) / mapGeometry.value.scale;

    if (!dragging.value.scene.data) dragging.value.scene.data = {};

    dragging.value.scene.data.x = Math.round(Math.max(0, dragging.value.initialX + dx));
    dragging.value.scene.data.y = Math.round(Math.max(0, dragging.value.initialY + dy));
};

const onMouseUp = async () => {
    if (!dragging.value) return;

    const scene = dragging.value.scene;
    dragging.value = null;

    try {
        await axios.put(`/api/scenes/${scene.id}`, {
            titel: scene.titel,
            locatie_id: scene.locatie_id,
            sector_id: scene.sector_id,
            type: scene.type,
            beschrijving: scene.beschrijving,
            status: scene.status,
            gateways: scene.gateways,
            data: scene.data // Save the data object
        });
    } catch (e) {
        console.error("Failed to save scene position", e);
    }
};

const getImageUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/storage')) return `http://localhost:8000${path}`;
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    return `http://localhost:8000/storage${cleanPath}`;
};

const getSceneArtwork = (scene) => {
    if (scene.artwork && scene.artwork.length > 0) {
        return scene.artwork;
    }
    if (scene.locatie?.artwork && scene.locatie.artwork.length > 0) {
        return scene.locatie.artwork;
    }
    return [];
};

// Connections (Wires) Logic
const connections = computed(() => {
    const list = [];
    scenes.value.forEach(sourceScene => {
        if (!sourceScene.gateways) return;

        sourceScene.gateways.forEach(gw => {
            const targetScene = scenes.value.find(s => s.id === gw.target_scene_id);
            if (targetScene) {
                list.push({
                    id: `${sourceScene.id}-${targetScene.id}`,
                    source: sourceScene,
                    target: targetScene
                });
            }
        });
    });
    return list;
});

const getLineCoords = (conn) => {
    const s = conn.source;
    const t = conn.target;

    // Internal coordinates
    const sx = (s.data?.x ?? 100) + (s.data?.width ?? 200); // Right edge
    const sy = (s.data?.y ?? 100) + (s.data?.height ?? 150) / 2; // Middle Y
    const tx = (t.data?.x ?? 100); // Left edge
    const ty = (t.data?.y ?? 100) + (t.data?.height ?? 150) / 2; // Middle Y

    // Convert to screen coordinates
    return {
        x1: mapGeometry.value.offsetX + sx * mapGeometry.value.scale,
        y1: mapGeometry.value.offsetY + sy * mapGeometry.value.scale,
        x2: mapGeometry.value.offsetX + tx * mapGeometry.value.scale,
        y2: mapGeometry.value.offsetY + ty * mapGeometry.value.scale
    };
};

const getLinePath = (conn) => {
    const { x1, y1, x2, y2 } = getLineCoords(conn);
    const dx = Math.abs(x2 - x1);

    // Control points: pull out horizontally from dots
    // If target is to the left, we still pull "out" from right and "in" to left
    const offset = Math.max(80 * mapGeometry.value.scale, dx * 0.5);
    const cx1 = x1 + offset;
    const cy1 = y1;
    const cx2 = x2 - offset;
    const cy2 = y2;

    return `M ${x1} ${y1} C ${cx1} ${cy1}, ${cx2} ${cy2}, ${x2} ${y2}`;
};

const getLineColor = (conn) => {
    const s = conn.source;
    const t = conn.target;
    // Determine "Back" connection if target is significantly to the left of source
    if ((t.data?.x ?? 0) < (s.data?.x ?? 0) - 50) {
        return {
            stroke: 'rgba(255, 60, 150, 0.5)', // Neon Pink/Magenta for "Back"
            filter: 'glow-back'
        };
    }
    return {
        stroke: 'rgba(0, 180, 255, 0.5)', // Neon Blue/Cyan for "Forward"
        filter: 'glow-forward'
    };
};
</script>

<template>
    <div class="h-[calc(100vh-4rem)] flex flex-col bg-black">
        <!-- Toolbar -->
        <div class="h-16 bg-noir-dark border-b border-noir-panel px-6 flex justify-between items-center z-30">
            <div class="flex items-center gap-4">
                <RouterLink to="/map" class="text-noir-muted hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </RouterLink>
                <h1 v-if="sector" class="text-2xl font-bold text-white tracking-tight uppercase">
                    SECTOR: {{ sector.naam }}
                </h1>
            </div>
            <div class="flex gap-4">
                <span class="text-xs text-noir-muted self-center font-mono uppercase">
                    DRAG SCENES | CONNECTIES KOMEN UIT GATEWAYS
                </span>
            </div>
        </div>

        <!-- Map Canvas -->
        <div v-if="loading" class="flex-grow flex items-center justify-center text-noir-muted font-mono animate-pulse">
        {{ t('map.loading_sector_matrix') }}
    </div>

    <div v-else class="flex-grow relative overflow-hidden bg-noir-darker" ref="mapContainer"> 
        
        <!-- Grid Background -->
        <!-- The original code did not have a grid background, so this line is not added based on the diff. -->

        <!-- Toolbar -->
        <div class="absolute top-4 left-4 z-20 flex items-center gap-4 bg-noir-dark p-2 rounded border border-noir-border">
             <RouterLink :to="`/map/${sector?.id || ''}`" class="text-white hover:text-noir-accent transition-colors font-bold uppercase text-xs flex items-center gap-1">
                &lt; {{ t('map.back') }}
            </RouterLink>
            <div class="h-4 w-px bg-noir-border"></div>
            <h2 class="text-white font-bold uppercase tracking-wider text-sm">{{ sector?.naam }}</h2>
             <div class="h-4 w-px bg-noir-border"></div>
             <span class="text-xs text-noir-muted self-center font-mono uppercase">
                {{ t('map.drag_scenes_hint') }}
            </span>
        </div>

            <!-- Map Background -->
            <div class="absolute inset-0 pointer-events-none opacity-40"
                 :style="{
                    backgroundImage: `url(${mapBackground})`,
                    backgroundSize: 'contain',
                    backgroundPosition: 'center',
                    backgroundRepeat: 'no-repeat',
                    filter: 'blur(7px)'
                 }"
            >
            </div>

            <!-- SVG Overlay for Wires -->
            <svg class="absolute inset-0 pointer-events-none w-full h-full z-10">
                <defs>
                    <filter id="glow-forward">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    <filter id="glow-back">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                <path v-for="conn in connections" :key="conn.id"
                      :d="getLinePath(conn)"
                      :stroke="getLineColor(conn).stroke"
                      stroke-width="2"
                      stroke-dasharray="8,6"
                      fill="none"
                      :filter="`url(#${getLineColor(conn).filter})`"
                />
            </svg>

            <!-- Scenes -->
            <div v-for="scene in scenes" :key="scene.id"
                 @mousedown="onMouseDown($event, scene)"
                 :style="{
                     left: `${mapGeometry.offsetX + ((scene.data?.x ?? 100) * mapGeometry.scale)}px`,
                     top: `${mapGeometry.offsetY + ((scene.data?.y ?? 100) * mapGeometry.scale)}px`,
                     width: `${(scene.data?.width ?? 200) * mapGeometry.scale}px`,
                     height: `${(scene.data?.height ?? 150) * mapGeometry.scale}px`
                 }"
                 class="absolute bg-noir-panel/90 border border-noir-dark group hover:border-noir-accent z-20 hover:z-50 shadow-2xl flex flex-col select-none transition-shadow backdrop-blur-sm"
                 :class="{ 'border-noir-accent z-50 ring-2 ring-noir-accent/50': dragging?.scene?.id === scene.id }">

                <!-- Scene Mini Preview -->
                <div class="absolute inset-0 z-0 opacity-20 group-hover:opacity-40 transition-opacity overflow-hidden">
                     <img v-if="getSceneArtwork(scene).length > 0"
                          :src="getImageUrl(getSceneArtwork(scene)[0].bestandspad)"
                          class="w-full h-full object-cover pointer-events-none">
                     <!-- Placeholder Visual if no image -->
            <div v-if="!scene.achtergrond_afbeelding" class="w-full h-full flex items-center justify-center bg-noir-dark">
                <span class="text-xs text-noir-muted italic">{{ t('map.no_visual') }}</span>
            </div>
         </div>

                <!-- Overlay Info -->
                <div class="relative z-10 p-3 flex flex-col h-full justify-between border-t border-white/5">
                    <div class="flex justify-between items-start">
                        <span class="text-[9px] bg-black/60 px-1.5 py-0.5 rounded text-noir-accent font-mono border border-noir-accent/20">
                            {{ scene.type.toUpperCase() }}
                        </span>
                        <div :class="['w-2 h-2 rounded-full shadow-[0_0_5px]',
                             scene.status === 'active' ? 'bg-noir-success shadow-noir-success/50' :
                             scene.status === 'completed' ? 'bg-noir-muted shadow-noir-muted/50' :
                             'bg-noir-danger shadow-noir-danger/50']">
                        </div>
                    </div>

                    <div class="mt-2">
                        <RouterLink :to="`/scenes/${scene.id}`" @mousedown.stop class="text-white font-bold hover:text-noir-accent transition-colors uppercase text-xs tracking-wider line-clamp-2">
                            {{ scene.titel }}
                        </RouterLink>
                        <div class="text-[10px] text-noir-muted mt-1 font-mono">
                            ID: {{ String(scene.id).padStart(4, '0') }}
                        </div>
                    </div>
                </div>

                <!-- Connection Points (Visual Only) -->
                <div class="absolute -left-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-noir-dark border border-noir-accent/30 rounded-full z-20"></div>
                <div class="absolute -right-1.5 top-1/2 -translate-y-1/2 w-3 h-3 bg-noir-dark border border-noir-accent/30 rounded-full z-20"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
}

@keyframes dash {
    to {
        stroke-dashoffset: -20;
    }
}

svg path {
    animation: dash 8s linear infinite;
}
</style>
