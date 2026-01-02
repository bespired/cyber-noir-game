<script setup>
import { ref, onMounted, onUnmounted, computed, reactive, watch, defineProps, defineEmits } from 'vue';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { useDataRobustness } from '../../composables/useDataRobustness';

const props = defineProps({
    id: { type: [String, Number], required: true },
    sectorId: { type: [String, Number], default: null },
    sector_id: { type: [String, Number], default: null }
});

const effectiveSectorId = computed(() => props.sectorId || props.sector_id);

const emit = defineEmits(['scene-complete']);

const { fetchData: fetchRobustData, resolveAssetUrl, slugify, getCharacterGlbUrl } = useDataRobustness();

// Data refs
const currentScene = ref(null);
const sectorData = ref(null);
const settings = ref({});
const gedragingen = ref([]);
const loading = ref(true);
const error = ref(null);

// Dialogue System
const activeDialogue = ref(null);
const currentNodeId = ref('start');
const typewriterText = ref('');
const showDialogueOptions = ref(false);
const typewriterInterval = ref(null);
const dialogueNPCName = ref('');
let dialogueResolve = null;

// Three.js refs
const canvasContainer = ref(null);
let renderer, scene, camera, clock;
let ambientLight, sun;
let currentGltf = null;
let animationFrameId = null;
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();

// Movement / Character refs
let playableCharacter = null;
let vehicle = null;
const isWalking = ref(false);
const targetPosition = new THREE.Vector3();
const characterSpeed = 2.0;
const landingDone = ref(false);
let targetPointMesh = null;
const spawnedNPCs = reactive({});
const npcModes = reactive({}); // 'IDLE', 'SEQUENCE'
const isBehaviorActive = ref(false);
const lastTriggeredGateway = ref(null);
const lastExecutedBehaviorGateway = ref(null);
const pendingGateway = ref(null);

// Animation refs
let characterMixer = null;
const characterActions = {};
let activeAction = null;

// Layout
const VIEW_WIDTH = 1216;
const VIEW_HEIGHT = 832;
const ASPECT_RATIO = VIEW_WIDTH / VIEW_HEIGHT;
const containerWidth = ref(VIEW_WIDTH);
const containerHeight = ref(VIEW_HEIGHT);

const updateDimensions = () => {
    const parent = canvasContainer.value?.parentElement;
    if (!parent) return;
    
    const maxWidth = parent.clientWidth;
    const maxHeight = parent.clientHeight || window.innerHeight - 200;

    let newWidth = maxWidth;
    let newHeight = newWidth / ASPECT_RATIO;

    if (newHeight > maxHeight) {
        newHeight = maxHeight;
        newWidth = newHeight * ASPECT_RATIO;
    }

    containerWidth.value = Math.floor(newWidth);
    containerHeight.value = Math.floor(newHeight);

    if (renderer && camera) {
        camera.aspect = ASPECT_RATIO;
        camera.updateProjectionMatrix();
        renderer.setSize(containerWidth.value, containerHeight.value);
    }
};

onMounted(async () => {
    console.log("[WalkableAreaScene] Mounting scene:", props.id);
    await loadInitialData();
    window.addEventListener('resize', updateDimensions);
    updateDimensions();
    initThree();
    if (currentScene.value) {
        await loadSceneGLB(currentScene.value);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', updateDimensions);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (renderer) renderer.dispose();
    if (typewriterInterval.value) clearInterval(typewriterInterval.value);
});

const loadInitialData = async () => {
    try {
        loading.value = true;
        
        // Load settings
        settings.value = await fetchRobustData('settings.json', 'settings') || {};
        
        // Load scenes and find the one we need
        const scenes = await fetchRobustData('scenes.json', 'scenes');
        currentScene.value = scenes.find(s => String(s.id) === String(props.id));
        
        if (!currentScene.value) {
            throw new Error(`Scene ${props.id} not found`);
        }

        // Load sector if not provided
        const curSectorId = effectiveSectorId.value || currentScene.value.sector_id;
        const sectors = await fetchRobustData('sectors.json', 'sectors');
        sectorData.value = sectors.find(s => String(s.id) === String(curSectorId));

        // Load behaviors
        gedragingen.value = await fetchRobustData('dialogues.json', 'gedrag') || [];

        // Load dialogues
        // ... (can be added if needed)

    } catch (e) {
        console.error("[WalkableAreaScene] Load Error:", e);
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

const initThree = () => {
    if (!canvasContainer.value) return;

    scene = new THREE.Scene();
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(containerWidth.value, containerHeight.value);
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    canvasContainer.value.appendChild(renderer.domElement);

    camera = new THREE.PerspectiveCamera(45, ASPECT_RATIO, 0.1, 1000);
    camera.position.set(5, 5, 5);
    camera.lookAt(0, 0, 0);

    clock = new THREE.Clock();

    ambientLight = new THREE.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);

    sun = new THREE.DirectionalLight(0xffffff, 1.0);
    sun.position.set(5, 10, 5);
    sun.castShadow = true;
    scene.add(sun);

    // Target Marker
    const markerGeom = new THREE.RingGeometry(0.2, 0.25, 32);
    markerGeom.rotateX(-Math.PI / 2);
    targetPointMesh = new THREE.Mesh(markerGeom, new THREE.MeshBasicMaterial({ color: 0x00ffff, transparent: true, opacity: 0.8 }));
    targetPointMesh.userData.isHelper = true;
    targetPointMesh.visible = false;
    scene.add(targetPointMesh);

    animate();
};

const getSceneGlbUrl = (location, sector) => {
    if (!location || !sector) return '';
    const sectorSlug = slugify(sector.naam);
    const locationSlug = slugify(location.naam);
    return resolveAssetUrl(`glb/${sectorSlug}--${locationSlug}.glb`);
};

const loadSceneGLB = (sceneData, targetSpawnLabel = null) => {
    return new Promise((resolve) => {
        if (!sceneData || !sceneData.locatie_id) {
            resolve();
            return;
        }

        // Fetch location data if needed (it might be in scenes.json already if exported that way, 
        // but often it's a separate entity)
        // For simplicity, let's assume currentScene.locatie exists or we fetch it
        const url = getSceneGlbUrl(sceneData.locatie || { naam: sceneData.locatie_naam }, sectorData.value);
        if (!url) { resolve(); return; }

        const loader = new GLTFLoader();
        loader.load(url, (gltf) => {
            currentGltf = gltf.scene;
            scene.add(currentGltf);

            let fspyCamera = null;
            currentGltf.traverse((child) => {
                if (child.name.includes('fspy') && child.isCamera) fspyCamera = child;
                if (child.isMesh) {
                    const isFloor = child.name.toLowerCase() === 'floor' || child.name.toLowerCase() === 'plane';
                    if (isFloor) {
                        child.material = new THREE.ShadowMaterial({ opacity: 0.4 });
                        child.receiveShadow = true;
                        child.name = 'floor';
                    } else {
                        child.material = new THREE.MeshBasicMaterial({
                            color: 0xff00ff,
                            transparent: true,
                            opacity: 0,
                            colorWrite: false
                        });
                        child.material.depthWrite = true;
                        child.userData.isHelper = true;
                    }
                }
            });

            if (fspyCamera) {
                camera = fspyCamera;
                camera.aspect = ASPECT_RATIO;
                camera.updateProjectionMatrix();
            }

            // Spawn Logic
            const spawnPoints = sceneData.locatie?.spawn_points?.[sectorData.value.id] || [];
            const charSpawn = spawnPoints.find(p => p.type === 'personage') || { x: 0, y: 0, z: 0 };
            
            spawnCharacter(charSpawn).then(resolve);
            landingDone.value = true; // For now just skip landing animation in engine
        }, undefined, () => resolve());
    });
};

const spawnCharacter = (spawnPoint) => {
    return new Promise((resolve) => {
        // Get selected character from localStorage or setting
        const storedChar = localStorage.getItem('player_character');
        const charName = storedChar ? JSON.parse(storedChar).naam : settings.value.playable;
        
        const charUrl = getCharacterGlbUrl(charName);
        if (!charUrl) { resolve(); return; }

        const loader = new GLTFLoader();
        loader.load(charUrl, (gltf) => {
            playableCharacter = gltf.scene;
            
            const bbox = new THREE.Box3().setFromObject(playableCharacter);
            const size = new THREE.Vector3();
            bbox.getSize(size);
            let scale = 0.5;
            if (size.y > 20) scale *= 0.01;
            
            playableCharacter.scale.set(scale, scale, scale);
            playableCharacter.position.set(spawnPoint.x, spawnPoint.y, spawnPoint.z);
            scene.add(playableCharacter);

            if (gltf.animations?.length > 0) {
                characterMixer = new THREE.AnimationMixer(playableCharacter);
                gltf.animations.forEach(clip => {
                    const name = clip.name.toLowerCase();
                    if (name.startsWith('walk')) characterActions.walk = characterMixer.clipAction(clip);
                    else if (name.startsWith('idle')) characterActions.idle = characterMixer.clipAction(clip);
                });
                if (characterActions.idle) characterActions.idle.play();
            }

            playableCharacter.traverse(child => {
                if (child.isMesh) child.castShadow = true;
            });

            resolve();
        });
    });
};

const animate = () => {
    animationFrameId = requestAnimationFrame(animate);
    const delta = clock ? clock.getDelta() : 0.016;

    if (characterMixer) characterMixer.update(delta);

    if (isWalking.value && playableCharacter) {
        const distance = playableCharacter.position.distanceTo(targetPosition);
        if (distance > 0.1) {
            const lookPos = new THREE.Vector3(targetPosition.x, playableCharacter.position.y, targetPosition.z);
            playableCharacter.lookAt(lookPos);
            const direction = new THREE.Vector3().subVectors(targetPosition, playableCharacter.position).normalize();
            playableCharacter.position.add(direction.multiplyScalar(characterSpeed * delta));
            checkGateways();
        } else {
            isWalking.value = false;
            if (targetPointMesh) targetPointMesh.visible = false;
            checkGateways();
        }
    }
    renderer.render(scene, camera);
};

const onMapClick = (e) => {
    if (activeDialogue.value || !playableCharacter) return;

    const rect = renderer.domElement.getBoundingClientRect();
    const mouseX = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    const mouseY = -((e.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(new THREE.Vector2(mouseX, mouseY), camera);
    const intersects = raycaster.intersectObjects(scene.children, true);
    const floorIntersect = intersects.find(i => 
        !i.object.userData.isCharacter && !i.object.userData.isHelper
    );

    if (floorIntersect) {
        targetPosition.copy(floorIntersect.point);
        isWalking.value = true;
        if (targetPointMesh) {
            targetPointMesh.position.copy(targetPosition);
            targetPointMesh.position.y += 0.01;
            targetPointMesh.visible = true;
        }
    }
};

const checkGateways = () => {
    if (!currentScene.value || !currentScene.value.gateways || !playableCharacter) return;

    const vector = playableCharacter.position.clone();
    vector.project(camera);
    const x = (vector.x + 1) / 2 * 100;
    const y = -(vector.y - 1) / 2 * 100;

    for (const gw of currentScene.value.gateways) {
        if (x >= gw.x && x <= gw.x + gw.width && y >= gw.y && y <= gw.y + gw.height) {
            if (lastTriggeredGateway.value !== gw) {
                lastTriggeredGateway.value = gw;
                handleGateway(gw);
            }
            return;
        }
    }
    lastTriggeredGateway.value = null;
};

const handleGateway = (gw) => {
    if (gw.target_scene_id) {
        // Transition to next scene!
        emit('scene-complete', { targetSceneId: gw.target_scene_id });
    }
};

</script>

<template>
    <div class="walkable-scene-wrapper" @click="onMapClick">
        <div v-if="loading" class="loading-overlay">LADEN_GEOMETRIE...</div>
        <div v-if="error" class="error-overlay">ERROR: {{ error }}</div>
        
        <div ref="canvasContainer" class="canvas-container"></div>
        
        <div class="ui-overlay" v-if="currentScene">
            <div class="scene-header">{{ currentScene.titel }}</div>
        </div>
    </div>
</template>

<style scoped>
.walkable-scene-wrapper {
    width: 100%;
    height: 100%;
    position: relative;
    background: #000;
}

.canvas-container {
    width: 100%;
    height: 100%;
}

.loading-overlay, .error-overlay {
    position: absolute;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.8);
    color: #0ff;
    font-family: monospace;
}

.ui-overlay {
    position: absolute;
    top: 20px;
    left: 20px;
    z-index: 50;
    pointer-events: none;
}

.scene-header {
    color: #fff;
    font-family: 'Wallace', sans-serif;
    font-size: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    text-shadow: 0 0 10px rgba(0,255,255,0.5);
}
</style>
