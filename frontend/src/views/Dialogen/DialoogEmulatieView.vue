<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../axios';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { useToast } from '../../composables/useToast';
import { useDataRobustness } from '../../composables/useDataRobustness';

const { resolveAssetUrl, getCharacterGlbUrl } = useDataRobustness();

const toast = useToast();
const route = useRoute();
const router = useRouter();
const dialoogId = route.params.id;

// Data refs
const dialoogData = ref(null);
const settings = ref({});
const loading = ref(true);
const error = ref(null);

// Three.js refs
const canvasContainer = ref(null);
let renderer, scene, camera, clock;
let ambientLight, sun;
let npcCharacter = null;
let playableCharacter = null;
let animationFrameId = null;
let npcMixer = null;
let pcMixer = null;

// Dialogue Flow refs
const currentNodeId = ref('root');
const typewriterText = ref('');
const showOptions = ref(false);
let typewriterInterval = null;

const currentNode = computed(() => {
    if (!dialoogData.value || !dialoogData.value.tree) return null;
    return dialoogData.value.tree.nodes[currentNodeId.value];
});

// slugify is now imported from useDataRobustness

const getImageUrl = (path) => {
    return resolveAssetUrl(path);
};

// getCharacterGlbUrl is now imported from useDataRobustness

onMounted(async () => {
    await fetchData();
    initThree();
    await loadCharacters();
    startDialogue();
});

onUnmounted(() => {
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
    clearInterval(typewriterInterval);
});

const fetchData = async () => {
    try {
        const [dialoogRes, settingsRes] = await Promise.all([
            axios.get(`/api/dialogen/${dialoogId}`),
            axios.get('/api/instellingen')
        ]);
        dialoogData.value = dialoogRes.data;
        settings.value = settingsRes.data;
    } catch (e) {
        console.error("Failed to fetch data", e);
        error.value = "Failed to load dialogue data.";
    } finally {
        loading.value = false;
    }
};

const initThree = () => {
    if (!canvasContainer.value) return;

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x050505);

    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    canvasContainer.value.appendChild(renderer.domElement);

    camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 1.6, 3);
    camera.lookAt(0, 1.2, 0);

    clock = new THREE.Clock();

    ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
    scene.add(ambientLight);

    sun = new THREE.DirectionalLight(0xffffff, 0.8);
    sun.position.set(5, 5, 5);
    sun.castShadow = true;
    scene.add(sun);

    // Rim light for that noir feel
    const rimLight = new THREE.PointLight(0x00aaff, 1, 10);
    rimLight.position.set(-2, 2, -2);
    scene.add(rimLight);

    animate();
};

const loadCharacters = async () => {
    const loader = new GLTFLoader();

    // 1. Load NPC
    if (dialoogData.value?.personage?.naam) {
        try {
            const npcUrl = getCharacterGlbUrl(dialoogData.value.personage.naam);
            const npcGltf = await new Promise((res, rej) => loader.load(npcUrl, res, undefined, rej));
            npcCharacter = npcGltf.scene;
            npcCharacter.position.set(-0.8, 0, 0);
            npcCharacter.rotation.y = Math.PI * 0.15;
            scene.add(npcCharacter);
            
            if (npcGltf.animations?.length) {
                npcMixer = new THREE.AnimationMixer(npcCharacter);
                const idle = npcGltf.animations.find(a => a.name.toLowerCase().includes('idle'));
                if (idle) npcMixer.clipAction(idle).play();
            }
            adjustScale(npcCharacter);
        } catch (e) {
            console.warn("GLB load failed for NPC, using artwork fallback:", dialoogData.value.personage.naam);
            if (dialoogData.value.personage.artwork?.length) {
                spawnSpriteFallback(dialoogData.value.personage.artwork[0].bestandspad, -0.8);
            }
        }
    }

    // 2. Load Playable Character
    if (settings.value.playable) {
        try {
            const pcUrl = getCharacterGlbUrl(settings.value.playable);
            const pcGltf = await new Promise((res, rej) => loader.load(pcUrl, res, undefined, rej));
            playableCharacter = pcGltf.scene;
            playableCharacter.position.set(0.8, 0, 0);
            playableCharacter.rotation.y = -Math.PI * 0.15;
            scene.add(playableCharacter);
            
            if (pcGltf.animations?.length) {
                pcMixer = new THREE.AnimationMixer(playableCharacter);
                const idle = pcGltf.animations.find(a => a.name.toLowerCase().includes('idle'));
                if (idle) pcMixer.clipAction(idle).play();
            }
            adjustScale(playableCharacter);
        } catch (e) {
            console.warn("GLB load failed for PC, using artwork fallback:", settings.value.playable);
            // We need to fetch PC info to get its artwork
            try {
                const pcRes = await axios.get(`/api/personages?naam=${encodeURIComponent(settings.value.playable)}`);
                const pcData = pcRes.data.find(p => p.naam === settings.value.playable);
                if (pcData?.artwork?.length) {
                     spawnSpriteFallback(pcData.artwork[0].bestandspad, 0.8);
                }
            } catch (err) {
                console.error("Failed to fetch PC artwork for fallback", err);
            }
        }
    }
};

const spawnSpriteFallback = (imagePath, xPos) => {
    const textureLoader = new THREE.TextureLoader();
    const map = textureLoader.load(getImageUrl(imagePath), (texture) => {
        const aspect = texture.image.width / texture.image.height;
        sprite.scale.set(1.8 * aspect, 1.8, 1);
    });
    const material = new THREE.SpriteMaterial({ map: map, transparent: true });
    const sprite = new THREE.Sprite(material);
    
    // Position it at head/chest height
    sprite.position.set(xPos, 1.2, 0);
    
    scene.add(sprite);
    return sprite;
};

const adjustScale = (model) => {
    const bbox = new THREE.Box3().setFromObject(model);
    const size = new THREE.Vector3();
    bbox.getSize(size);
    if (size.y > 20) {
        model.scale.set(0.01, 0.01, 0.01);
    } else if (size.y < 0.5) {
        model.scale.set(1, 1, 1);
    }
    
    // Ensure cast shadows
    model.traverse(child => {
        if (child.isMesh) child.castShadow = true;
    });
};

const animate = () => {
    animationFrameId = requestAnimationFrame(animate);
    const delta = clock ? clock.getDelta() : 0.016;
    if (npcMixer) npcMixer.update(delta);
    if (pcMixer) pcMixer.update(delta);
    renderer.render(scene, camera);
};

const startDialogue = () => {
    currentNodeId.value = 'root';
    playText(currentNode.value?.text || '');
};

const playText = (text) => {
    showOptions.value = false;
    typewriterText.value = '';
    let i = 0;
    clearInterval(typewriterInterval);
    typewriterInterval = setInterval(() => {
        if (i < text.length) {
            typewriterText.value += text[i];
            i++;
        } else {
            clearInterval(typewriterInterval);
            showOptions.value = true;
        }
    }, 30);
};

const selectOption = (option) => {
    if (option.actions) {
        // Handle actions like END TALK
        const endAction = option.actions.find(a => a.type === 'END TALK');
        if (endAction) {
            router.push('/dialogen');
            return;
        }
    }

    if (option.next) {
        currentNodeId.value = option.next;
        playText(currentNode.value.text);
    }
};

</script>

<template>
    <div class="h-screen bg-black overflow-hidden relative">
        <div ref="canvasContainer" class="absolute inset-0"></div>

        <!-- Dialogue UI Overlay -->
        <div class="absolute inset-x-0 top-[33%] p-8 flex flex-col items-center pointer-events-none">



            <!-- NPC Text Box -->
            <transition name="fade">
                <div v-if="typewriterText" class="max-w-2xl w-full bg-noir-panel/80 backdrop-blur-md border border-noir-accent/30 p-6 rounded-lg mb-8 pointer-events-auto">
                    <div class="text-xs font-mono text-noir-accent mb-2 uppercase tracking-widest">
                        {{ dialoogData?.personage?.naam || 'UNKNOWN_CONTACT' }}
                    </div>
                    <div class="text-white text-lg leading-relaxed font-light italic">
                        {{ typewriterText }}
                    </div>

                </div>
            </transition>

            <!-- PC Options -->
            <transition name="slide-up">
                <div v-if="showOptions && currentNode?.options?.length" class="flex flex-col gap-3 w-full max-w-xl pointer-events-auto">
                    <button 
                        v-for="(option, idx) in currentNode.options" 
                        :key="idx"
                        @click="selectOption(option)"
                        class="w-full bg-black/60 hover:bg-noir-accent/20 hover:text-white border border-noir-accent/30 hover:border-noir-accent text-noir-accent px-6 py-3 rounded text-left transition-all duration-300 group flex justify-between items-center hover:shadow-[0_0_15px_rgba(0,180,255,0.2)]"
                    >
                        <span class="text-sm font-medium uppercase tracking-wider">{{ option.text }}</span>
                        <span class="text-[10px] opacity-0 group-hover:opacity-100 transition-opacity font-mono">SELECT_RESPONSE >></span>
                    </button>
                </div>
            </transition>
        </div>

        <!-- Back Button -->
        <button 
            @click="router.back()" 
            class="absolute top-8 left-8 bg-black/40 hover:bg-noir-accent border border-white/10 text-white hover:text-black px-4 py-2 rounded text-xs font-mono transition-all z-50 uppercase tracking-widest"
        >
            &lt; ABORT_EMULATION
        </button>

        <!-- Loading Overlay -->
        <div v-if="loading" class="absolute inset-0 bg-black flex items-center justify-center z-[100]">
            <div class="text-noir-accent font-mono animate-pulse tracking-[0.5em] text-xl uppercase">
                Synchronizing_Neuro_Link...
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from {
    opacity: 0;
    transform: translateY(20px);
}
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.bg-noir-panel {
    background-color: #0a0a0a;
}
.bg-noir-dark {
    background-color: #050505;
}
.text-noir-accent {
    color: #00b4ff;
}
.border-noir-accent\/30 {
    border-color: rgba(0, 180, 255, 0.3);
}
.border-noir-accent\/50 {
    border-color: rgba(0, 180, 255, 0.5);
}
.hover\:bg-noir-accent:hover {
    background-color: #00b4ff;
    box-shadow: 0 0 20px rgba(0, 180, 255, 0.3);
}
</style>
