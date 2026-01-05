<script setup>
import { ref, onMounted, shallowRef, defineAsyncComponent, computed } from 'vue'
import { gameDataService } from '@/services/GameDataService'

import { sectorData } from '@/sectordata.js';

const message = ref('Cyber Noir Engine')
const currentScene = ref(null)
const activeComponent = shallowRef(null)
const loading = ref(true)
const eventLog = ref([])
const isDebug = computed(() => import.meta.env.VITE_DEBUG === 'true')

// Track sectors where the vehicle has already landed
const landedSectors = ref(new Set(JSON.parse(localStorage.getItem('landedSectors') || '[]')));

const saveLandedSectors = () => {
    localStorage.setItem('landedSectors', JSON.stringify([...landedSectors.value]));
};

const transientProps = ref({});

const sceneProps = computed(() => {
    if (!currentScene.value) return {};
    // Merge root fields, data fields, flattened data.props, and transient props
    const flattened = {
        ...currentScene.value,
        ...(currentScene.value.data || {}),
        ...(currentScene.value.data?.props || {}),
        ...transientProps.value,
        debug: isDebug.value,
        isLanded: landedSectors.value.has(currentScene.value?.sector_id)
    };
    console.log("[DEBUG] Scene Props for", currentScene.value.id, flattened);
    return flattened;
});

// Scan all components in the game_scenes directory
const availableScenes = import.meta.glob('./components/game_scenes/*.vue');

const loadScene = async (sceneId, extraProps = {}) => {
    console.log("[DEBUG] Loading scene:", sceneId);
    loading.value = true
    transientProps.value = extraProps;
    const scene = await gameDataService.getSceneById(sceneId)

    if (scene) {
        currentScene.value = scene
        console.log("[DEBUG] Scene data loaded:", scene);

        // Determine which component to show
        if (scene.type === 'vue-component') {
            // Check for component name in both formats
            const compName = scene.data?.componentName || scene.data?.component || 'DemoTitleScene';
            const path = `./components/game_scenes/${compName}.vue`;

            if (availableScenes[path]) {
                activeComponent.value = defineAsyncComponent(availableScenes[path]);
                console.log("[DEBUG] Component bound:", compName);
            } else {
                console.warn(`Scene component ${compName} not found at ${path}`);
            }
        } else if (scene.type === 'walkable-area') {
            // Use specialized 3D engine component
            const path = `./components/game_scenes/WalkableAreaScene.vue`;

            if (availableScenes[path]) {
                activeComponent.value = defineAsyncComponent(availableScenes[path]);
                console.log("[DEBUG] 3D Engine bound");
            } else {
                console.warn(`WalkableAreaScene component not found at ${path}`);
            }
        } else {
            console.warn(`Unknown scene type: ${scene.type}`)
        }
    } else {
        console.error("[DEBUG] Scene", sceneId, "not found in data.");
    }

    loading.value = false
}

const onSceneComplete = (data) => {
    console.log("[ENGINE] Scene complete event received:", data)
    eventLog.value.unshift({
        time: new Date().toLocaleTimeString(),
        data
    });

    if (data.landed && currentScene.value?.sector_id) {
        console.log("[ENGINE] Marking sector as landed:", currentScene.value.sector_id);
        landedSectors.value.add(currentScene.value.sector_id);
        saveLandedSectors();
    }

    if (data.targetSceneId) {
        loadScene(data.targetSceneId, { targetSpawnPoint: data.targetSpawnPoint })
    } else if (!data.landed) {
        console.warn("[ENGINE] No targetSceneId provided in complete event.");
    }
}

onMounted(async () => {
    console.log("[ENGINE] Initializing...");

    console.log( sectorData )

    const settings = await gameDataService.loadSettings()
    if (settings && settings.opening_scene) {
        await loadScene(settings.opening_scene)
    } else {
        console.warn("[ENGINE] No opening_scene found in settings.");
        loading.value = false
    }
})
</script>

<template>
  <div class="game-container">
    <div v-if="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p>INITIALIZING_SYSTEM...</p>
    </div>

    <component
        v-if="activeComponent"
        :is="activeComponent"
        v-bind="sceneProps"
        @scene-complete="onSceneComplete"
    />

    <div v-else-if="!loading" class="no-scene">
        <h1>{{ message }}</h1>
        <p>No active scene (ID: {{ currentScene?.id }}). Check settings.json or data exports.</p>
    </div>

    <!-- Debug Log Overlay -->
    <div v-if="isDebug" class="debug-overlay">
        <div class="debug-header">SYSTEM_LOG</div>
        <div v-for="(log, idx) in eventLog" :key="idx" class="debug-entry">
            <span class="debug-time">[{{ log.time }}]</span>
            <span class="debug-msg">SWAP -> {{ JSON.stringify(log.data) }}</span>
        </div>
        <div v-if="eventLog.length === 0" class="debug-empty">WAITING_FOR_EVENTS...</div>
    </div>
  </div>
</template>

<style>
/* Global game styles can also be put here, although we now use game.css */
.game-container {
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    position: relative;
    background-color: #000;
}

.loading-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    background: #000;
    color: #00ffcc;
    font-family: 'Courier New', Courier, monospace;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(0, 255, 204, 0.1);
    border-top: 4px solid #00ffcc;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.debug-overlay {
    position: absolute;
    top: 10px;
    right: 17px;
    width: 35%;
    max-height: 200px;
    background: rgba(0, 10, 10, 0.9);
    border: 1px solid #00ffcc;
    padding: 12px;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    color: #00ffcc;
    z-index: 10000;
    /*pointer-events: none;*/
    overflow-y: auto;
    box-shadow: 0 0 20px rgba(0, 255, 204, 0.2);
    text-transform: uppercase;
    opacity: 0.5;
}

.debug-header {
    border-bottom: 2px solid #00ffcc;
    margin-bottom: 8px;
    padding-bottom: 4px;
    font-weight: 900;
    letter-spacing: 2px;
    font-size: 12px;
}

.debug-entry {
    margin-bottom: 6px;
    line-height: 1.4;
    border-left: 2px solid rgba(0, 255, 204, 0.3);
    padding-left: 8px;
}

.debug-time {
    color: rgba(0, 255, 204, 0.5);
    margin-right: 8px;
    font-size: 9px;
}

.debug-msg {
    word-break: break-all;
}

.debug-empty {
    color: rgba(0, 255, 204, 0.3);
    font-style: italic;
    text-align: center;
    padding: 10px;
}
</style>
