<script setup>
import { ref, onMounted, shallowRef, defineAsyncComponent } from 'vue'
import { gameDataService } from '@/services/GameDataService'

const message = ref('Cyber Noir Engine')
const currentScene = ref(null)
const activeComponent = shallowRef(null)
const loading = ref(true)

// Scan all components in the game_scenes directory
const availableScenes = import.meta.glob('./components/game_scenes/*.vue');

const loadScene = async (sceneId) => {
    loading.value = true
    const scene = await gameDataService.getSceneById(sceneId)
    
    if (scene) {
        currentScene.value = scene
        
        // Determine which component to show
        if (scene.type === 'vue-component') {
            // Check for component name in both formats
            const compName = scene.data?.componentName || scene.data?.component || 'DemoTitleScene';
            const path = `./components/game_scenes/${compName}.vue`;

            if (availableScenes[path]) {
                activeComponent.value = defineAsyncComponent(availableScenes[path]);
            } else {
                console.warn(`Scene component ${compName} not found at ${path}`);
            }
        } else if (scene.type === 'walkable-area') {
            // Placeholder for WalkableAreaScene
            console.log("Walkable Area scene detected, logic to be implemented")
        } else {
            console.warn(`Unknown scene type: ${scene.type}`)
        }
    }
    
    loading.value = false
}

const onSceneComplete = (data) => {
    console.log("Scene complete:", data)
    if (data.targetSceneId) {
        loadScene(data.targetSceneId)
    }
}

onMounted(async () => {
    const settings = await gameDataService.loadSettings()
    if (settings && settings.opening_scene) {
        await loadScene(settings.opening_scene)
    } else {
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
        v-bind="currentScene.data"
        @scene-complete="onSceneComplete"
    />

    <div v-else-if="!loading" class="no-scene">
        <h1>{{ message }}</h1>
        <p>No active scene. Check settings.json or data exports.</p>
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
</style>
