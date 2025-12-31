<script setup>
import { ref, onMounted, defineAsyncComponent, shallowRef } from 'vue';
import { useRoute } from 'vue-router';
import axios from '../../axios';
import DemoTitleScene from '../../components/game_scenes/DemoTitleScene.vue'; // Direct import for fallback/demo

const route = useRoute();
const scene = ref(null);
const loading = ref(true);
const currentComponent = shallowRef(null);
const eventLog = ref([]);

// Registry of available game scenes
// In a real app, this might be auto-imported or more dynamic
const componentRegistry = {
    'DemoTitleScene': DemoTitleScene
};

onMounted(async () => {
    try {
        const response = await axios.get(`/api/scenes/${route.params.id}`);
        scene.value = response.data;
        
        if (scene.value.type === 'vue-component' && scene.value.data?.component) {
            const compName = scene.value.data.component;
            
            if (componentRegistry[compName]) {
                currentComponent.value = componentRegistry[compName];
            } else {
                console.warn(`Component ${compName} not found in registry.`);
                // Try dynamic import if strictly needed, but registry is safer for explicit control
            }
        }
    } catch (e) {
        console.error("Failed to load scene", e);
    } finally {
        loading.value = false;
    }
});

const handleSceneComplete = (payload) => {
    eventLog.value.unshift({
        time: new Date().toLocaleTimeString(),
        event: 'scene-complete',
        payload
    });
};
</script>

<template>
    <div class="w-full h-screen bg-black flex flex-col relative overflow-hidden">
        
        <!-- Top Bar -->
        <div class="h-12 bg-noir-darker border-b border-noir-dark flex justify-between items-center px-4 z-50">
            <div class="flex items-center gap-4">
                <RouterLink :to="`/scenes/${route.params.id}`" class="text-noir-muted hover:text-white text-xs uppercase font-bold tracking-wider">
                    &lt; Terug naar Config
                </RouterLink>
                <div v-if="scene" class="text-white font-mono text-sm">
                    EMULATION_MODE: <span class="text-noir-accent">{{ scene.data?.component || 'UNKNOWN' }}</span>
                </div>
            </div>
            <div class="text-[10px] text-noir-muted">
                {{ scene?.titel }}
            </div>
        </div>

        <!-- Component Stage -->
        <div class="flex-1 relative flex items-center justify-center bg-gray-900">
            <div v-if="loading" class="text-noir-accent animate-pulse">LADEN...</div>
            
            <component 
                v-else-if="currentComponent" 
                :is="currentComponent"
                v-bind="scene.data.props"
                @scene-complete="handleSceneComplete"
                class="w-full h-full"
            />
            
            <div v-else class="text-center p-8 border-2 border-noir-danger text-noir-danger">
                <h2 class="text-xl font-bold mb-2">COMPONENT NOT FOUND</h2>
                <p>Could not load component: "{{ scene?.data?.component }}"</p>
                <p class="text-xs mt-4 text-white">Available: {{ Object.keys(componentRegistry).join(', ') }}</p>
            </div>
        </div>

        <!-- Debug Log Overlay -->
        <div class="absolute bottom-4 right-4 w-64 max-h-48 bg-black/80 border border-noir-dark p-2 rounded text-[10px] font-mono overflow-y-auto z-50 pointer-events-none">
            <div class="text-noir-muted border-b border-noir-dark mb-1 pb-1">EVENT LOG</div>
            <div v-for="(log, i) in eventLog" :key="i" class="mb-1">
                <span class="text-gray-500">[{{ log.time }}]</span>
                <span class="text-noir-accent font-bold"> {{ log.event }}</span>
                <span class="text-gray-300 block pl-2">{{ JSON.stringify(log.payload) }}</span>
            </div>
            <div v-if="eventLog.length === 0" class="text-gray-600 italic">Waiting for events...</div>
        </div>

    </div>
</template>
